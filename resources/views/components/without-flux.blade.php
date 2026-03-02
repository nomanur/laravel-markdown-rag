<?php

use Livewire\Component;
use Nomanurrahman\Ai\Agents\KnowledgeAgent;
use Nomanurrahman\Models\History;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public string $prompt = '';
    public array $messages = [];
    public int $testCount = 0;

    public function test() { $this->testCount++; Log::info('Test incremented'); }

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

            // Get AI Response using Kno    wledgeAgent (RAG)
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
};
?>



<div 
    class="rt-chat-container"
    x-data="{ 
        scrollToBottom() { 
            this.$nextTick(() => {
                const container = this.$refs.messages;
                if (container) {
                    container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                }
            });
        }
    }"
    x-init="scrollToBottom()"
    x-on:message-sent.window="scrollToBottom()"
>
    <!-- Chat Card -->
        <!-- Header -->
        <header class="rt-chat-header">
            <div class="rt-header-info">
                <div class="rt-status-indicator">
                    <span class="rt-status-dot"></span>
                </div>
                <div>
                    <h3 class="rt-title">AI Assistant (No-Flux)</h3>
                    <p class="rt-subtitle">Standalone Premium Interface</p>
                </div>
            </div>
            <div class="rt-header-actions">
                <span class="rt-badge">RAG Active</span>
                <button wire:click="test" class="rt-badge ml-2">Click Test: {{ $testCount }}</button>
            </div>
        </header>

        <!-- Messages Area -->
        <div 
            class="rt-messages-area" 
            x-ref="messages"
        >
            @forelse ($messages as $message)
                <div 
                    wire:key="msg-{{ $message['id'] ?? md5($message['content'] . ($message['created_at'] ?? '')) }}"
                    @class([
                        'rt-message-wrapper',
                        'rt-user' => $message['role'] === 'user',
                        'rt-assistant' => $message['role'] === 'assistant',
                    ])
                >
                    <div class="rt-message-bubble">
                        @if ($message['role'] === 'assistant')
                            <div x-data="{ content: @js($message['content']) }" x-html="marked.parse(content)" class="rt-prose"></div>
                        @else
                            <p>{{ $message['content'] }}</p>
                        @endif
                    </div>
                    @if(isset($message['created_at']))
                        <span class="rt-timestamp">{{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}</span>
                    @endif
                </div>
            @empty
                <div class="rt-empty-state">
                    <div class="rt-empty-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <h4>Welcome to the knowledge base</h4>
                    <p>This version is optimized to run without Flux UI dependencies.</p>
                </div>
            @endforelse

            <!-- Thinking Indicator -->
            <div wire:loading wire:target="send" class="rt-message-wrapper rt-assistant">
                <div class="rt-message-bubble rt-thinking">
                    <div class="rt-typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <footer class="rt-chat-footer">
            <div class="rt-input-group">
                <input 
                    type="text" 
                    wire:model="prompt" 
                    placeholder="Type your message..."
                    class="rt-input-field"
                    wire:loading.attr="disabled"
                >
                <button 
                    type="button"
                    wire:click="send"
                    wire:loading.attr="disabled"
                    class="rt-send-button"
                >
                    <span wire:loading.remove wire:target="send">
                        <svg style="width: 20px; height: 20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </span>
                    <span wire:loading wire:target="send" class="rt-loading-spin"></span>
                </button>
            </div>
        </footer>
    </div>

    <style>
        .rt-chat-container {
            --rt-primary: #6366f1;
            --rt-primary-dark: #4f46e5;
            --rt-bg: #ffffff;
            --rt-text: #1f2937;
            --rt-text-muted: #6b7280;
            --rt-glass: rgba(255, 255, 255, 0.8);
            --rt-border: rgba(229, 231, 235, 0.8);
            --rt-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            
            width: 100%;
            max-width: 800px;
            margin: 2rem auto;
            height: 600px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        @media (prefers-color-scheme: dark) {
            .rt-chat-container {
                --rt-bg: #111827;
                --rt-text: #f9fafb;
                --rt-text-muted: #9ca3af;
                --rt-glass: rgba(17, 24, 39, 0.8);
                --rt-border: rgba(31, 41, 55, 0.8);
            }
        }

        .rt-chat-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: var(--rt-bg);
            border: 1px solid var(--rt-border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .rt-chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--rt-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--rt-glass);
        }

        .rt-header-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .rt-status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: block;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: rt-pulse 2s infinite;
        }

        .rt-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--rt-text);
            margin: 0;
        }

        .rt-subtitle {
            font-size: 0.75rem;
            color: var(--rt-text-muted);
            margin: 0;
        }

        .rt-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--rt-primary);
            border-radius: 2rem;
        }

        .rt-messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            scroll-behavior: smooth;
        }

        .rt-message-wrapper {
            display: flex;
            flex-direction: column;
            max-width: 85%;
            animation: rt-slide-up 0.3s ease-out;
        }

        .rt-message-wrapper.rt-user {
            align-self: flex-end;
            align-items: flex-end;
        }

        .rt-message-wrapper.rt-assistant {
            align-self: flex-start;
        }

        .rt-message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .rt-user .rt-message-bubble {
            background: var(--rt-primary);
            color: white;
            border-bottom-right-radius: 0.25rem;
        }

        .rt-assistant .rt-message-bubble {
            background: var(--rt-glass);
            color: var(--rt-text);
            border: 1px solid var(--rt-border);
            border-bottom-left-radius: 0.25rem;
        }

        .rt-prose :first-child { margin-top: 0; }
        .rt-prose :last-child { margin-bottom: 0; }
        .rt-prose code { background: rgba(0,0,0,0.05); padding: 0.1rem 0.3rem; border-radius: 4px; }
        .rt-prose pre { background: #1f2937; color: #f9fafb; padding: 0.75rem; border-radius: 8px; overflow-x: auto; margin: 0.5rem 0; }

        .rt-timestamp {
            font-size: 0.7rem;
            color: var(--rt-text-muted);
            margin-top: 0.4rem;
        }

        .rt-chat-footer {
            padding: 1.25rem;
            background: var(--rt-glass);
            border-top: 1px solid var(--rt-border);
        }

        .rt-input-group {
            display: flex;
            background: var(--rt-bg);
            border: 1px solid var(--rt-border);
            border-radius: 0.75rem;
            padding: 0.4rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .rt-input-group:focus-within {
            border-color: var(--rt-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .rt-input-field {
            flex: 1;
            background: transparent;
            border: none;
            padding: 0.5rem 0.75rem;
            color: var(--rt-text);
            font-size: 0.9375rem;
            outline: none;
        }

        .rt-send-button {
            background: var(--rt-primary);
            color: white;
            border: none;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.1s, background-color 0.2s;
        }

        .rt-send-button:hover { background: var(--rt-primary-dark); }
        .rt-send-button:active { transform: scale(0.95); }
        .rt-send-button:disabled { opacity: 0.5; cursor: not-allowed; }

        .rt-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            padding: 2rem;
            color: var(--rt-text-muted);
        }

        .rt-empty-icon {
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            opacity: 0.2;
        }

        .rt-empty-state h4 { margin: 0; color: var(--rt-text); width: 100%; }
        .rt-empty-state p { font-size: 0.875rem; max-width: 280px; }

        .rt-thinking { padding: 0.8rem 1rem; }
        .rt-typing-indicator { display: flex; gap: 4px; }
        .rt-typing-indicator span {
            width: 6px;
            height: 6px;
            background: var(--rt-text-muted);
            border-radius: 50%;
            animation: rt-typing 1.4s infinite ease-in-out;
            opacity: 0.4;
        }
        .rt-typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .rt-typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes rt-typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes rt-slide-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes rt-pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .rt-loading-spin {
            width: 1.2rem;
            height: 1.2rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: rt-spin 0.8s linear infinite;
        }

        @keyframes rt-spin { to { transform: rotate(360deg); } }

        /* Custom Scrollbar */
        .rt-messages-area::-webkit-scrollbar { width: 5px; }
        .rt-messages-area::-webkit-scrollbar-track { background: transparent; }
        .rt-messages-area::-webkit-scrollbar-thumb { background: var(--rt-border); border-radius: 10px; }
    </style>
</div>