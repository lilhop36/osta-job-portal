<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\BaseModel;

class BaseModelTest extends TestCase
{
    public function test_canary(): void
    {
        $this->assertTrue(true);
    }

    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(BaseModel::class));
    }
}
