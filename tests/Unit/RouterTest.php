<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Router;

class RouterTest extends TestCase
{
    public function test_router_class_exists(): void
    {
        $this->assertTrue(class_exists(Router::class));
    }

    public function test_router_can_be_instantiated(): void
    {
        $router = new Router();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function test_router_has_get_method(): void
    {
        $router = new Router();
        $this->assertTrue(method_exists($router, 'get'));
    }

    public function test_router_has_post_method(): void
    {
        $router = new Router();
        $this->assertTrue(method_exists($router, 'post'));
    }

    public function test_router_has_put_method(): void
    {
        $router = new Router();
        $this->assertTrue(method_exists($router, 'put'));
    }

    public function test_router_has_delete_method(): void
    {
        $router = new Router();
        $this->assertTrue(method_exists($router, 'delete'));
    }
}
