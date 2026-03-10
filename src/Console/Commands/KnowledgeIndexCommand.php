<?php

namespace Nomanur\Console\Commands;

use Illuminate\Console\Command;
use Nomanur\Services\VectorService;
use Illuminate\Support\Facades\File;

class KnowledgeIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markdownrag:index {--clear : Clear existing chunks before indexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index markdown files from knowledge base into vector database';

    /**
     * Execute the console command.
     */
    public function handle(VectorService $vectorService)
    {
        if ($this->option('clear')) {
            $this->info('Clearing existing knowledge chunks...');
            \Nomanur\Models\KnowledgeChunk::truncate();
        }

        $this->info('Starting knowledge base indexing...');

        $knowledgePath = public_path(config('laravel-markdown-rag.markdown_info_path', 'knowledge-base'));
        
        if (!File::exists($knowledgePath)) {
            $this->error("Knowledge base path not found: {$knowledgePath}");
            return;
        }

        $files = File::allFiles($knowledgePath);
        $documents = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $documents[] = [
                    'path' => $file->getRealPath(),
                    'name' => $file->getFilename(),
                ];

                // Ensure the document exists in the knowledge_documents table
                // This makes it easier for admins to customize prompts later.
                \Nomanur\Models\KnowledgeDocument::firstOrCreate(
                    ['name' => $file->getFilename()],
                    ['path' => $file->getRealPath()]
                );
            }
        }
        
        if (empty($documents)) {
            $this->error('No markdown files found in ' . $knowledgePath);
            return;
        }

        $chunks = $vectorService->chunkDocuments($documents);
        $total = count($chunks);
        $batchSize = config('laravel-markdown-rag.markdown_embedding_batch_size', 50);
        
        $this->info("Found {$total} chunks. Generating embeddings in batches of {$batchSize}...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $chunkBatches = array_chunk($chunks, $batchSize);

        foreach ($chunkBatches as $batch) {
            $texts = array_column($batch, 'text');
            
            try {
                $embeddings = $vectorService->getEmbeddings($texts);
                
                if (count($embeddings) !== count($batch)) {
                    $this->error("\nEmbedding mismatch: Expected " . count($batch) . " but got " . count($embeddings));
                    continue;
                }

                foreach ($batch as $index => $chunk) {
                    $vectorService->storeChunk($chunk['text'], $embeddings[$index], $chunk['source'], $chunk['document_id'] ?? null);
                    $bar->advance();
                }
            } catch (\Exception $e) {
                $this->error("\nError indexing batch: {$e->getMessage()}");
                // Advance bar for failed batch to keep progress accurate
                $bar->advance(count($batch));
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Knowledge base indexed successfully!');
    }
}
