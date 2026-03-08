<?php

namespace Laravel\Ai\Contracts;

interface Tool {
    public function name(): string;
    public function description(): \Stringable|string;
    public function handle(\Laravel\Ai\Tools\Request $request): \Stringable|string;
    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array;
}

interface Agent {}
interface Conversational {}
interface HasTools {}

namespace Laravel\Ai\Tools;
class Request extends \Illuminate\Support\Fluent {}

namespace Laravel\Ai\Messages;
class Message {
    public function __construct(public string $role, public string $content) {}
}

namespace Laravel\Ai;
class Embeddings {
    public static function for($texts) { return new self; }
    public function generate() { return (object)['embeddings' => []]; }
}

namespace Laravel\Ai;
trait Promptable {
    public function prompt($prompt) { return "AI response"; }
}
