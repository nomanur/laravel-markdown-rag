<?php

namespace Nomanur\Tests\Unit;

require_once __DIR__ . '/../Mocks/LaravelAiMocks.php';

use Nomanur\Ai\Agents\KnowledgeAgent;
use Nomanur\Models\KnowledgeDocument;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery as m;

class DynamicInstructionsTest extends TestCase
{
    protected $container;
    protected $configData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $this->configData = [];
        
        $this->container->bind('config', function() {
            return new class($this) {
                private $test;
                public function __construct($test) { $this->test = $test; }
                public function get($key, $default = null) { 
                    return $this->test->getConfig($key, $default); 
                }
            };
        });
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);
    }

    public function getConfig($key, $default = null)
    {
        return $this->configData[$key] ?? $default;
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    protected function getUser()
    {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $guarded = [];
        };
        if (!class_exists('App\Models\User')) {
            class_alias(get_class($user), 'App\Models\User');
        }
        return new \App\Models\User();
    }

    public function test_it_returns_custom_instructions_from_document()
    {
        // 1. Highest priority: Document-specific prompt
        $mockDoc = m::mock(KnowledgeDocument::class);
        $mockDoc->allows()->getAttribute('system_prompt')->andReturn('Document Prompt');
        $mockDoc->allows()->offsetExists('system_prompt')->andReturn(true);

        $agent = new KnowledgeAgent($this->getUser(), 'test_doc', $mockDoc);
        
        $this->assertEquals('Document Prompt', $agent->instructions());
    }

    public function test_it_falls_back_to_global_config()
    {
        // 2. Middle priority: Global config
        $this->configData['laravel-markdown-rag.markdown_default_agent_prompt'] = 'Global Configuration Prompt';

        $agent = new KnowledgeAgent($this->getUser(), null, null);
        
        $this->assertEquals('Global Configuration Prompt', $agent->instructions());
    }

    public function test_it_falls_back_to_hardcoded_safety_net()
    {
        // 3. Lowest priority: Hardcoded safety net
        // No document, and config returns null (default in our mock setup)
        $this->configData = []; 

        $agent = new KnowledgeAgent($this->getUser(), null, null);
        
        $this->assertEquals('You are a helpful assistant.', $agent->instructions());
    }
}
