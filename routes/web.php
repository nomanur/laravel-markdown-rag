Route::get('vector-embedding', function() {
    set_time_limit(120); // Increase timeout for t-SNE

    $chunks = \Nomanurrahman\Models\KnowledgeChunk::latest()->limit(100)->get();
    
    if ($chunks->isEmpty()) {
        return view('laravel-markdown-rag::vector-embedding', ['data' => []]);
    }

    $embeddings = $chunks->pluck('embedding')->toArray();
    $vectorService = new \Nomanurrahman\Services\VectorService();
    $reduced = $vectorService->reduceDimensions($embeddings);

    $data = $chunks->map(function($chunk, $index) use ($reduced) {
        return [
            'x' => $reduced[$index][0],
            'y' => $reduced[$index][1],
            'text' => Illuminate\Support\Str::limit($chunk->content, 200),
            'source' => $chunk->source,
        ];
    })->toArray();

    return view('laravel-markdown-rag::vector-embedding', ['data' => $data]);
})->middleware(['auth', 'verified'])->name('vector-embedding');

Route::get('/markdownrag', function () {
    return view('without-flux');
})->name('markdownrag');