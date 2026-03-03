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
            }
        }
        
        if (empty($documents)) {
            $this->error('No markdown files found in ' . $knowledgePath);
            return;
        }

        $chunks = $vectorService->chunkDocuments($documents);
        $total = count($chunks);
        
        $this->info("Found {$total} chunks. Generating embeddings...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($chunks as $chunk) {
            try {
                $embedding = $vectorService->getEmbeddings([$chunk['text']])[0];
                $vectorService->storeChunk($chunk['text'], $embedding, $chunk['source']);
            } catch (\Exception $e) {
                $this->error("\nError indexing chunk from {$chunk['source']}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Knowledge base indexed successfully!');
    }
}
