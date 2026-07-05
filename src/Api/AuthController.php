<?php
declare(strict_types=1);

namespace App\Api;

use App\Http\Request;
use App\Http\Response;

class AuthController extends ApiController
{
    public function login(): void
    {
        global $pdo;
        $email = $this->request->json('email') ?? $this->request->body('email');
        $password = $this->request->json('password') ?? $this->request->body('password');

        if (!$email || !$password) {
            $this->response->error('Email and password are required.', 422, [
                'email' => !$email ? 'Email is required.' : null,
                'password' => !$password ? 'Password is required.' : null,
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT id, username, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->response->unauthorized('Invalid email or password.');
            return;
        }

        if ($user['status'] !== 'active') {
            $this->response->error('Account is not active. Please verify your email or contact support.', 403);
            return;
        }

        // Generate API token
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET api_token = ?, api_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
        $stmt->execute([$token, $user['id']]);

        $this->response->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]
        ]);
    }

    public function register(): void
    {
        global $pdo;
        $data = $this->request->json() ?? $this->request->all();

        $errors = $this->validateRequired($data, [
            'username' => 'required|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'phone' => 'required',
        ]);

        if (!empty($errors)) {
            $this->response->error('Validation failed.', 422, $errors);
            return;
        }

        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$data['email'], $data['username']]);
        if ($stmt->fetch()) {
            $this->response->error('Email or username already exists.', 409);
            return;
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'applicant', 'pending')");
        $stmt->execute([$data['username'], $data['email'], $data['phone'], $hashedPassword]);
        $userId = $pdo->lastInsertId();

        $this->response->created([
            'id' => (int) $userId,
            'username' => $data['username'],
            'email' => $data['email'],
            'message' => 'Registration successful. Please verify your email.',
        ]);
    }

    public function logout(): void
    {
        global $pdo;
        if ($this->currentUser) {
            $stmt = $pdo->prepare("UPDATE users SET api_token = NULL, api_token_expires = NULL WHERE id = ?");
            $stmt->execute([$this->currentUser['id']]);
        }
        $this->response->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function me(): void
    {
        $this->response->json([
            'success' => true,
            'data' => $this->currentUser,
        ]);
    }
}
