<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;

class ConnectionTest extends TestCase
{
    public function test_connection_class_exists(): void
    {
        $this->assertTrue(class_exists(Connection::class));
    }
}
