<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

use App\Http\Request;
use App\Http\Response;
use App\Api\AuthController;
use App\Api\JobsController;
use App\Api\ApplicationsController;

init_secure_session();

$request = new Request();
$response = new Response();

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(204);
    exit;
}

$method = $request->method();
$uri = $request->uri();

// Strip base path from SITE_URL
$basePath = app_base_path();
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$path = preg_replace('#^/api#', '', $uri);
$path = '/' . trim($path, '/');

// Parse path segments
$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id = isset($segments[1]) ? (int) $segments[1] : null;
$subAction = $segments[2] ?? null;

try {
    switch ($resource) {
        case 'auth':
            $controller = new AuthController($request, $response);
            if ($method === 'POST' && !$id) {
                $controller->login();
            } elseif ($method === 'POST' && $id === null && $subAction === null) {
                // /api/auth with POST - could be login or register based on body
                $action = $request->json('action') ?? $request->body('action') ?? 'login';
                if ($action === 'register') {
                    $controller->register();
                } else {
                    $controller->login();
                }
            } elseif ($method === 'POST' && $segments[1] === 'register') {
                $controller->register();
            } elseif ($method === 'POST' && $segments[1] === 'logout') {
                if ($controller->authenticate()) {
                    $controller->logout();
                }
            } elseif ($method === 'GET' && $segments[1] === 'me') {
                if ($controller->authenticate()) {
                    $controller->me();
                }
            } else {
                $response->error('Endpoint not found.', 404);
            }
            break;

        case 'jobs':
            $controller = new JobsController($request, $response);
            if ($method === 'GET' && !$id) {
                $controller->index();
            } elseif ($method === 'GET' && $id) {
                $controller->show($id);
            } elseif ($method === 'POST' && !$id) {
                $controller->store();
            } elseif ($method === 'PUT' && $id) {
                $controller->update($id);
            } elseif ($method === 'DELETE' && $id) {
                $controller->destroy($id);
            } else {
                $response->error('Endpoint not found.', 404);
            }
            break;

        case 'applications':
            $controller = new ApplicationsController($request, $response);
            if ($method === 'GET' && !$id) {
                $controller->index();
            } elseif ($method === 'GET' && $id) {
                $controller->show($id);
            } elseif ($method === 'POST' && !$id) {
                $controller->store();
            } elseif ($method === 'PUT' && $id && $subAction === 'status') {
                $controller->updateStatus($id);
            } elseif ($method === 'DELETE' && $id) {
                $controller->destroy($id);
            } else {
                $response->error('Endpoint not found.', 404);
            }
            break;

        case '':
            $response->json([
                'name' => 'OSTA Job Portal API',
                'version' => '1.0.0',
                'endpoints' => [
                    'POST /api/auth' => 'Login (send email + password)',
                    'POST /api/auth/register' => 'Register new account',
                    'POST /api/auth/logout' => 'Logout (requires Bearer token)',
                    'GET  /api/auth/me' => 'Get current user (requires Bearer token)',
                    'GET  /api/jobs' => 'List jobs (supports: keyword, type, department_id, location_id, page, per_page)',
                    'GET  /api/jobs/{id}' => 'Get job details',
                    'POST /api/jobs' => 'Create job (employer/admin, Bearer token)',
                    'PUT  /api/jobs/{id}' => 'Update job (owner/admin, Bearer token)',
                    'DELETE /api/jobs/{id}' => 'Delete job (owner/admin, Bearer token)',
                    'GET  /api/applications' => 'List applications (role-filtered, Bearer token)',
                    'GET  /api/applications/{id}' => 'Get application details (Bearer token)',
                    'POST /api/applications' => 'Submit application (applicant, Bearer token)',
                    'PUT  /api/applications/{id}/status' => 'Update status (employer/admin, Bearer token)',
                    'DELETE /api/applications/{id}' => 'Withdraw application (owner/admin, Bearer token)',
                ]
            ]);
            break;

        default:
            $response->error('Endpoint not found.', 404);
    }
} catch (\Throwable $e) {
    error_log("API Error: " . $e->getMessage());
    $response->serverError('An unexpected error occurred.');
}
