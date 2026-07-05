<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Api\AuthController;
use App\Api\JobsController;
use App\Api\ApplicationsController;

class ApiControllersTest extends TestCase
{
    public function test_auth_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(AuthController::class));
    }

    public function test_jobs_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(JobsController::class));
    }

    public function test_applications_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ApplicationsController::class));
    }
}
