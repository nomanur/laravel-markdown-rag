<?php

namespace Nomanur\Tests\Unit;

require_once __DIR__ . '/../Mocks/LaravelAiMocks.php';

use Nomanur\Services\VectorService;
use Nomanur\Models\KnowledgeChunk;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;

class VectorServiceTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_exists_and_has_core_methods()
    {
        $service = new VectorService();
        $this->assertTrue(method_exists($service, 'storeChunk'));
        $this->assertTrue(method_exists($service, 'search'));
    }
}
