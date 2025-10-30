<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/application_functions.php';

// Require admin role
require_role('admin', '../login.php');

// Function to get interview type name
function get_interview_type_name($type_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM interview_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown';
}

// generate_interview_code() function is defined in application_functions.php

// Set security headers
set_security_headers();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate CSRF token if not present
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid CSRF token.';
        header('Location: manage_interviews.php');
        exit;
    }
    
    if (isset($_POST['create_interview'])) {
        try {
            // Validate required fields
            if (empty($_POST['application_id'])) {
                throw new Exception('Application is required');
            }
            if (empty($_POST['primary_interviewer_id'])) {
                throw new Exception('Primary interviewer is required');
            }
            if (empty($_POST['scheduled_date'])) {
                throw new Exception('Interview date is required');
            }
            if (empty($_POST['start_time'])) {
                throw new Exception('Start time is required');
            }
            
            // Verify the primary interviewer exists and is valid
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role IN ('admin', 'employer') AND status = 'active'");
            $stmt->execute([$_POST['primary_interviewer_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid primary interviewer selected');
            }
            
            // Get application details
            $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE id = ?");
            $stmt->execute([$_POST['application_id']]);
            $application = $stmt->fetch();
            
            if (!$application) {
                throw new Exception('Application not found');
            }
            
            // Create interview
            $stmt = $pdo->prepare("INSERT INTO interviews (application_id, interview_type_id, interview_code, interview_type, scheduled_date, start_time, duration_minutes, venue, meeting_link, primary_interviewer_id, status, created_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', ?)");
            
            // Determine interview type from form or default
            $interview_type_enum = $_POST['interview_type'] ?? 'in_person';
            
            $stmt->execute([
                $_POST['application_id'],
                $_POST['interview_type_id'] ?? null,
                $_POST['interview_code'] ?? generate_interview_code(),
                $interview_type_enum,
                $_POST['scheduled_date'],
                $_POST['start_time'],
                $_POST['duration_minutes'] ?? 60,
                $_POST['location'] ?? '',
                $_POST['meeting_link'] ?? null,
                $_POST['primary_interviewer_id'] ?? null,
                $_SESSION['user_id']
            ]);
            
            // Insert panel members into interview_panel_members table if provided
            $interview_id = $pdo->lastInsertId();
            if (isset($_POST['interviewer_ids']) && !empty($_POST['interviewer_ids'])) {
                $interviewer_ids_array = json_decode($_POST['interviewer_ids'], true);
                if (is_array($interviewer_ids_array)) {
                    foreach ($interviewer_ids_array as $member) {
                        if (isset($member['user_id']) && isset($member['role'])) {
                            $panel_stmt = $pdo->prepare("INSERT INTO interview_panel_members (interview_id, user_id, role) VALUES (?, ?, ?)");
                            $panel_stmt->execute([$interview_id, $member['user_id'], $member['role']]);
                        }
                    }
                }
            }
            
            // Update application status and notify applicant
            $notes = "Interview scheduled for " . $_POST['interview_date'] . " at " . $_POST['start_time'];
            if (!empty($_POST['location'])) {
                $notes .= " (Location: " . $_POST['location'] . ")";
            }
            if (!empty($_POST['meeting_link'])) {
                $notes .= "\nMeeting Link: " . $_POST['meeting_link'];
            }
            
            update_application_status(
                $_POST['application_id'], 
                'interview_scheduled',
                $notes,
                $_SESSION['user_id']
            );
            
            $_SESSION['success_message'] = 'Interview scheduled successfully and applicant has been notified.';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error scheduling interview: ' . $e->getMessage();
        }
        
        header('Location: manage_interviews.php');
        exit;
    }
    
    if (isset($_POST['update_interview'])) {
        try {
            // Validate required fields
            if (empty($_POST['interview_id'])) {
                throw new Exception('Interview ID is required');
            }
            
            // Verify the interview exists
            $stmt = $pdo->prepare("SELECT id FROM interviews WHERE id = ?");
            $stmt->execute([$_POST['interview_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid interview selected');
            }
            
            // Update interview
            $stmt = $pdo->prepare("UPDATE interviews SET interview_type_id = ?, interview_code = ?, interview_type = ?, scheduled_date = ?, start_time = ?, duration_minutes = ?, venue = ?, meeting_link = ?, feedback = ? WHERE id = ?");
            
            $stmt->execute([
                $_POST['interview_type_id'] ?? null,
                $_POST['interview_code'] ?? null,
                $_POST['interview_type'] ?? 'in_person',
                $_POST['scheduled_date'],
                $_POST['start_time'],
                $_POST['duration_minutes'] ?? 60,
                $_POST['location'] ?? '',
                $_POST['meeting_link'] ?? null,
                $_POST['feedback'] ?? null,
                $_POST['interview_id']
            ]);
            
            // Update panel members if provided
            if (isset($_POST['interviewer_ids']) && !empty($_POST['interviewer_ids'])) {
                $interviewer_ids_array = json_decode($_POST['interviewer_ids'], true);
                if (is_array($interviewer_ids_array)) {
                    // Delete existing panel members
                    $delete_stmt = $pdo->prepare("DELETE FROM interview_panel_members WHERE interview_id = ?");
                    $delete_stmt->execute([$_POST['interview_id']]);
                    
                    // Insert new panel members
                    foreach ($interviewer_ids_array as $member) {
                        if (isset($member['user_id']) && isset($member['role'])) {
                            $panel_stmt = $pdo->prepare("INSERT INTO interview_panel_members (interview_id, user_id, role) VALUES (?, ?, ?)");
                            $panel_stmt->execute([$_POST['interview_id'], $member['user_id'], $member['role']]);
                        }
                    }
                }
            }
            
            $_SESSION['success_message'] = 'Interview updated successfully.';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error updating interview: ' . $e->getMessage();
        }
        
        header('Location: manage_interviews.php');
        exit;
    }
    
    if (isset($_POST['delete_interview'])) {
        try {
            // Validate required fields
            if (empty($_POST['interview_id'])) {
                throw new Exception('Interview ID is required');
            }
            
            // Verify the interview exists
            $stmt = $pdo->prepare("SELECT id FROM interviews WHERE id = ?");
            $stmt->execute([$_POST['interview_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid interview selected');
            }
            
            $stmt = $pdo->prepare("DELETE FROM interviews WHERE id = ?");
            $stmt->execute([$_POST['interview_id']]);
            
            $_SESSION['success_message'] = 'Interview deleted successfully.';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error deleting interview: ' . $e->getMessage();
        }
        
        header('Location: manage_interviews.php');
        exit;
    }
}

// Get interview types
$interview_types = $pdo->query("SELECT * FROM interview_types ORDER BY name")->fetchAll();

// Debug: Check if we can connect to the database
try {
    $test = $pdo->query("SELECT 1")->fetch();
    
    // First, let's see all centralized applications
    $all_apps = $pdo->query("SELECT ca.id, ca.application_number, ca.status, ca.preferred_positions, u.full_name, u.email 
                            FROM centralized_applications ca
                            JOIN users u ON ca.user_id = u.id
                            ORDER BY ca.created_at DESC")->fetchAll();
    
    // Check which applications already have interviews
    $apps_with_interviews = $pdo->query("SELECT DISTINCT application_id FROM interviews WHERE application_id IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get applications that don't have interviews yet
    $applications = $pdo->query("SELECT ca.*, u.full_name, u.email 
                               FROM centralized_applications ca
                               JOIN users u ON ca.user_id = u.id
                               WHERE ca.id NOT IN (SELECT COALESCE(application_id, 0) FROM interviews)
                               AND ca.status IN ('submitted', 'shortlisted', 'under_review')
                               ORDER BY ca.created_at DESC")->fetchAll();
    
    // Debug output
    error_log("Total applications: " . count($all_apps));
    error_log("Applications with interviews: " . count($apps_with_interviews));
    error_log("Available applications for interview: " . count($applications));
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $applications = [];
    $all_apps = [];
}

// Get interviews with application and user details
$stmt = $pdo->prepare("
    SELECT 
        i.id, 
        i.application_id, 
        i.interview_type_id, 
        i.interview_code,
        i.interview_type,
        i.scheduled_date, 
        i.start_time, 
        i.duration_minutes,
        i.venue as location,
        i.meeting_link,
        i.status,
        i.feedback,
        i.overall_rating,
        i.recommendation,
        i.created_at,
        ca.application_number, 
        u.first_name, 
        u.last_name, 
        u.email,
        u.full_name,
        ca.preferred_positions,
        COALESCE(it.name, i.interview_type) as interview_type_name
    FROM interviews i
    JOIN centralized_applications ca ON i.application_id = ca.id
    JOIN users u ON ca.user_id = u.id
    LEFT JOIN interview_types it ON i.interview_type_id = it.id
    ORDER BY i.scheduled_date DESC, i.start_time DESC
");
$stmt->execute();
$interviews_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now enrich with panel members
$interviews = array_map(function($interview) use ($pdo) {
    $panel_stmt = $pdo->prepare("
        SELECT ipm.user_id, ipm.role, u.first_name, u.last_name 
        FROM interview_panel_members ipm
        JOIN users u ON ipm.user_id = u.id
        WHERE ipm.interview_id = ?
    ");
    $panel_stmt->execute([$interview['id']]);
    $interview['panel_members'] = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);
    return $interview;
}, $interviews_data);

// Get interviewers (admins and HR staff)
$interviewers = $pdo->query("SELECT id, first_name, last_name, email, full_name FROM users WHERE role IN ('admin', 'hr') ORDER BY first_name, last_name")->fetchAll();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Manage Interviews - Admin";
include '../includes/header.php';

// Page-specific scripts injected via footer hook
if (!function_exists('page_specific_scripts')) {
    function page_specific_scripts() {
        ?>
<script>
    // Initialize DataTables if available
    (function() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            jQuery(function($){
                $('#interviewsTable').DataTable({
                    order: [[3, 'desc']],
                    pageLength: 25
                });
            });
        }
    })();

    // Populate Schedule Interview modal with fresh data from API
    (function() {
        var modalEl = document.getElementById('createInterviewModal');
        if (!modalEl) return;
        
        // Add event listener for application selection
        modalEl.addEventListener('change', function(e) {
            if (e.target.id === 'application_id') {
                var selectedOption = e.target.options[e.target.selectedIndex];
                var positionsDisplay = document.getElementById('preferred_positions_display');
                var positionsList = document.getElementById('positions_list');
                
                if (selectedOption.value && selectedOption.textContent) {
                    // Extract positions from the option text (everything in parentheses except the application number and date)
                    var text = selectedOption.textContent;
                    var positionsMatch = text.match(/\(([^)]+)\)\s*-\s*[A-Z]+-\d+-\d+/);
                    if (positionsMatch && positionsMatch[1]) {
                        positionsDisplay.style.display = 'block';
                        positionsList.textContent = positionsMatch[1];
                    } else {
                        positionsDisplay.style.display = 'none';
                    }
                } else {
                    positionsDisplay.style.display = 'none';
                }
            }
        });
        
        modalEl.addEventListener('show.bs.modal', function () {
            var modal = this;
            var applicationSelect = modal.querySelector('#application_id');
            var typeSelect = modal.querySelector('#interview_type_id');
            var interviewerSelect = modal.querySelector('#primary_interviewer_id');
            var codeInput = modal.querySelector('#interview_code');

            [applicationSelect, typeSelect, interviewerSelect].forEach(function(sel){
                if (!sel) return;
                sel.innerHTML = '<option value="">Loading...</option>';
                sel.disabled = true;
            });

            fetch('api/get_schedule_interview_data.php', { credentials: 'same-origin' })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    if (!json.success) { throw new Error(json.error || 'Unknown error'); }
                    var data = json.data || {};

                    if (applicationSelect) {
                        applicationSelect.disabled = false;
                        applicationSelect.innerHTML = '<option value="">Choose an application</option>';
                        (data.applications || []).forEach(function(app){
                            var createdStr = app.created_at ? new Date(app.created_at).toLocaleDateString() : '';
                            
                            // Parse preferred positions
                            var positionsText = '';
                            if (app.preferred_positions) {
                                try {
                                    var positions = JSON.parse(app.preferred_positions);
                                    if (Array.isArray(positions) && positions.length > 0) {
                                        positionsText = ' (' + positions.slice(0, 3).join(', ') + (positions.length > 3 ? ' + ' + (positions.length - 3) + ' more' : '') + ')';
                                    }
                                } catch (e) {
                                    // If JSON parsing fails, use the raw value
                                    positionsText = ' (' + app.preferred_positions + ')';
                                }
                            }
                            
                            var text = (app.first_name + ' ' + app.last_name + positionsText + ' - ' + app.application_number + (createdStr ? (' - ' + createdStr) : ''));
                            var opt = document.createElement('option');
                            opt.value = app.id;
                            opt.textContent = text;
                            applicationSelect.appendChild(opt);
                        });
                    }

                    if (typeSelect) {
                        typeSelect.disabled = false;
                        typeSelect.innerHTML = '<option value="">Choose interview category</option>';
                        (data.interview_types || []).forEach(function(type){
                            var opt = document.createElement('option');
                            opt.value = type.id;
                            opt.textContent = type.name;
                            typeSelect.appendChild(opt);
                        });
                    }

                    if (interviewerSelect) {
                        interviewerSelect.disabled = false;
                        interviewerSelect.innerHTML = '<option value="">Select primary interviewer</option>';
                        (data.interviewers || []).forEach(function(interviewer){
                            var opt = document.createElement('option');
                            opt.value = interviewer.id;
                            opt.textContent = interviewer.first_name + ' ' + interviewer.last_name;
                            interviewerSelect.appendChild(opt);
                        });
                    }

                    if (codeInput && data.default_interview_code) {
                        codeInput.value = data.default_interview_code;
                    }
                    
                    // Hide positions display when modal opens
                    var positionsDisplay = document.getElementById('preferred_positions_display');
                    if (positionsDisplay) {
                        positionsDisplay.style.display = 'none';
                    }
                })
                .catch(function(err) {
                    console.error('Error loading schedule interview data:', err);
                    alert('Error loading data. Please try again.');
                    [applicationSelect, typeSelect, interviewerSelect].forEach(function(sel){
                        if (sel) {
                            sel.innerHTML = '<option value="">Error loading data</option>';
                            sel.disabled = true;
                        }
                    });
                });
        });
    })();
</script>
<?php 
        // Security hook to prevent back navigation
        prevent_back_navigation();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div class="alert alert-info">
                <h5>Debug Information</h5>
                <p><strong>Total Applications:</strong> <?= count($all_apps ?? []) ?></p>
                <p><strong>Applications with Interviews:</strong> <?= count($apps_with_interviews ?? []) ?></p>
                <p><strong>Available for Interview:</strong> <?= count($applications) ?></p>
                
                <?php if (!empty($all_apps)): ?>
                <h6>All Applications:</h6>
                <ul>
                    <?php foreach ($all_apps as $app): ?>
                        <li>#<?= $app['application_number'] ?> - <?= htmlspecialchars($app['full_name']) ?> (<?= $app['status'] ?>)
                            <?php
                            // Show preferred positions in debug
                            $positions = [];
                            if (!empty($app['preferred_positions'])) {
                                $pos_array = json_decode($app['preferred_positions'], true);
                                if (is_array($pos_array)) {
                                    $positions = $pos_array;
                                }
                            }
                            if (!empty($positions)) {
                                echo '<br><small class="text-muted">Positions: ' . htmlspecialchars(implode(', ', $positions)) . '</small>';
                            } else {
                                echo '<br><small class="text-muted">Positions: Not specified</small>';
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-comments me-2"></i>Manage Interviews</h2>
                <div>
                    <a href="?debug=1" class="btn btn-outline-secondary btn-sm me-2">Debug Info</a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInterviewModal">
                        <i class="fas fa-plus me-1"></i>Schedule Interview
                    </button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Interviews Table -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-list me-2"></i>Scheduled Interviews</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($interviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No interviews scheduled</h5>
                            <p class="text-muted">Schedule interviews using the button above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="interviewsTable">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Application #</th>
                                        <th>Position Applied</th>
                                        <th>Interview Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interviews as $interview): ?>
                                        <tr>
                                            <td>
                                                <div><?= htmlspecialchars($interview['full_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($interview['email']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($interview['application_number']) ?></td>
                                            <td>
                                                <?php
                                                // Display preferred positions
                                                $positions = [];
                                                if (!empty($interview['preferred_positions'])) {
                                                    $pos_array = json_decode($interview['preferred_positions'], true);
                                                    if (is_array($pos_array)) {
                                                        $positions = $pos_array;
                                                    }
                                                }
                                                if (!empty($positions)) {
                                                    echo htmlspecialchars(implode(', ', array_slice($positions, 0, 2)));
                                                    if (count($positions) > 2) {
                                                        echo ' <span class="text-muted">+' . (count($positions) - 2) . ' more</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">Not specified</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($interview['interview_type_name']) ?></td>
                                            <td><?= date('M j, Y', strtotime($interview['scheduled_date'])) ?></td>
                                            <td>
                                                <?= date('g:i A', strtotime($interview['start_time'])) ?>
                                                <?php if (!empty($interview['end_time'])): ?>
                                                    - <?= date('g:i A', strtotime($interview['end_time'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= !empty($interview['location']) ? htmlspecialchars($interview['location']) : '<span class="text-muted">Not specified</span>' ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'scheduled' => 'bg-info',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    'rescheduled' => 'bg-warning'
                                                ][$interview['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $status_class ?>">
                                                    <?= ucfirst(htmlspecialchars($interview['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editInterviewModal" 
                                                            data-interview-id="<?= $interview['id'] ?>"
                                                            data-application-id="<?= $interview['application_id'] ?>"
                                                            data-interview-type-id="<?= $interview['interview_type_id'] ?? '' ?>"
                                                            data-interview-type="<?= $interview['interview_type'] ?? 'in_person' ?>"
                                                            data-interview-date="<?= $interview['scheduled_date'] ?>"
                                                            data-start-time="<?= $interview['start_time'] ?>"
                                                            data-panel-members="<?= htmlspecialchars(json_encode($interview['panel_members'])) ?>"
                                                            data-location="<?= htmlspecialchars($interview['location'] ?? '') ?>"
                                                            data-feedback="<?= htmlspecialchars($interview['feedback'] ?? '') ?>"
                                                            data-interview-code="<?= htmlspecialchars($interview['interview_code'] ?? '') ?>"
                                                            data-duration-minutes="<?= htmlspecialchars($interview['duration_minutes'] ?? '60') ?>"
                                                            data-meeting-link="<?= htmlspecialchars($interview['meeting_link'] ?? '') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteInterviewModal" 
                                                            data-interview-id="<?= $interview['id'] ?>"
                                                            data-applicant-name="<?= htmlspecialchars($interview['full_name']) ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Interview Modal -->
<div class="modal fade" id="createInterviewModal" tabindex="-1" aria-labelledby="createInterviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="manage_interviews.php">
                <?= csrf_token_field() ?>
                <input type="hidden" name="create_interview" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createInterviewModalLabel">Schedule Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="application_id" class="form-label">Select Application *</label>
                        <select class="form-select" id="application_id" name="application_id" required>
                            <option value="">Choose an application</option>
                            <?php if (empty($applications)): ?>
                                <option value="" disabled>No applications available for interview</option>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <option value="<?= $app['id'] ?>">
                                        #<?= htmlspecialchars($app['application_number']) ?> - 
                                        <?= htmlspecialchars($app['full_name'] ?? ($app['first_name'] . ' ' . $app['last_name'])) ?> 
                                        (<?= htmlspecialchars($app['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            <?php if (empty($applications)): ?>
                                <span class="text-warning">No applications are currently available for scheduling interviews. Applications must be in 'shortlisted' or 'under_review' status.</span>
                            <?php else: ?>
                                Showing <?= count($applications) ?> available applications
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Preferred Positions Display -->
                    <div class="mb-3" id="preferred_positions_display" style="display: none;">
                        <label class="form-label">Preferred Positions:</label>
                        <div id="positions_list" class="alert alert-info mb-0"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="interview_type_id" class="form-label">Interview Category</label>
                        <select class="form-select" id="interview_type_id" name="interview_type_id">
                            <option value="">Choose interview category</option>
                            <?php foreach ($interview_types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="interview_type" class="form-label">Interview Type *</label>
                        <select class="form-select" id="interview_type" name="interview_type" required>
                            <option value="in_person">In Person</option>
                            <option value="phone">Phone</option>
                            <option value="video">Video</option>
                            <option value="panel">Panel</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="interview_code" class="form-label">Interview Code</label>
                        <input type="text" class="form-control" id="interview_code" name="interview_code" placeholder="INT-001">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" value="60" min="15" max="240">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="primary_interviewer_id" class="form-label">Primary Interviewer *</label>
                            <select class="form-select" id="primary_interviewer_id" name="primary_interviewer_id" required>
                                <option value="">Select primary interviewer</option>
                                <?php foreach ($interviewers as $interviewer): ?>
                                    <option value="<?= $interviewer['id'] ?>"><?= htmlspecialchars($interviewer['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="meeting_link" class="form-label">Meeting Link</label>
                        <input type="url" class="form-control" id="meeting_link" name="meeting_link" placeholder="https://meet.google.com/xxx-xxxx-xxx">
                        <div class="form-text">For virtual interviews</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="scheduled_date" class="form-label">Interview Date *</label>
                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="start_time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="end_time" class="form-label">End Time *</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    
                    
                    <div class="mb-3">
                        <label for="interviewer_ids" class="form-label">Panel Members (JSON format)</label>
                        <textarea class="form-control" id="interviewer_ids" name="interviewer_ids" rows="3" placeholder='[{"user_id": 1, "role": "member"}, {"user_id": 2, "role": "chairperson"}]'></textarea>
                        <div class="form-text">Enter panel members as JSON array with user_id and role.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Interview Modal -->
<div class="modal fade" id="editInterviewModal" tabindex="-1" aria-labelledby="editInterviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="manage_interviews.php">
                <?= csrf_token_field() ?>
                <input type="hidden" name="update_interview" value="1">
                <input type="hidden" name="interview_id" id="edit_interview_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editInterviewModalLabel">Edit Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label for="edit_interview_type_id" class="form-label">Interview Category</label>
                        <select class="form-select" id="edit_interview_type_id" name="interview_type_id">
                            <option value="">Choose interview category</option>
                            <?php foreach ($interview_types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_interview_type" class="form-label">Interview Type *</label>
                        <select class="form-select" id="edit_interview_type" name="interview_type" required>
                            <option value="in_person">In Person</option>
                            <option value="phone">Phone</option>
                            <option value="video">Video</option>
                            <option value="panel">Panel</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_interview_code" class="form-label">Interview Code</label>
                        <input type="text" class="form-control" id="edit_interview_code" name="interview_code" placeholder="INT-001">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_duration_minutes" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration_minutes" name="duration_minutes" value="60" min="15" max="240">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_meeting_link" class="form-label">Meeting Link</label>
                        <input type="url" class="form-control" id="edit_meeting_link" name="meeting_link" placeholder="https://meet.google.com/xxx-xxxx-xxx">
                        <div class="form-text">For virtual interviews</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_scheduled_date" class="form-label">Interview Date *</label>
                            <input type="date" class="form-control" id="edit_scheduled_date" name="scheduled_date" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="edit_start_time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="edit_end_time" class="form-label">End Time *</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    
                    
                    <div class="mb-3">
                        <label for="edit_interviewer_ids" class="form-label">Panel Members (JSON format)</label>
                        <textarea class="form-control" id="edit_interviewer_ids" name="interviewer_ids" rows="3" placeholder='[{"user_id": 1, "role": "member"}, {"user_id": 2, "role": "chairperson"}]'></textarea>
                        <div class="form-text">Enter panel members as JSON array with user_id and role.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="edit_location" name="location">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_feedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="edit_feedback" name="feedback" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Interview Modal -->
<div class="modal fade" id="deleteInterviewModal" tabindex="-1" aria-labelledby="deleteInterviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_interviews.php">
                <?= csrf_token_field() ?>
                <input type="hidden" name="delete_interview" value="1">
                <input type="hidden" name="interview_id" id="delete_interview_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteInterviewModalLabel">Delete Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the interview for "<strong id="delete_applicant_name"></strong>" (Type: <strong id="delete_interview_type_id"></strong>)?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    // Edit Interview Modal
    document.getElementById('editInterviewModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var interviewId = button.getAttribute('data-interview-id');
        var applicationId = button.getAttribute('data-application-id');
        var interviewTypeId = button.getAttribute('data-interview-type-id');
        var interviewType = button.getAttribute('data-interview-type');
        var interviewDate = button.getAttribute('data-interview-date');
        var startTime = button.getAttribute('data-start-time');
        var panelMembers = button.getAttribute('data-panel-members') || '[]';
        var location = button.getAttribute('data-location');
        var feedback = button.getAttribute('data-feedback');
        var interviewCode = button.getAttribute('data-interview-code');
        var durationMinutes = button.getAttribute('data-duration-minutes');
        var meetingLink = button.getAttribute('data-meeting-link');
        
        var modal = this;
        modal.querySelector('#edit_interview_id').value = interviewId;
        modal.querySelector('#edit_interview_type_id').value = interviewTypeId || '';
        modal.querySelector('#edit_interview_type').value = interviewType || 'in_person';
        modal.querySelector('#edit_scheduled_date').value = interviewDate;
        modal.querySelector('#edit_start_time').value = startTime;
        modal.querySelector('#edit_interviewer_ids').value = panelMembers;
        modal.querySelector('#edit_location').value = location;
        modal.querySelector('#edit_feedback').value = feedback;
        modal.querySelector('#edit_interview_code').value = interviewCode;
        modal.querySelector('#edit_duration_minutes').value = durationMinutes || '60';
        modal.querySelector('#edit_meeting_link').value = meetingLink;
    });
    
    // Delete Interview Modal
    document.getElementById('deleteInterviewModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var interviewId = button.getAttribute('data-interview-id');
        var interviewType = button.getAttribute('data-interview-type');
        var applicantName = button.getAttribute('data-applicant-name');
        
        var modal = this;
        modal.querySelector('#delete_interview_id').value = interviewId;
        modal.querySelector('#delete_interview_type_id').textContent = interviewType;
        modal.querySelector('#delete_applicant_name').textContent = applicantName;
    });
    
</script>
