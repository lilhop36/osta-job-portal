<?php
declare(strict_types=1);

namespace App\Http;

class Request
{
    private array $query;
    private array $body;
    private array $headers;
    private string $method;
    private string $uri;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->headers = $this->parseHeaders();

        $contentType = $this->header('Content-Type') ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $this->jsonBody = json_decode($raw, true) ?? [];
            $this->body = $this->jsonBody;
        } else {
            $this->body = $_POST;
        }
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization') ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->jsonBody ?? [];
        return ($this->jsonBody ?? [])[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('content-type') ?? '', 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string ...$keys): bool
    {
        $data = $this->all();
        foreach ($keys as $key) {
            if (!isset($data[$key]) || $data[$key] === '') return false;
        }
        return true;
    }

    public function filled(string ...$keys): bool
    {
        return $this->has(...$keys);
    }
}
