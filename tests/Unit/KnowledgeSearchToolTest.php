<?php

namespace Nomanur\Tests\Unit;

require_once __DIR__ . '/../Mocks/LaravelAiMocks.php';

use Nomanur\Ai\Tools\KnowledgeSearchTool;
use PHPUnit\Framework\TestCase;
use Laravel\Ai\Tools\Request;
use Mockery;
use Nomanur\Services\VectorService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

class KnowledgeSearchToolTest extends TestCase
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_has_correct_name_and_description()
    {
        $tool = new KnowledgeSearchTool();
        $this->assertEquals('search_knowledge_base', $tool->name());
        $this->assertStringContainsString('knowledge base', $tool->description());
    }

    public function test_it_filters_by_document_id_when_provided()
    {
        $vectorService = Mockery::mock(VectorService::class);
        $this->container->instance(VectorService::class, $vectorService);

        $vectorService->shouldReceive('search')
            ->with('query string', Mockery::any(), 'my_doc_id')
            ->once()
            ->andReturn(collect());

        $tool = new KnowledgeSearchTool();
        $request = new Request(['query' => 'query string', 'document_id' => 'my_doc_id']);
        
        $tool->handle($request);
        
        $this->addToAssertionCount(1); // Mockery verified the call
    }
}
