<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $education = sanitize($_POST['education']);
    $experience = sanitize($_POST['experience']);
    
    // Validate inputs
    $errors = [];
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $errors[] = "All required fields are mandatory";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Phone number validation
    if (!preg_match("/^\+?\d{10,15}$/", $phone)) {
        $errors[] = "Invalid phone number format";
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "This email is already registered";
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "This username is already taken";
    }
    
    // Handle file uploads
    $resume = null;
    $certificates = [];
    
    if (isset($_FILES['resume'])) {
        $allowed_types = ['pdf', 'doc', 'docx'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['resume']['name']);
            $extension = strtolower($file_info['extension']);
            
            if (!in_array($extension, $allowed_types)) {
                $errors[] = "Invalid resume file type. Only PDF, DOC, and DOCX are allowed";
            } elseif ($_FILES['resume']['size'] > $max_size) {
                $errors[] = "Resume file size must be less than 5MB";
            } else {
                $resume_filename = uniqid() . '.' . $extension;
                $full_path = "../uploads/resumes/{$resume_filename}";
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $full_path)) {
                    // Store only filename in database
                    $resume = $resume_filename;
                } else {
                    $errors[] = "Failed to upload resume. Please try again.";
                    $resume = null;
                }
            }
        }
    }
    
    if (isset($_FILES['certificates'])) {
        foreach ($_FILES['certificates']['name'] as $key => $name) {
            if ($_FILES['certificates']['error'][$key] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($name);
                $extension = strtolower($file_info['extension']);
                
                if (!in_array($extension, $allowed_types)) {
                    $errors[] = "Invalid certificate file type. Only PDF, DOC, and DOCX are allowed";
                } elseif ($_FILES['certificates']['size'][$key] > $max_size) {
                    $errors[] = "Certificate file size must be less than 5MB";
                } else {
                    $cert_name = uniqid() . '.' . $extension;
                    move_uploaded_file($_FILES['certificates']['tmp_name'][$key], "../uploads/certificates/{$cert_name}");
                    $certificates[] = $cert_name;
                }
            }
        }
    }
    
    if (empty($errors)) {
        // Hash password
        $hashed_password = hash_password($password);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, first_name, last_name, phone, address, education, experience) 
                             VALUES (?, ?, ?, 'applicant', 'active', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone, $address, $education, $experience]);
        
        $user_id = $pdo->lastInsertId();
        
        // Store resume and certificates
        if ($resume) {
            $stmt = $pdo->prepare("INSERT INTO applicant_documents (user_id, document_type, file_path) VALUES (?, 'resume', ?)");
            $stmt->execute([$user_id, $resume]);
        }
        
        foreach ($certificates as $cert) {
            $stmt = $pdo->prepare("INSERT INTO applicant_documents (user_id, document_type, file_path) VALUES (?, 'certificate', ?)");
            $stmt->execute([$user_id, $cert]);
        }
        
        // Log in the user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'applicant';
        $_SESSION['username'] = $username;
        
        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Applicant - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Register as Job Applicant</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="education" class="form-label">Education *</label>
                                <textarea class="form-control" id="education" name="education" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="experience" class="form-label">Work Experience *</label>
                                <textarea class="form-control" id="experience" name="experience" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="resume" class="form-label">Resume/CV *</label>
                                <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="certificates" class="form-label">Professional Certificates (Optional)</label>
                                <input type="file" class="form-control" id="certificates" name="certificates[]" accept=".pdf,.doc,.docx" multiple>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../login.php">Already have an account? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
