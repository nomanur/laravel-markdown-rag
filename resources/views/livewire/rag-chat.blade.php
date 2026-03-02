<div class="rm-rag-container">
    <div class="rm-rag-card">
        <header class="rm-rag-header">
            <div class="rm-rag-header-content">
                <h3 class="rm-rag-title">Knowledge Base Assistant</h3>
                <p class="rm-rag-subtitle">Powered by Gemini & Smart RAG</p>
            </div>
            <div class="rm-rag-status-badge">
                <span class="rm-rag-status-dot"></span>
                Online
            </div>
        </header>

        <div class="rm-rag-messages" 
             x-init="$el.scrollTop = $el.scrollHeight" 
             x-on:message-sent.window="$nextTick(() => $el.scrollTo({ top: $el.scrollHeight, behavior: 'smooth' }))">
            
            @forelse ($messages as $message)
                <div 
                    wire:key="msg-{{ $message['id'] ?? md5($message['content'] . $message['created_at']) }}"
                    wire:transition
                    @class([
                        'rm-rag-message-wrapper',
                        'rm-rag-user' => $message['role'] === 'user',
                        'rm-rag-assistant' => $message['role'] === 'assistant',
                    ])
                >
                    <div class="rm-rag-bubble-container">
                        <div class="rm-rag-bubble">
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                    </div>
                    <span class="rm-rag-timestamp">
                        {{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}
                    </span>
                </div>
            @empty
                <div class="rm-rag-empty" wire:transition>
                    <div class="rm-rag-empty-icon-wrapper">
                        <svg class="rm-rag-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <p class="rm-rag-empty-text">Ask anything about your knowledge base</p>
                    <p class="rm-rag-empty-subtext">The AI will search through your documents to provide accurate answers.</p>
                </div>
            @endforelse
            
            <div wire:loading wire:target="send" class="rm-rag-message-wrapper rm-rag-assistant" wire:transition>
                <div class="rm-rag-bubble-container">
                    <div class="rm-rag-bubble rm-rag-thinking">
                        <div class="rm-rag-typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        AI is searching your knowledge base...
                    </div>
                </div>
            </div>
        </div>

        <footer class="rm-rag-footer">
            <form wire:submit="send" class="rm-rag-form">
                <div class="rm-rag-input-glass">
                    <input 
                        wire:model="prompt" 
                        type="text"
                        placeholder="Ask a question..." 
                        autocomplete="off"
                        class="rm-rag-input"
                    />
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled"
                        class="rm-rag-submit"
                    >
                        <span wire:loading.remove wire:target="send">
                            <svg class="rm-rag-send-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </span>
                        <span wire:loading wire:target="send" class="rm-rag-loading-spinner"></span>
                    </button>
                </div>
            </form>
        </footer>
    </div>
</div>

<style>
    :root {
        --rm-rag-primary: #6366f1;
        --rm-rag-primary-hover: #4f46e5;
        --rm-rag-bg: #f8fafc;
        --rm-rag-glass: rgba(255, 255, 255, 0.7);
        --rm-rag-border: rgba(226, 232, 240, 0.8);
        --rm-rag-text-main: #1e293b;
        --rm-rag-text-muted: #64748b;
        --rm-rag-user-bubble: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --rm-rag-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    }

    [data-theme='dark'] {
        :root {
            --rm-rag-bg: #0f172a;
            --rm-rag-glass: rgba(30, 41, 59, 0.7);
            --rm-rag-border: rgba(51, 65, 85, 0.8);
            --rm-rag-text-main: #f1f5f9;
            --rm-rag-text-muted: #94a3b8;
            --rm-rag-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --rm-rag-bg: #0f172a;
            --rm-rag-glass: rgba(30, 41, 59, 0.7);
            --rm-rag-border: rgba(51, 65, 85, 0.8);
            --rm-rag-text-main: #f1f5f9;
            --rm-rag-text-muted: #94a3b8;
            --rm-rag-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }
    }

    .rm-rag-container {
        display: flex;
        flex-direction: column;
        height: min(700px, calc(100vh - 4rem));
        width: 100%;
        max-width: 48rem;
        margin: 2rem auto;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        perspective: 1000px;
    }

    .rm-rag-card {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--rm-rag-bg);
        border: 1px solid var(--rm-rag-border);
        border-radius: 1.5rem;
        box-shadow: var(--rm-rag-shadow);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .rm-rag-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--rm-rag-border);
        background: var(--rm-rag-glass);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .rm-rag-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--rm-rag-text-main);
        letter-spacing: -0.025em;
    }

    .rm-rag-subtitle {
        margin: 0.125rem 0 0 0;
        font-size: 0.875rem;
        color: var(--rm-rag-text-muted);
    }

    .rm-rag-status-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .rm-rag-status-dot {
        width: 0.5rem;
        height: 0.5rem;
        background: #22c55e;
        border-radius: 50%;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        animation: pulse-green 2s infinite;
    }

    .rm-rag-messages {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
        scroll-behavior: smooth;
    }

    /* Track styles */
    .rm-rag-messages::-webkit-scrollbar { width: 6px; }
    .rm-rag-messages::-webkit-scrollbar-track { background: transparent; }
    .rm-rag-messages::-webkit-scrollbar-thumb { background: var(--rm-rag-border); border-radius: 10px; }

    .rm-rag-message-wrapper {
        display: flex;
        flex-direction: column;
        max-width: 80%;
        animation: slide-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .rm-rag-user { align-self: flex-end; align-items: flex-end; }
    .rm-rag-assistant { align-self: flex-start; align-items: flex-start; }

    .rm-rag-bubble-container { position: relative; }

    .rm-rag-bubble {
        padding: 0.875rem 1.25rem;
        border-radius: 1.25rem;
        font-size: 0.9375rem;
        line-height: 1.5;
        position: relative;
    }

    .rm-rag-user .rm-rag-bubble {
        background: var(--rm-rag-user-bubble);
        color: white;
        border-bottom-right-radius: 0.25rem;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
    }

    .rm-rag-assistant .rm-rag-bubble {
        background: var(--rm-rag-glass);
        color: var(--rm-rag-text-main);
        border: 1px solid var(--rm-rag-border);
        border-bottom-left-radius: 0.25rem;
    }

    .rm-rag-timestamp {
        font-size: 0.7rem;
        color: var(--rm-rag-text-muted);
        margin-top: 0.5rem;
        padding: 0 0.5rem;
    }

    .rm-rag-thinking {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--rm-rag-text-muted) !important;
        font-style: normal !important;
    }

    .rm-rag-typing { display: flex; gap: 4px; }
    .rm-rag-typing span {
        width: 6px;
        height: 6px;
        background: var(--rm-rag-primary);
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out;
        opacity: 0.6;
    }
    .rm-rag-typing span:nth-child(2) { animation-delay: 0.2s; }
    .rm-rag-typing span:nth-child(3) { animation-delay: 0.4s; }

    .rm-rag-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        text-align: center;
        padding: 4rem 2rem;
    }

    .rm-rag-empty-icon-wrapper {
        width: 5rem;
        height: 5rem;
        background: rgba(99, 102, 241, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 2rem;
        margin-bottom: 2rem;
        color: var(--rm-rag-primary);
    }

    .rm-rag-empty-icon { width: 3rem; height: 3rem; }

    .rm-rag-empty-text {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--rm-rag-text-main);
        margin: 0;
    }

    .rm-rag-empty-subtext {
        font-size: 0.9375rem;
        color: var(--rm-rag-text-muted);
        margin: 0.5rem 0 0 0;
        max-width: 24rem;
    }

    .rm-rag-footer {
        padding: 1.5rem;
        border-top: 1px solid var(--rm-rag-border);
        background: var(--rm-rag-glass);
    }

    .rm-rag-form { width: 100%; }

    .rm-rag-input-glass {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--rm-rag-bg);
        border: 1px solid var(--rm-rag-border);
        border-radius: 1rem;
        padding: 0.5rem 0.5rem 0.5rem 1.25rem;
        transition: all 0.2s;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .rm-rag-input-glass:focus-within {
        border-color: var(--rm-rag-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .rm-rag-input {
        flex: 1;
        padding: 0.5rem 0;
        border: none;
        background: transparent;
        color: var(--rm-rag-text-main);
        font-size: 0.9375rem;
        outline: none;
    }

    .rm-rag-submit {
        height: 2.75rem;
        width: 2.75rem;
        background: var(--rm-rag-primary);
        color: white;
        border: none;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .rm-rag-submit:hover {
        background: var(--rm-rag-primary-hover);
        transform: scale(1.05);
    }

    .rm-rag-submit:active { transform: scale(0.95); }
    .rm-rag-submit:disabled { opacity: 0.5; cursor: not-allowed; }

    .rm-rag-send-icon { width: 1.25rem; height: 1.25rem; }

    .rm-rag-loading-spinner {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes typing { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    @keyframes slide-in { from { opacity: 0; transform: translateY(10px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }

    @media (max-width: 640px) {
        .rm-rag-container { height: calc(100vh - 2rem); margin: 1rem; }
        .rm-rag-messages { padding: 1.25rem; }
        .rm-rag-message-wrapper { max-width: 90%; }
    }
</style>
