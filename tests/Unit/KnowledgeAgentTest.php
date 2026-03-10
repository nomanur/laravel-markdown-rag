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
        $this->container->singleton('cache', function() {
            return new class {
                public function remember($key, $ttl, $callback) { return $callback(); }
                public function forget($key) { return true; }
            };
        });
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(KnowledgeAgent::class);
        if ($reflection->hasProperty('instructionsResolver')) {
            $property = $reflection->getProperty('instructionsResolver');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }

        parent::tearDown();
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
        
        // Use Mockery to avoid DB lookup in constructor
        $mockDoc = \Mockery::mock(\Nomanur\Models\KnowledgeDocument::class);
        
        $agent = new KnowledgeAgent($user, 'doc_123', $mockDoc);
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
    public function test_it_passes_tool_description_to_tool()
    {
        $user = new \App\Models\User();
        $mockDoc = \Mockery::mock(\Nomanur\Models\KnowledgeDocument::class);
        $mockDoc->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockDoc->shouldReceive('getAttribute')->with('tool_description')->andReturn('Specific document search description');
        
        $agent = new KnowledgeAgent($user, 'doc_123', $mockDoc);
        $tools = $agent->tools();
        $this->assertEquals('Specific document search description', $tools[0]->description());
    }

    public function test_it_returns_default_instructions_without_document()
    {
        $user = new \App\Models\User();
        $agent = new KnowledgeAgent($user);
        
        $instructions = (string) $agent->instructions();
        
        $this->assertEquals("You are a helpful assistant.", $instructions);
    }

    public function test_it_returns_document_instructions_when_available()
    {
        $user = new \App\Models\User();
        $mockDoc = \Mockery::mock(\Nomanur\Models\KnowledgeDocument::class);
        $mockDoc->shouldReceive('getAttribute')->with('system_prompt')->andReturn('You are an expert on Laravel.');
        
        $agent = new KnowledgeAgent($user, 'doc_123', $mockDoc);
        $instructions = (string) $agent->instructions();
        
        $this->assertEquals('You are an expert on Laravel.', $instructions);
    }

    public function test_it_can_resolve_instructions_using_closure()
    {
        KnowledgeAgent::resolveInstructionsUsing(function ($agent) {
            return "Custom Global Instructions";
        });
        
        $user = new \App\Models\User();
        $agent = new KnowledgeAgent($user);
        
        $instructions = (string) $agent->instructions();
        
        $this->assertEquals("Custom Global Instructions", $instructions);
    }
}
