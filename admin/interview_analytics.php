<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

// Get interview statistics
try {
    // Total interviews
    $total_interviews = $pdo->query("SELECT COUNT(*) as count FROM interviews")->fetch()['count'];
    
    // Interviews by status
    $status_stats = $pdo->query("SELECT status, COUNT(*) as count FROM interviews GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    
    // Interviews by type
    $type_stats = $pdo->query("SELECT interview_type, COUNT(*) as count FROM interviews GROUP BY interview_type")->fetchAll(PDO::FETCH_ASSOC);
    
    // Interviews by month (last 12 months)
    $monthly_stats = $pdo->query("SELECT 
        DATE_FORMAT(scheduled_date, '%Y-%m') as month, 
        COUNT(*) as count 
        FROM interviews 
        WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
        GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m') 
        ORDER BY month")->fetchAll(PDO::FETCH_ASSOC);
    
    // Average duration of interviews
    $avg_duration = $pdo->query("SELECT AVG(duration_minutes) as avg_duration FROM interviews WHERE duration_minutes IS NOT NULL")->fetch()['avg_duration'];
    
    // Interviews with feedback
    $feedback_stats = $pdo->query("SELECT 
        COUNT(*) as total, 
        COUNT(CASE WHEN feedback IS NOT NULL AND feedback != '' THEN 1 END) as with_feedback 
        FROM interviews")->fetch();
    
    // Top interviewers by number of interviews conducted
    $top_interviewers = $pdo->query("SELECT 
        u.first_name, u.last_name, 
        COUNT(i.id) as interview_count 
        FROM users u 
        JOIN interviews i ON u.id = i.primary_interviewer_id 
        GROUP BY u.id, u.first_name, u.last_name 
        ORDER BY interview_count DESC 
        LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Interview completion rate
    $completion_rate = $pdo->query("SELECT 
        COUNT(*) as total, 
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed 
        FROM interviews")->fetch();
    
    $completion_percentage = $completion_rate['total'] > 0 ? ($completion_rate['completed'] / $completion_rate['total']) * 100 : 0;
    
} catch (Exception $e) {
    error_log("Error fetching interview analytics: " . $e->getMessage());
    $total_interviews = 0;
    $status_stats = [];
    $type_stats = [];
    $monthly_stats = [];
    $avg_duration = 0;
    $feedback_stats = ['total' => 0, 'with_feedback' => 0];
    $top_interviewers = [];
    $completion_rate = ['total' => 0, 'completed' => 0];
    $completion_percentage = 0;
}

$page_title = "Interview Analytics - Admin";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-chart-bar me-2"></i>Interview Analytics</h2>
            </div>
            
            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Interviews</h5>
                            <h2 class="mb-0"><?php echo $total_interviews; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Avg. Duration</h5>
                            <h2 class="mb-0"><?php echo round($avg_duration ?? 0); ?> min</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">With Feedback</h5>
                            <h2 class="mb-0"><?php echo $feedback_stats['with_feedback'] ?? 0; ?>/<?php echo $feedback_stats['total'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Completion Rate</h5>
                            <h2 class="mb-0"><?php echo round($completion_percentage, 1); ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Interviews by Status -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Interviews by Status</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($status_stats)): ?>
                                <p class="text-muted">No data available</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $status_colors = [
                                                'scheduled' => 'bg-info',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'rescheduled' => 'bg-warning'
                                            ];
                                            foreach ($status_stats as $status): 
                                                $percentage = $total_interviews > 0 ? ($status['count'] / $total_interviews) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge <?php echo $status_colors[$status['status']] ?? 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($status['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $status['count']; ?></td>
                                                    <td><?php echo round($percentage, 1); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Interviews by Type -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Interviews by Type</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($type_stats)): ?>
                                <p class="text-muted">No data available</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($type_stats as $type): 
                                                $percentage = $total_interviews > 0 ? ($type['count'] / $total_interviews) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst(str_replace('_', ' ', $type['interview_type'])); ?></td>
                                                    <td><?php echo $type['count']; ?></td>
                                                    <td><?php echo round($percentage, 1); ?>%</td>
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
            
            <div class="row">
                <!-- Monthly Trend -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0">Interviews Trend (Last 12 Months)</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($monthly_stats)): ?>
                                <p class="text-muted">No data available</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Interviews</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthly_stats as $month): ?>
                                                <tr>
                                                    <td><?php echo date('F Y', strtotime($month['month'])); ?></td>
                                                    <td><?php echo $month['count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Top Interviewers -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h4 class="mb-0">Top Interviewers</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_interviewers)): ?>
                                <p class="text-muted">No data available</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Interviewer</th>
                                                <th>Interviews</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_interviewers as $interviewer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($interviewer['first_name'] . ' ' . $interviewer['last_name']); ?></td>
                                                    <td><?php echo $interviewer['interview_count']; ?></td>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    // Security hook to prevent back navigation
    prevent_back_navigation();
</script>
</body>
</html>
