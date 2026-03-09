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
        if (!$this->document) {
            return config('laravel-markdown-rag.markdown_default_agent_prompt') 
                ?? "You are a helpful assistant.";
        }

        return \Illuminate\Support\Facades\Cache::remember("doc_{$this->document->id}_system_prompt", 3600, function () {
            return $this->document->getAttribute('system_prompt') 
                ?? config('laravel-markdown-rag.markdown_default_agent_prompt') 
                ?? "You are a helpful assistant.";
        });
    }

    /**
     * Get the timeout for the agent.
     */
    public function timeout(): int
    {
        return 60000;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
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
            $description = \Illuminate\Support\Facades\Cache::remember("doc_{$this->document->id}_tool_desc", 3600, function () {
                return $this->document->getAttribute('tool_description') 
                    ?? "Search across all available company documents and knowledge.";
            });
        }

        return [
            new KnowledgeSearchTool($this->user, $this->documentId, $description),
        ];
    }
}
