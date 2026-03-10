<?php

namespace Nomanur\Ai\Agents;

use Stringable;
use Illuminate\Contracts\Auth\Authenticatable;
use Nomanur\Models\KnowledgeDocument;
use Nomanur\Models\History;
use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Messages\Message;
use Nomanur\Ai\Tools\KnowledgeSearchTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Conversational;

class KnowledgeAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * The callback that should be used to resolve the agent's messages.
     *
     * @var (callable(\Nomanur\Ai\Agents\KnowledgeAgent): iterable)|null
     */
    protected static $messagesResolver;

    /**
     * The callback that should be used to resolve the agent's instructions.
     *
     * @var (callable(\Nomanur\Ai\Agents\KnowledgeAgent): (\Stringable|string))|null
     */
    protected static $instructionsResolver;


    public function __construct(
        public Authenticatable $user,
        protected ?string $documentId = null,
        protected ?KnowledgeDocument $document = null
    ) {
        if (!$this->document && $this->documentId) {
            $this->document = KnowledgeDocument::where('name', $this->documentId)
                ->orWhere('path', $this->documentId)
                ->first();
        }
    }


    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        if (static::$instructionsResolver) {
            return call_user_func(static::$instructionsResolver, $this);
        }

        if (!$this->document) {
            return config('laravel-markdown-rag.markdown_default_agent_prompt') 
                ?? "You are a helpful assistant.";
        }

        return $this->document->getAttribute('system_prompt') 
            ?? config('laravel-markdown-rag.markdown_default_agent_prompt') 
            ?? "You are a helpful assistant.";
    }

    /**
     * Set the callback that should be used to resolve the agent's instructions.
     */
    public static function resolveInstructionsUsing(callable $resolver): void
    {
        static::$instructionsResolver = $resolver;
    }

    /**
     * Get the timeout for the agent.
     */
    public function timeout(): int
    {
        return 60000;
    }

    /**
     * Set the callback that should be used to resolve the agent's messages.
     */
    public static function resolveMessagesUsing(callable $resolver): void
    {
        static::$messagesResolver = $resolver;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        if (static::$messagesResolver) {
            return call_user_func(static::$messagesResolver, $this);
        }

        return $this->getConversationMessages();
    }

    /**
     * Get the default conversation messages.
     */
    protected function getConversationMessages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->where('agent', 'knowledge')
            ->latest()
            ->when(config('laravel-markdown-rag.markdown_chat_rate_limit'), fn($query, $limit) => $query->limit($limit))
            ->get()
            ->reverse()
            ->map(fn($message) => new Message($message->role, $message->content))
            ->all();
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        if (!$this->document) {
            $description = "Search across all available company documents and knowledge.";
        } else {
            $description = $this->document->getAttribute('tool_description') 
                ?? "Search across all available company documents and knowledge.";
        }

        return [
            new KnowledgeSearchTool($this->user, $this->documentId, $description),
        ];
    }
}
