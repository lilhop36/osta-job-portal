<?php
declare(strict_types=1);

namespace App\Middleware;

class DashboardMiddleware
{
    private array $rolePaths = [
        'admin'    => '/admin/dashboard.php',
        'employer' => '/employer/dashboard.php',
        'applicant'=> '/applicant/dashboard.php',
    ];

    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: /login.php');
            return false;
        }

        return true;
    }
}
