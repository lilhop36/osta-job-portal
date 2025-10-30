<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/application_functions.php';

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get user's centralized application
$stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
$stmt->execute([$user_id]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: centralized_application.php');
    exit;
}

// Get existing documents
$doc_stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY document_type, created_at DESC");
$doc_stmt->execute([$application['id']]);
$existing_documents = $doc_stmt->fetchAll();

// Group documents by type
$documents_by_type = [];
foreach ($existing_documents as $doc) {
    $documents_by_type[$doc['document_type']][] = $doc;
}

// Define document types and requirements
$document_types = [
    'resume' => ['label' => 'Resume/CV', 'required' => true, 'max_size' => 5, 'formats' => ['pdf', 'doc', 'docx']],
    'cover_letter' => ['label' => 'Cover Letter', 'required' => false, 'max_size' => 3, 'formats' => ['pdf', 'doc', 'docx']],
    'national_id' => ['label' => 'National ID', 'required' => true, 'max_size' => 2, 'formats' => ['pdf', 'jpg', 'jpeg', 'png']],
    'passport' => ['label' => 'Passport', 'required' => false, 'max_size' => 2, 'formats' => ['pdf', 'jpg', 'jpeg', 'png']],
    'diploma' => ['label' => 'Diploma/Degree Certificate', 'required' => true, 'max_size' => 3, 'formats' => ['pdf', 'jpg', 'jpeg', 'png']],
    'transcript' => ['label' => 'Academic Transcript', 'required' => true, 'max_size' => 3, 'formats' => ['pdf', 'jpg', 'jpeg', 'png']],
    'certificate' => ['label' => 'Professional Certificates', 'required' => false, 'max_size' => 3, 'formats' => ['pdf', 'jpg', 'jpeg', 'png']],
    'recommendation_letter' => ['label' => 'Recommendation Letters', 'required' => false, 'max_size' => 3, 'formats' => ['pdf', 'doc', 'docx']],
    'other' => ['label' => 'Other Documents', 'required' => false, 'max_size' => 3, 'formats' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']]
];

// Handle file upload
if ($_POST && verify_csrf_token($_POST['csrf_token'])) {
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        try {
            $document_type = $_POST['document_type'];
            $file = $_FILES['document'];
            
            // Validate document type
            if (!isset($document_types[$document_type])) {
                throw new Exception("Invalid document type");
            }
            
            $type_config = $document_types[$document_type];
            
            // Validate file size (convert MB to bytes)
            $max_size = $type_config['max_size'] * 1024 * 1024;
            if ($file['size'] > $max_size) {
                throw new Exception("File size exceeds {$type_config['max_size']}MB limit");
            }
            
            // Validate file extension
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $type_config['formats'])) {
                throw new Exception("Invalid file format. Allowed formats: " . implode(', ', $type_config['formats']));
            }
            
            // Validate MIME type for security
            $allowed_mimes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!isset($allowed_mimes[$file_extension]) || $mime_type !== $allowed_mimes[$file_extension]) {
                throw new Exception("File type validation failed. Please upload a valid file.");
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/application_documents/{$application['id']}/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $stored_filename = $document_type . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $stored_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception("Failed to upload file");
            }
            
            // Insert document record
            $stmt = $pdo->prepare("INSERT INTO application_documents 
                                 (application_id, document_type, original_filename, stored_filename, file_path, 
                                  file_size, mime_type, is_required, verification_status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->execute([
                $application['id'],
                $document_type,
                $file['name'],
                $stored_filename,
                $file_path,
                $file['size'],
                $mime_type,
                $type_config['required'] ? 1 : 0
            ]);
            
            // Log the action
            log_audit_action($user_id, 'upload', 'application_documents', $pdo->lastInsertId(), 
                           "Uploaded {$document_type} document: {$file['name']}");
            
            $success_message = "Document uploaded successfully!";
            
            // Refresh documents
            $doc_stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY document_type, created_at DESC");
            $doc_stmt->execute([$application['id']]);
            $existing_documents = $doc_stmt->fetchAll();
            
            // Regroup documents by type
            $documents_by_type = [];
            foreach ($existing_documents as $doc) {
                $documents_by_type[$doc['document_type']][] = $doc;
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            log_debug("Document upload error: " . $e->getMessage());
        }
    } else {
        $error_message = "Please select a file to upload";
    }
}

// Handle document deletion
if (isset($_POST['delete_document']) && verify_csrf_token($_POST['csrf_token'])) {
    try {
        $document_id = $_POST['document_id'];
        
        // Get document details
        $stmt = $pdo->prepare("SELECT * FROM application_documents WHERE id = ? AND application_id = ?");
        $stmt->execute([$document_id, $application['id']]);
        $document = $stmt->fetch();
        
        if (!$document) {
            throw new Exception("Document not found");
        }
        
        // Delete file from filesystem
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM application_documents WHERE id = ?");
        $stmt->execute([$document_id]);
        
        // Log the action
        log_audit_action($user_id, 'delete', 'application_documents', $document_id, 
                       "Deleted document: {$document['original_filename']}");
        
        $success_message = "Document deleted successfully!";
        
        // Refresh documents
        $doc_stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY document_type, created_at DESC");
        $doc_stmt->execute([$application['id']]);
        $existing_documents = $doc_stmt->fetchAll();
        
        // Regroup documents by type
        $documents_by_type = [];
        foreach ($existing_documents as $doc) {
            $documents_by_type[$doc['document_type']][] = $doc;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        log_debug("Document deletion error: " . $e->getMessage());
    }
}

$page_title = "Document Upload";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-file-upload me-2"></i>
                            Document Upload
                        </h4>
                        <div>
                            <span class="badge bg-light text-dark">
                                Application: <?= htmlspecialchars($application['application_number']) ?>
                            </span>
                            <span class="badge bg-<?= get_status_color($application['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application['status'] !== 'draft'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Your application has been submitted. You can still upload additional documents, but they will require verification.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Upload Form -->
                    <div class="row mb-4">
                        <div class="col-lg-6">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>
                                        Upload New Document
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <?= generate_csrf_token() ?>
                                        
                                        <div class="mb-3">
                                            <label for="document_type" class="form-label">Document Type *</label>
                                            <select class="form-select" id="document_type" name="document_type" required>
                                                <option value="">Select document type</option>
                                                <?php foreach ($document_types as $type => $config): ?>
                                                    <option value="<?= $type ?>">
                                                        <?= $config['label'] ?>
                                                        <?= $config['required'] ? ' (Required)' : ' (Optional)' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="document" class="form-label">Choose File *</label>
                                            <input type="file" class="form-control" id="document" name="document" required>
                                            <div class="form-text" id="file-requirements">
                                                Select a document type to see file requirements.
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Upload Document
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload Guidelines -->
                        <div class="col-lg-6">
                            <div class="card border-info">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Upload Guidelines
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Ensure documents are clear and readable
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Upload original or certified copies
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            File names should be descriptive
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                            Required documents must be uploaded before submission
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-shield-alt text-info me-2"></i>
                                            All documents are securely stored and encrypted
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Uploaded Documents -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-folder-open me-2"></i>
                                Uploaded Documents
                            </h5>
                            
                            <?php if (empty($existing_documents)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No documents uploaded yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($document_types as $type => $config): ?>
                                        <?php if (isset($documents_by_type[$type])): ?>
                                            <div class="col-lg-6 mb-4">
                                                <div class="card">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-file me-2"></i>
                                                            <?= $config['label'] ?>
                                                            <?php if ($config['required']): ?>
                                                                <span class="badge bg-danger ms-2">Required</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($documents_by_type[$type] as $doc): ?>
                                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                                                <div class="flex-grow-1">
                                                                    <div class="fw-bold"><?= htmlspecialchars($doc['original_filename']) ?></div>
                                                                    <small class="text-muted">
                                                                        <?= formatFileSize($doc['file_size']) ?> • 
                                                                        Uploaded: <?= date('M j, Y g:i A', strtotime($doc['created_at'])) ?>
                                                                    </small>
                                                                    <div>
                                                                        <span class="badge bg-<?= getVerificationColor($doc['verification_status']) ?>">
                                                                            <?= ucfirst($doc['verification_status']) ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="ms-2">
                                                                    <a href="download_document.php?id=<?= $doc['id'] ?>" 
                                                                       class="btn btn-sm btn-outline-primary me-1" 
                                                                       title="Download">
                                                                        <i class="fas fa-download"></i>
                                                                    </a>
                                                                    <?php if ($application['status'] === 'draft'): ?>
                                                                        <form method="POST" class="d-inline" 
                                                                              onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                                            <?= generate_csrf_token() ?>
                                                                            <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                            <button type="submit" name="delete_document" 
                                                                                    class="btn btn-sm btn-outline-danger" 
                                                                                    title="Delete">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="centralized_application.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Application
                                </a>
                                
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic file requirements display
document.getElementById('document_type').addEventListener('change', function() {
    const documentTypes = <?= json_encode($document_types) ?>;
    const selectedType = this.value;
    const requirementsDiv = document.getElementById('file-requirements');
    
    if (selectedType && documentTypes[selectedType]) {
        const config = documentTypes[selectedType];
        requirementsDiv.innerHTML = `
            <strong>Requirements:</strong><br>
            • Max size: ${config.max_size}MB<br>
            • Allowed formats: ${config.formats.join(', ').toUpperCase()}<br>
            • ${config.required ? 'This document is required' : 'This document is optional'}
        `;
        requirementsDiv.className = 'form-text text-info';
    } else {
        requirementsDiv.innerHTML = 'Select a document type to see file requirements.';
        requirementsDiv.className = 'form-text';
    }
});

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php 
// Helper functions
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getVerificationColor($status) {
    $colors = [
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

include '../includes/footer_new.php'; 
?>
