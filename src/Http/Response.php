<?php
declare(strict_types=1);

namespace App\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $body = null;

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(mixed $data, int $code = 200): void
    {
        $this->statusCode = $code;
        $this->sendHeaders();
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function created(mixed $data = null): void
    {
        $this->json(['success' => true, 'data' => $data], 201);
    }

    public function noContent(): void
    {
        $this->statusCode = 204;
        $this->sendHeaders();
        http_response_code(204);
        exit;
    }

    public function error(string $message, int $code = 400, ?array $errors = null): void
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        $this->json($response, $code);
    }

    public function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, 401);
    }

    public function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, 403);
    }

    public function notFound(string $message = 'Not found'): void
    {
        $this->error($message, 404);
    }

    public function serverError(string $message = 'Internal server error'): void
    {
        $this->error($message, 500);
    }

    public function paginated(array $data, int $total, int $page, int $perPage): void
    {
        $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1,
            ]
        ]);
    }

    private function sendHeaders(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }
}
