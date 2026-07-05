<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Request;
use App\Http\Response;

class ResponseTest extends TestCase
{
    public function test_response_class_exists(): void
    {
        $this->assertTrue(class_exists(Response::class));
    }

    public function test_request_class_exists(): void
    {
        $this->assertTrue(class_exists(Request::class));
    }
}
