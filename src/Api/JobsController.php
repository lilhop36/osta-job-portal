<?php
declare(strict_types=1);

namespace App\Api;

use App\Http\Request;
use App\Http\Response;

class JobsController extends ApiController
{
    public function index(): void
    {
        global $pdo;

        $where = "WHERE j.status = 'approved'";
        $params = [];

        if ($keyword = $this->request->query('keyword')) {
            $where .= " AND (j.title LIKE ? OR j.description LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        if ($type = $this->request->query('type')) {
            $where .= " AND j.employment_type = ?";
            $params[] = $type;
        }
        if ($deptId = $this->request->query('department_id')) {
            $where .= " AND j.department_id = ?";
            $params[] = (int) $deptId;
        }
        if ($locationId = $this->request->query('location_id')) {
            $where .= " AND j.location_id = ?";
            $params[] = (int) $locationId;
        }

        $query = "SELECT j.*, d.name as department_name, l.name as location_name
                  FROM jobs j 
                  LEFT JOIN departments d ON j.department_id = d.id
                  LEFT JOIN locations l ON j.location_id = l.id
                  $where
                  ORDER BY j.created_at DESC";

        $result = $this->paginate($query, $params);
        $this->response->paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
    }

    public function show(int $id): void
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT j.*, d.name as department_name, l.name as location_name, 
                   u.username as posted_by_name,
                   (SELECT COUNT(*) FROM centralized_applications WHERE job_id = j.id) as application_count
            FROM jobs j 
            LEFT JOIN departments d ON j.department_id = d.id
            LEFT JOIN locations l ON j.location_id = l.id
            LEFT JOIN users u ON j.created_by = u.id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$job) {
            $this->response->notFound('Job not found.');
            return;
        }

        $this->response->json(['success' => true, 'data' => $job]);
    }

    public function store(): void
    {
        global $pdo;
        if (!$this->authenticate() || !$this->requireRole('employer', 'admin')) return;

        $data = $this->request->json() ?? $this->request->all();
        $errors = $this->validateRequired($data, [
            'title' => 'required|max:255',
            'description' => 'required',
            'department_id' => 'required',
        ]);

        if (!empty($errors)) {
            $this->response->error('Validation failed.', 422, $errors);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO jobs (title, description, department_id, location_id, employment_type, status, created_by, deadline)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['department_id'],
            $data['location_id'] ?? null,
            $data['employment_type'] ?? 'full_time',
            $this->currentUser['id'],
            $data['deadline'] ?? null,
        ]);

        $this->response->created(['id' => (int) $pdo->lastInsertId(), 'message' => 'Job created. Awaiting admin approval.']);
    }

    public function update(int $id): void
    {
        global $pdo;
        if (!$this->authenticate() || !$this->requireRole('employer', 'admin')) return;

        // Verify ownership
        $stmt = $pdo->prepare("SELECT created_by FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$job) { $this->response->notFound('Job not found.'); return; }
        if ($this->currentUser['role'] !== 'admin' && $job['created_by'] != $this->currentUser['id']) {
            $this->response->forbidden('You can only edit your own jobs.'); return;
        }

        $data = $this->request->json() ?? $this->request->all();
        $fields = [];
        $params = [];
        foreach (['title', 'description', 'department_id', 'location_id', 'employment_type', 'deadline'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) { $this->response->error('No fields to update.'); return; }

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE jobs SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        $this->response->json(['success' => true, 'message' => 'Job updated.']);
    }

    public function destroy(int $id): void
    {
        global $pdo;
        if (!$this->authenticate() || !$this->requireRole('employer', 'admin')) return;

        $stmt = $pdo->prepare("SELECT created_by FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$job) { $this->response->notFound('Job not found.'); return; }
        if ($this->currentUser['role'] !== 'admin' && $job['created_by'] != $this->currentUser['id']) {
            $this->response->forbidden('You can only delete your own jobs.'); return;
        }

        $check = $pdo->prepare("SELECT COUNT(*) as c FROM centralized_applications WHERE job_id = ?");
        $check->execute([$id]);
        if ($check->fetch()['c'] > 0) {
            $this->response->error('Cannot delete job with applications.');
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $this->response->noContent();
    }
}
