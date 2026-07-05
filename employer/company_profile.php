<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Company;

require_role('employer', '../login.php');

$userId = (int) $_SESSION['user_id'];
$companyModel = new Company();
$company = $companyModel->getByUser($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: company_profile.php');
        exit;
    }

    $data = [
        'name'        => sanitize($_POST['name'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'website'     => sanitize($_POST['website'] ?? ''),
        'industry'    => sanitize($_POST['industry'] ?? ''),
        'size'        => sanitize($_POST['size'] ?? '1-10'),
        'address'     => sanitize($_POST['address'] ?? ''),
        'city'        => sanitize($_POST['city'] ?? ''),
        'region'      => sanitize($_POST['region'] ?? ''),
        'phone'       => sanitize($_POST['phone'] ?? ''),
        'email'       => sanitize($_POST['email'] ?? ''),
    ];

    // Handle logo upload
    if (!empty($_FILES['logo']['tmp_name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
            $filename = 'company_' . $userId . '_' . time() . '.' . $ext;
            $target = dirname(__DIR__) . '/uploads/logos/' . $filename;
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                $data['logo'] = 'logos/' . $filename;
            }
        }
    }

    if ($company) {
        $companyModel->update($company['id'], $data);
    } else {
        $data['user_id'] = $userId;
        $companyModel->create($data);
    }

    $_SESSION['success_message'] = 'Company profile updated.';
    header('Location: company_profile.php');
    exit;
}

$pageTitle = 'Company Profile';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-building me-2" style="color: var(--osta-green);"></i>Company Profile</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <?php if ($company && !$company['is_verified']): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Your company profile is pending verification by an admin.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Company Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Website</label>
                                <input type="url" name="website" class="form-control" placeholder="https://" value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Industry</label>
                                <input type="text" name="industry" class="form-control" value="<?php echo htmlspecialchars($company['industry'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company Size</label>
                                <select name="size" class="form-select">
                                    <?php foreach (['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'] as $size): ?>
                                    <option value="<?php echo $size; ?>" <?php echo ($company['size'] ?? '') === $size ? 'selected' : ''; ?>><?php echo $size; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <?php if (!empty($company['logo'])): ?>
                                <img src="<?php echo htmlspecialchars('../uploads/' . $company['logo']); ?>" alt="Logo" class="rounded mb-2" style="max-height: 120px;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 120px; height: 120px;">
                                    <i class="fas fa-building fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <label class="form-label small text-muted">Company Logo</label>
                            <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($company['city'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Region</label>
                            <input type="text" name="region" class="form-control" value="<?php echo htmlspecialchars($company['region'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn px-4" style="background: var(--osta-green); color: white;">
                    <i class="fas fa-save me-1"></i>Save Profile
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
