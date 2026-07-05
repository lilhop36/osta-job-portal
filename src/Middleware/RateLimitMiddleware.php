<?php
declare(strict_types=1);

namespace App\Middleware;

class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 60, int $windowSeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): bool
    {
        $key = 'rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'window_start' => $now];
        }

        $rate = &$_SESSION[$key];

        // Reset window if expired
        if (($now - $rate['window_start']) > $this->windowSeconds) {
            $rate = ['count' => 0, 'window_start' => $now];
        }

        $rate['count']++;

        if ($rate['count'] > $this->maxAttempts) {
            http_response_code(429);
            header('Retry-After: ' . $this->windowSeconds);
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            return false;
        }

        return true;
    }
}
