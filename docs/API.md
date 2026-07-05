# OSTA Job Portal — REST API Documentation

**Base URL:** `/api`  
**Content-Type:** `application/json`  
**Authentication:** Bearer token in `Authorization` header

## Authentication

### Login
```
POST /api/auth
Body: { "email": "user@example.com", "password": "secret" }
Response: { "success": true, "token": "...", "user": { "id", "username", "email", "role" } }
```

### Register
```
POST /api/auth/register
Body: { "username": "john", "email": "john@example.com", "phone": "+251...", "password": "secret123" }
Response: 201 { "success": true, "data": { "id", "username", "email", "message" } }
```

### Logout
```
POST /api/auth/logout
Authorization: Bearer <token>
Response: { "success": true, "message": "Logged out successfully." }
```

### Current User
```
GET /api/auth/me
Authorization: Bearer <token>
Response: { "success": true, "data": { "id", "username", "email", "role" } }
```

## Jobs

### List Jobs (Public)
```
GET /api/jobs?keyword=developer&type=full_time&department_id=1&page=1&per_page=15
Response: { "success": true, "data": [...], "pagination": { "total", "page", "per_page", "total_pages" } }
```
**Filters:** `keyword`, `type` (full_time/part_time/contract/internship), `department_id`, `location_id`

### Get Job Details (Public)
```
GET /api/jobs/{id}
Response: { "success": true, "data": { "id", "title", "description", "department_name", "application_count", ... } }
```

### Create Job (Employer/Admin)
```
POST /api/jobs
Authorization: Bearer <token>
Body: { "title": "...", "description": "...", "department_id": 1, "employment_type": "full_time" }
Response: 201 { "success": true, "data": { "id", "message" } }
```

### Update Job (Owner/Admin)
```
PUT /api/jobs/{id}
Authorization: Bearer <token>
Body: { "title": "Updated Title" }
Response: { "success": true, "message": "Job updated." }
```

### Delete Job (Owner/Admin)
```
DELETE /api/jobs/{id}
Authorization: Bearer <token>
Response: 204 No Content
```

## Applications

### List Applications (Role-filtered)
```
GET /api/applications?status=submitted&job_id=5&page=1&per_page=15
Authorization: Bearer <token>
Response: { "success": true, "data": [...], "pagination": {...} }
```
- **Applicant:** sees only their own applications
- **Employer:** sees applications to their jobs
- **Admin:** sees all applications

### Get Application Details
```
GET /api/applications/{id}
Authorization: Bearer <token>
Response: { "success": true, "data": { ... } }
```

### Submit Application (Applicant)
```
POST /api/applications
Authorization: Bearer <token>
Body: { "job_id": 5, "preferred_positions": "..." }
Response: 201 { "success": true, "data": { "id", "application_number", "message" } }
```

### Update Application Status (Employer/Admin)
```
PUT /api/applications/{id}/status
Authorization: Bearer <token>
Body: { "status": "shortlisted", "notes": "..." }
Response: { "success": true, "message": "..." }
```
**Allowed statuses:** `under_review`, `shortlisted`, `interview_scheduled`, `offered`, `hired`, `rejected`

### Withdraw Application (Owner/Admin)
```
DELETE /api/applications/{id}
Authorization: Bearer <token>
Response: { "success": true, "message": "Application withdrawn." }
```

## Error Responses
```json
{ "success": false, "message": "Error description" }
{ "success": false, "message": "Validation failed.", "errors": { "field": "error message" } }
```

| Code | Meaning |
|------|---------|
| 400  | Bad Request |
| 401  | Unauthorized (missing/invalid token) |
| 403  | Forbidden (insufficient permissions) |
| 404  | Not Found |
| 409  | Conflict (duplicate) |
| 422  | Validation Error |
| 500  | Server Error |

## Token Lifecycle
- Tokens expire after **30 days**
- Tokens are invalidated on logout
- Include in header: `Authorization: Bearer <token>`
