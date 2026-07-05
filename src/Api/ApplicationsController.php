<?php
declare(strict_types=1);

namespace App\Api;

use App\Http\Request;
use App\Http\Response;

class ApplicationsController extends ApiController
{
    public function index(): void
    {
        global $pdo;
        if (!$this->authenticate()) return;

        $where = "";
        $params = [];

        if ($this->currentUser['role'] === 'applicant') {
            $where = "WHERE ca.user_id = ?";
            $params[] = $this->currentUser['id'];
        } elseif ($this->currentUser['role'] === 'employer') {
            $where = "WHERE j.created_by = ?";
            $params[] = $this->currentUser['id'];
        } else {
            $where = "WHERE 1=1";
        }

        if ($status = $this->request->query('status')) {
            $where .= " AND ca.status = ?";
            $params[] = $status;
        }
        if ($jobId = $this->request->query('job_id')) {
            $where .= " AND ca.job_id = ?";
            $params[] = (int) $jobId;
        }

        $query = "SELECT ca.*, j.title as job_title, u.username as applicant_name, d.name as department_name
                  FROM centralized_applications ca
                  JOIN jobs j ON ca.job_id = j.id
                  JOIN users u ON ca.user_id = u.id
                  LEFT JOIN departments d ON j.department_id = d.id
                  $where
                  ORDER BY ca.created_at DESC";

        $result = $this->paginate($query, $params);
        $this->response->paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
    }

    public function show(int $id): void
    {
        global $pdo;
        if (!$this->authenticate()) return;

        $stmt = $pdo->prepare("
            SELECT ca.*, j.title as job_title, j.description as job_description,
                   u.username as applicant_name, u.email as applicant_email, u.phone as applicant_phone,
                   d.name as department_name
            FROM centralized_applications ca
            JOIN jobs j ON ca.job_id = j.id
            JOIN users u ON ca.user_id = u.id
            LEFT JOIN departments d ON j.department_id = d.id
            WHERE ca.id = ?
        ");
        $stmt->execute([$id]);
        $app = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$app) { $this->response->notFound('Application not found.'); return; }

        // Authorization check
        if ($this->currentUser['role'] === 'applicant' && $app['user_id'] != $this->currentUser['id']) {
            $this->response->forbidden('Access denied.');
            return;
        }

        $this->response->json(['success' => true, 'data' => $app]);
    }

    public function store(): void
    {
        global $pdo;
        if (!$this->authenticate() || !$this->requireRole('applicant')) return;

        $data = $this->request->json() ?? $this->request->all();
        $errors = $this->validateRequired($data, ['job_id' => 'required']);

        if (!empty($errors)) {
            $this->response->error('Validation failed.', 422, $errors);
            return;
        }

        // Check job exists and is approved
        $stmt = $pdo->prepare("SELECT id, status FROM jobs WHERE id = ?");
        $stmt->execute([$data['job_id']]);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$job || $job['status'] !== 'approved') {
            $this->response->error('Job not found or not accepting applications.');
            return;
        }

        // Check for duplicate
        $check = $pdo->prepare("SELECT id FROM centralized_applications WHERE user_id = ? AND job_id = ?");
        $check->execute([$this->currentUser['id'], $data['job_id']]);
        if ($check->fetch()) {
            $this->response->error('You have already applied to this job.', 409);
            return;
        }

        $appNumber = 'APP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO centralized_applications (application_number, user_id, job_id, status, preferred_positions, created_at)
            VALUES (?, ?, ?, 'submitted', ?, NOW())
        ");
        $stmt->execute([
            $appNumber,
            $this->currentUser['id'],
            $data['job_id'],
            $data['preferred_positions'] ?? null,
        ]);

        $this->response->created([
            'id' => (int) $pdo->lastInsertId(),
            'application_number' => $appNumber,
            'message' => 'Application submitted successfully.',
        ]);
    }

    public function updateStatus(int $id): void
    {
        global $pdo;
        if (!$this->authenticate() || !$this->requireRole('employer', 'admin')) return;

        $data = $this->request->json() ?? $this->request->all();
        $status = $data['status'] ?? '';
        $allowed = ['under_review', 'shortlisted', 'interview_scheduled', 'offered', 'hired', 'rejected'];

        if (!in_array($status, $allowed)) {
            $this->response->error('Invalid status. Allowed: ' . implode(', ', $allowed), 422);
            return;
        }

        $stmt = $pdo->prepare("SELECT id, user_id FROM centralized_applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$app) { $this->response->notFound('Application not found.'); return; }

        $notes = $data['notes'] ?? null;
        $stmt = $pdo->prepare("
            UPDATE centralized_applications 
            SET status = ?, updated_at = NOW(), updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $this->currentUser['id'], $id]);

        // Log status change
        $oldStmt = $pdo->prepare("SELECT status FROM centralized_applications WHERE id = ?");
        $oldStmt->execute([$id]);
        $oldApp = $oldStmt->fetch(\PDO::FETCH_ASSOC);
        $oldStatus = $oldApp ? $oldApp['status'] : 'unknown';

        $auditStmt = $pdo->prepare("
            INSERT INTO application_audit_log (application_id, old_status, new_status, changed_by, notes, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $auditStmt->execute([$id, $oldStatus, $status, $this->currentUser['id'], $notes]);

        $this->response->json(['success' => true, 'message' => "Application status updated to $status."]);
    }

    public function destroy(int $id): void
    {
        global $pdo;
        if (!$this->authenticate()) return;

        $stmt = $pdo->prepare("SELECT user_id, status FROM centralized_applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$app) { $this->response->notFound('Application not found.'); return; }

        if ($this->currentUser['role'] !== 'admin' && $app['user_id'] != $this->currentUser['id']) {
            $this->response->forbidden('Access denied.');
            return;
        }

        if (!in_array($app['status'], ['draft', 'submitted', 'withdrawn'])) {
            $this->response->error('Cannot withdraw application in current status.');
            return;
        }

        $stmt = $pdo->prepare("UPDATE centralized_applications SET status = 'withdrawn', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        $this->response->json(['success' => true, 'message' => 'Application withdrawn.']);
    }
}
