<?php

namespace Nomanur\Ai\Agents;

use Stringable;
use App\Models\User;
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
        public User $user,
        protected ?string $documentId = null
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return "You are an internal corporate assistant. Your goal is to help users by searching through the knowledge base. 
        Always use the 'search_knowledge_base' tool when asked about company policies, products, employees, or contracts.
        Provide concise and accurate information based on the search results. If you cannot find the information, let the user know.
        Cite your sources if the search results provide them.";
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
        return [
            new KnowledgeSearchTool($this->user, $this->documentId),
        ];
    }
}
