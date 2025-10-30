<?php
/**
 * Application Functions for OSTA Job Portal
 * Contains functions for centralized application management, eligibility checks, and notifications
 */

/**
 * Update application status and send notification
 * 
 * @param int $application_id The ID of the application
 * @param string $new_status The new status to set
 * @param string $notes Optional notes about the status change
 * @param int $updated_by The ID of the user making the change (defaults to current user)
 * @return bool True on success, false on failure
 */
function update_application_status($application_id, $new_status, $notes = '', $updated_by = null) {
    global $pdo;
    
    if ($updated_by === null) {
        $updated_by = $_SESSION['user_id'] ?? 0;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get current status and user ID
        $stmt = $pdo->prepare("SELECT status, user_id FROM centralized_applications WHERE id = ?");
        $stmt->execute([$application_id]);
        $app = $stmt->fetch();
        
        if (!$app) {
            throw new Exception("Application not found");
        }
        
        $old_status = $app['status'];
        $applicant_id = $app['user_id'];
        
        // Update application status
        $update = $pdo->prepare("UPDATE centralized_applications SET status = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$new_status, $application_id]);
        
        // Log status change
        $log = $pdo->prepare("INSERT INTO application_status_history 
                            (application_id, old_status, new_status, changed_by, notes, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
        $log->execute([$application_id, $old_status, $new_status, $updated_by, $notes]);
        
        // Send notification to applicant
        $status_labels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'shortlisted' => 'Shortlisted',
            'interview_scheduled' => 'Interview Scheduled',
            'interviewed' => 'Interview Completed',
            'offered' => 'Job Offered',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn'
        ];
        
        $title = 'Application Status Updated';
        $old_status_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
        $message = "Your application status has been updated from \"$old_status_label\" to \"$new_status_label\"";
        
        if (!empty($notes)) {
            $message .= ".\n\nNotes: " . $notes;
        }
        
        // Get updater's name for the notification
        $updater = 'System';
        if ($updated_by > 0) {
            $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $user_stmt->execute([$updated_by]);
            $user = $user_stmt->fetch();
            if ($user) {
                $updater = $user['username'];
            }
        }
        
        $message .= "\n\nUpdated by: " . $updater;
        
        // Create notification
        $notif = $pdo->prepare("INSERT INTO notifications 
                              (title, message, type, target, target_id, created_by, status, created_at) 
                              VALUES (?, ?, 'info', 'user', ?, ?, 'unread', NOW())");
        $notif->execute([
            $title,
            $message,
            $applicant_id,
            $updated_by
        ]);
        
        // Log the action
        log_audit_action(
            $updated_by,
            'application_status_update',
            'application',
            $application_id,
            "Updated application status from $old_status to $new_status",
            ['old_status' => $old_status],
            ['new_status' => $new_status, 'notes' => $notes]
        );
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating application status: " . $e->getMessage());
        return false;
    }
}

/**
 * Run eligibility checks for an application
 */
function run_eligibility_checks($application_id) {
    global $pdo;
    
    try {
        // Get application details
        $app_stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE id = ?");
        $app_stmt->execute([$application_id]);
        $application = $app_stmt->fetch();
        
        if (!$application) {
            throw new Exception("Application not found");
        }
        
        // Get eligibility criteria (general and department-specific)
        $preferred_departments = json_decode($application['preferred_departments'], true);
        $criteria_sql = "SELECT * FROM eligibility_criteria WHERE is_active = 1 AND 
                        (department_id IS NULL OR department_id IN (" . 
                        implode(',', array_fill(0, count($preferred_departments), '?')) . "))";
        
        $criteria_stmt = $pdo->prepare($criteria_sql);
        $criteria_stmt->execute($preferred_departments);
        $criteria_list = $criteria_stmt->fetchAll();
        
        $overall_eligible = true;
        $total_score = 0;
        $max_score = 0;
        
        foreach ($criteria_list as $criteria) {
            $check_result = evaluate_criteria($application, $criteria);
            $max_score += $criteria['weight'];
            
            // Insert or update eligibility check result
            $check_stmt = $pdo->prepare("INSERT INTO application_eligibility_checks 
                                       (application_id, criteria_id, check_result, actual_value, score, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE 
                                       check_result = VALUES(check_result), 
                                       actual_value = VALUES(actual_value),
                                       score = VALUES(score),
                                       notes = VALUES(notes),
                                       checked_at = NOW()");
            
            $check_stmt->execute([
                $application_id,
                $criteria['id'],
                $check_result['result'],
                $check_result['actual_value'],
                $check_result['score'],
                $check_result['notes']
            ]);
            
            $total_score += $check_result['score'];
            
            // If mandatory criteria fails, mark as not eligible
            if ($criteria['is_mandatory'] && $check_result['result'] === 'fail') {
                $overall_eligible = false;
            }
        }
        
        // Update application eligibility status
        $eligibility_status = $overall_eligible ? 'eligible' : 'not_eligible';
        $eligibility_notes = "Eligibility Score: {$total_score}/{$max_score}";
        
        $update_stmt = $pdo->prepare("UPDATE centralized_applications 
                                    SET eligibility_status = ?, eligibility_notes = ? 
                                    WHERE id = ?");
        $update_stmt->execute([$eligibility_status, $eligibility_notes, $application_id]);
        
        return [
            'eligible' => $overall_eligible,
            'score' => $total_score,
            'max_score' => $max_score,
            'percentage' => $max_score > 0 ? round(($total_score / $max_score) * 100, 2) : 0
        ];
        
    } catch (Exception $e) {
        log_debug("Eligibility check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Evaluate a single criteria against application data
 */
function evaluate_criteria($application, $criteria) {
    $result = [
        'result' => 'pending',
        'actual_value' => '',
        'score' => 0,
        'notes' => ''
    ];
    
    try {
        switch ($criteria['criteria_type']) {
            case 'education_level':
                $education_levels = ['high_school' => 1, 'diploma' => 2, 'bachelor' => 3, 'master' => 4, 'phd' => 5];
                $required_level = json_decode($criteria['required_value'], true);
                $app_level = $education_levels[$application['education_level']] ?? 0;
                $req_level = $education_levels[$required_level] ?? 0;
                
                $result['actual_value'] = $application['education_level'];
                
                if ($criteria['operator'] === 'greater_equal' && $app_level >= $req_level) {
                    $result['result'] = 'pass';
                    $result['score'] = $criteria['weight'];
                } else {
                    $result['result'] = 'fail';
                    $result['notes'] = "Required: {$required_level}, Actual: {$application['education_level']}";
                }
                break;
                
            case 'years_experience':
                $required_years = (int)json_decode($criteria['required_value'], true);
                $actual_years = (int)$application['years_of_experience'];
                
                $result['actual_value'] = $actual_years;
                
                switch ($criteria['operator']) {
                    case 'greater_equal':
                        if ($actual_years >= $required_years) {
                            $result['result'] = 'pass';
                            $result['score'] = $criteria['weight'];
                        } else {
                            $result['result'] = 'fail';
                            $result['notes'] = "Required: {$required_years}+ years, Actual: {$actual_years} years";
                        }
                        break;
                }
                break;
                
            case 'age_range':
                if (empty($application['date_of_birth'])) {
                    $result['result'] = 'fail';
                    $result['actual_value'] = 0;
                    $result['notes'] = "Date of birth not provided";
                    break;
                }
                
                try {
                    $birth_date = new DateTime($application['date_of_birth']);
                    $today = new DateTime();
                    $age = $today->diff($birth_date)->y;
                } catch (Exception $e) {
                    $result['result'] = 'fail';
                    $result['actual_value'] = 0;
                    $result['notes'] = "Invalid date of birth format";
                    break;
                }
                
                $required_age = (int)json_decode($criteria['required_value'], true);
                $result['actual_value'] = $age;
                
                switch ($criteria['operator']) {
                    case 'greater_equal':
                        if ($age >= $required_age) {
                            $result['result'] = 'pass';
                            $result['score'] = $criteria['weight'];
                        } else {
                            $result['result'] = 'fail';
                            $result['notes'] = "Minimum age: {$required_age}, Actual age: {$age}";
                        }
                        break;
                    case 'less_equal':
                        if ($age <= $required_age) {
                            $result['result'] = 'pass';
                            $result['score'] = $criteria['weight'];
                        } else {
                            $result['result'] = 'fail';
                            $result['notes'] = "Maximum age: {$required_age}, Actual age: {$age}";
                        }
                        break;
                }
                break;
                
            case 'field_of_study':
                $required_fields = json_decode($criteria['required_value'], true);
                $app_field = strtolower($application['field_of_study']);
                
                $result['actual_value'] = $application['field_of_study'];
                
                if ($criteria['operator'] === 'contains') {
                    $match_found = false;
                    foreach ($required_fields as $field) {
                        if (strpos($app_field, strtolower($field)) !== false) {
                            $match_found = true;
                            break;
                        }
                    }
                    
                    if ($match_found) {
                        $result['result'] = 'pass';
                        $result['score'] = $criteria['weight'];
                    } else {
                        $result['result'] = 'fail';
                        $result['notes'] = "Required field not found in: " . implode(', ', $required_fields);
                    }
                }
                break;
                
            default:
                $result['result'] = 'pending';
                $result['notes'] = 'Criteria type not implemented yet';
                break;
        }
        
    } catch (Exception $e) {
        $result['result'] = 'fail';
        $result['notes'] = 'Error evaluating criteria: ' . $e->getMessage();
        log_debug("Criteria evaluation error: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Queue a notification for sending
 */
function queue_notification($user_id, $template_code, $variables = []) {
    global $pdo;
    
    try {
        // Get user details
        $user_stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Get notification template
        $template_stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE template_code = ? AND is_active = 1");
        $template_stmt->execute([$template_code]);
        $template = $template_stmt->fetch();
        
        if (!$template) {
            throw new Exception("Notification template not found: " . $template_code);
        }
        
        // Replace variables in template
        $subject = $template['subject'];
        $message = $template['message_template'];
        
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }
        
        // Queue notification
        $queue_stmt = $pdo->prepare("INSERT INTO notification_queue 
                                   (recipient_id, notification_type, recipient_email, subject, message, reference_type) 
                                   VALUES (?, ?, ?, ?, ?, 'application')");
        
        $queue_stmt->execute([
            $user_id,
            $template['notification_type'],
            $user['email'],
            $subject,
            $message
        ]);
        
        return true;
        
    } catch (Exception $e) {
        log_debug("Notification queue error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get status color for badges
 */
function get_status_color($status) {
    $colors = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'under_review' => 'info',
        'shortlisted' => 'success',
        'interview_scheduled' => 'warning',
        'rejected' => 'danger',
        'accepted' => 'success',
        'onboarding' => 'success'
    ];
    
    return isset($colors[$status]) ? $colors[$status] : 'secondary';
}

/**
 * Log audit action
 */
function log_audit_action($user_id, $action_type, $resource_type, $resource_id, $description, $old_values = null, $new_values = null) {
    global $pdo;
    
    try {
        // Check if enhanced_audit_log table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'enhanced_audit_log'");
        if (!$stmt->fetch()) {
            // Table doesn't exist, skip audit logging
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO enhanced_audit_log 
                             (user_id, session_id, ip_address, user_agent, action_type, resource_type, resource_id, 
                              action_description, old_values, new_values, module, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'applicant', NOW())");
        
        $stmt->execute([
            $user_id,
            session_id(),
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
            $action_type,
            $resource_type,
            $resource_id,
            $description,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null
        ]);
    } catch (Exception $e) {
        // Silently fail audit logging to not break main functionality
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Calculate application completion percentage
 */
function calculate_application_completion($application) {
    $required_fields = [
        'first_name', 'last_name', 'email', 'phone', 'national_id',
        'date_of_birth', 'gender', 'address', 'city', 'region',
        'education_level', 'field_of_study', 'institution', 'graduation_year',
        'preferred_departments'
    ];
    
    $completed_fields = 0;
    $total_fields = count($required_fields);
    
    foreach ($required_fields as $field) {
        if ($field === 'preferred_departments') {
            $departments = json_decode(isset($application[$field]) ? $application[$field] : '[]', true);
            if (!empty($departments)) {
                $completed_fields++;
            }
        } else {
            if (!empty($application[$field])) {
                $completed_fields++;
            }
        }
    }
    
    return round(($completed_fields / $total_fields) * 100, 1);
}

/**
 * Get application statistics for dashboard
 */
function get_application_statistics($user_id = null) {
    global $pdo;
    
    try {
        $where_clause = $user_id ? "WHERE user_id = ?" : "";
        $params = $user_id ? [$user_id] : [];
        
        $stmt = $pdo->prepare("SELECT 
                              status,
                              COUNT(*) as count
                              FROM centralized_applications 
                              {$where_clause}
                              GROUP BY status");
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        $stats = [
            'draft' => 0,
            'submitted' => 0,
            'under_review' => 0,
            'shortlisted' => 0,
            'interview_scheduled' => 0,
            'rejected' => 0,
            'accepted' => 0,
            'onboarding' => 0
        ];
        
        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
        }
        
        return $stats;
        
    } catch (Exception $e) {
        log_debug("Application statistics error: " . $e->getMessage());
        return [];
    }
}

/**
 * Send pending notifications (to be called by cron job)
 */
function process_notification_queue($limit = 50) {
    global $pdo;
    
    try {
        // Get pending notifications
        $stmt = $pdo->prepare("SELECT * FROM notification_queue 
                             WHERE status = 'pending' AND scheduled_at <= NOW() 
                             ORDER BY created_at ASC LIMIT ?");
        $stmt->execute([$limit]);
        $notifications = $stmt->fetchAll();
        
        foreach ($notifications as $notification) {
            $success = false;
            
            switch ($notification['notification_type']) {
                case 'email':
                    $success = send_email_notification($notification);
                    break;
                case 'sms':
                    $success = send_sms_notification($notification);
                    break;
                case 'system':
                    $success = create_system_notification($notification);
                    break;
            }
            
            // Update notification status
            $status = $success ? 'sent' : 'failed';
            $sent_at = $success ? 'NOW()' : 'NULL';
            
            $update_stmt = $pdo->prepare("UPDATE notification_queue 
                                        SET status = ?, attempts = attempts + 1, sent_at = {$sent_at}
                                        WHERE id = ?");
            $update_stmt->execute([$status, $notification['id']]);
        }
        
        return count($notifications);
        
    } catch (Exception $e) {
        log_debug("Notification processing error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send email notification
 */
function send_email_notification($notification) {
    // This would integrate with your email service (PHPMailer, etc.)
    // For now, we'll just log it
    log_info("Email notification sent to: " . $notification['recipient_email'] . 
             " Subject: " . $notification['subject']);
    return true;
}

/**
 * Send SMS notification
 */
function send_sms_notification($notification) {
    // This would integrate with your SMS service
    // For now, we'll just log it
    log_info("SMS notification sent to: " . $notification['recipient_phone'] . 
             " Message: " . substr($notification['message'], 0, 50) . "...");
    return true;
}

/**
 * Create system notification
 */
function create_system_notification($notification) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications 
                             (title, message, type, user_id, created_by, created_at) 
                             VALUES (?, ?, 'info', ?, 1, NOW())");
        
        $stmt->execute([
            $notification['subject'],
            $notification['message'],
            $notification['recipient_id']
        ]);
        
        return true;
        
    } catch (Exception $e) {
        log_debug("System notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate a unique interview code
 */
function generate_interview_code() {
    global $pdo;
    
    try {
        // Generate a unique code
        do {
            $code = 'INT-' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            // Check if code already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM interviews WHERE interview_code = ?");
            $stmt->execute([$code]);
            $count = $stmt->fetchColumn();
        } while ($count > 0);
        
        return $code;
        
    } catch (Exception $e) {
        log_debug("Interview code generation error: " . $e->getMessage());
        // Fallback to timestamp-based code
        return 'INT-' . date('Ymd') . '-' . strtoupper(substr(md5(time()), 0, 4));
    }
}

?>
