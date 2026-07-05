<?php
declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__, 2) . "/views/{$view}.php";

        if (!is_file($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Wrap in layout if it exists
        $layoutPath = dirname(__DIR__, 2) . '/views/layouts/main.php';
        if (is_file($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function with(string $key, mixed $value): static
    {
        $_SESSION['flash'][$key] = $value;
        return $this;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate(array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $this->input($field);
            $rulesList = is_string($ruleSet) ? explode('|') : $ruleSet;

            foreach ($rulesList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($rule) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field] = ucfirst($field) . ' is required.';
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid email.';
                        }
                        break;
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$params[0]) {
                            $errors[$field] = ucfirst($field) . " must be at least {$params[0]} characters.";
                        }
                        break;
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$params[0]) {
                            $errors[$field] = ucfirst($field) . " must not exceed {$params[0]} characters.";
                        }
                        break;
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = ucfirst($field) . ' must be numeric.';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
