<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Company;
use App\Models\Skill;
use App\Models\SavedJob;
use App\Models\Message;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\PasswordReset;
use App\Models\Interview;

class ModelsExistTest extends TestCase
{
    /**
     * @dataProvider modelProvider
     */
    public function test_model_class_exists(string $className): void
    {
        $this->assertTrue(class_exists($className), "Class $className should exist");
    }

    public function modelProvider(): array
    {
        return [
            'User' => [User::class],
            'Job' => [Job::class],
            'Application' => [Application::class],
            'Department' => [Department::class],
            'Notification' => [Notification::class],
            'Company' => [Company::class],
            'Skill' => [Skill::class],
            'SavedJob' => [SavedJob::class],
            'Message' => [Message::class],
            'AuditLog' => [AuditLog::class],
            'ContactMessage' => [ContactMessage::class],
            'PasswordReset' => [PasswordReset::class],
            'Interview' => [Interview::class],
        ];
    }
}
