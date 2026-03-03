<?php

namespace Nomanur\Livewire;

use Livewire\Component;
use Nomanur\Ai\Agents\KnowledgeAgent;
use Nomanur\Models\History;
use Illuminate\Support\Facades\Log;

class WithoutFlux extends Component
{
    public string $prompt = '';
    public array $messages = [];
    public int $testCount = 0;

    public function test()
    {
        $this->testCount++;
        Log::info('Test incremented');
    }

    public function mount(): void
    {
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        $user = auth()->user();

        if (!$user) {
            $this->messages = [];
            return;
        }

        $this->messages = History::where('user_id', $user->id)
            ->where('agent', 'knowledge')
            ->latest()
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    public function send(): void
    {
        Log::info('Without-Flux Send Triggered', ['prompt' => $this->prompt]);

        if (empty(trim($this->prompt))) {
            return;
        }

        $user = auth()->user();

        if (!$user) {
            Log::error('Without-Flux User not authenticated');
            return;
        }

        $prompt = $this->prompt;
        $this->prompt = '';

        try {
            // Save User Message
            History::create([
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $prompt,
                'agent' => 'knowledge',
            ]);

            $this->loadMessages();
            $this->dispatch('message-sent');

            Log::info('Without-Flux User message saved');

            // Get AI Response using KnowledgeAgent (RAG)
            $ai = new KnowledgeAgent($user);
            $response = (string) $ai->prompt($prompt);

            Log::info('Without-Flux AI response received');

            // Save Assistant Response
            History::create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $response,
                'agent' => 'knowledge',
            ]);

            $this->loadMessages();
            $this->dispatch('message-sent');
        } catch (\Exception $e) {
            Log::error('Without-Flux Execution Error', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('laravel-markdown-rag::components.without-flux');
    }
}
