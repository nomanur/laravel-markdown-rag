<?php

namespace Nomanur\Tests\Unit;

require_once __DIR__ . '/../Mocks/LaravelAiMocks.php';

use Nomanur\Ai\Agents\KnowledgeAgent;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

class KnowledgeAgentTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $this->container->bind('config', function() {
            return new class {
                public function get($key, $default = null) { return $default; }
            };
        });
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);
    }

    public function test_it_can_be_instantiated_with_document_id()
    {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $guarded = [];
        };
        // We need to alias or mock App\Models\User if it's strictly typed
        if (!class_exists('App\Models\User')) {
            class_alias(get_class($user), 'App\Models\User');
        }
        
        $agent = new KnowledgeAgent($user, 'doc_123');
        $this->assertInstanceOf(KnowledgeAgent::class, $agent);
    }

    public function test_it_can_be_instantiated_without_document_id()
    {
        $user = new \App\Models\User();
        $agent = new KnowledgeAgent($user);
        $this->assertInstanceOf(KnowledgeAgent::class, $agent);
    }

    public function test_it_has_tools()
    {
        $user = new \App\Models\User();
        $agent = new KnowledgeAgent($user);
        $tools = $agent->tools();
        $this->assertIsArray($tools);
    }
}
