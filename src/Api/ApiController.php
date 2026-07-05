<?php
declare(strict_types=1);

namespace App\Api;

use App\Http\Request;
use App\Http\Response;
use App\Models\User;

abstract class ApiController
{
    protected Request $request;
    protected Response $response;
    protected ?array $currentUser = null;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    protected function authenticate(): bool
    {
        global $pdo;
        $token = $this->request->bearerToken();
        if (!$token) {
            $this->response->unauthorized('Bearer token required');
            return false;
        }

        $stmt = $pdo->prepare("SELECT id, username, email, role, status, api_token_expires FROM users WHERE api_token = ? AND status = 'active'");
        $stmt->execute([$token]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->currentUser) {
            $this->response->unauthorized('Invalid or expired token');
            return false;
        }

        // Check if token has expired
        if ($this->currentUser['api_token_expires'] !== null) {
            $expiresAt = strtotime($this->currentUser['api_token_expires']);
            if ($expiresAt !== false && $expiresAt < time()) {
                // Clear expired token
                $clearStmt = $pdo->prepare("UPDATE users SET api_token = NULL, api_token_expires = NULL WHERE id = ?");
                $clearStmt->execute([$this->currentUser['id']]);
                $this->response->unauthorized('Token has expired. Please login again.');
                return false;
            }
        }

        return true;
    }

    protected function requireRole(string ...$roles): bool
    {
        if (!$this->currentUser) {
            $this->response->unauthorized('Authentication required');
            return false;
        }
        if (!in_array($this->currentUser['role'], $roles)) {
            $this->response->forbidden('Insufficient permissions');
            return false;
        }
        return true;
    }

    protected function validateRequired(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if ($rule === 'required' && empty($data[$field])) {
                $errors[$field] = "The $field field is required.";
            } elseif (str_starts_with($rule, 'max:')) {
                $max = (int) substr($rule, 4);
                if (isset($data[$field]) && strlen($data[$field]) > $max) {
                    $errors[$field] = "The $field must not exceed $max characters.";
                }
            } elseif (str_starts_with($rule, 'min:')) {
                $min = (int) substr($rule, 4);
                if (isset($data[$field]) && strlen($data[$field]) < $min) {
                    $errors[$field] = "The $field must be at least $min characters.";
                }
            } elseif ($rule === 'email') {
                if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "The $field must be a valid email address.";
                }
            }
        }
        return $errors;
    }

    protected function paginate(string $query, array $params, int $defaultPerPage = 15): array
    {
        global $pdo;
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', $defaultPerPage)));
        $offset = ($page - 1) * $perPage;

        $countQuery = "SELECT COUNT(*) as total FROM ($query) as subq";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $dataQuery = "$query LIMIT $perPage OFFSET $offset";
        $dataStmt = $pdo->prepare($dataQuery);
        $dataStmt->execute($params);
        $data = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
