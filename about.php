<?php
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>About OSTA Job Portal</h1>
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Welcome to OSTA Job Portal</h3>
                        <p class="card-text">
                            The Oromia Science and Technology Authority (OSTA) Job Portal is a dedicated platform 
                            designed to connect job seekers with employment opportunities within OSTA and its 
                            affiliated departments. Our mission is to streamline the recruitment process and 
                            provide a transparent, efficient, and user-friendly experience for both applicants 
                            and employers.
                        </p>
                        
                        <h4>Our Features</h4>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="bi bi-check-lg"></i> Browse and apply for jobs
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-lg"></i> Upload and manage your resume
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-lg"></i> Track application status
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-lg"></i> Receive job notifications
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-lg"></i> Search and filter jobs
                            </li>
                        </ul>

                        <h4>For Employers</h4>
                        <p>
                            As a department within OSTA, you can:
                            <ul>
                                <li>Post job openings</li>
                                <li>Manage applications</li>
                                <li>Communicate with applicants</li>
                                <li>Track recruitment metrics</li>
                            </ul>
                        </p>

                        <h4>For Applicants</h4>
                        <p>
                            As a job seeker, you can:
                            <ul>
                                <li>Browse current job openings</li>
                                <li>Apply for positions online</li>
                                <li>Track your application status</li>
                                <li>Manage your profile</li>
                            </ul>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
