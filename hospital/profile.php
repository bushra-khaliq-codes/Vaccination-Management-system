<?php
/*
 * Hospital - Facility Profile Management Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('hospital');

$userId = (int)current_user()['id'];
$hospital = db_fetch_one("SELECT * FROM hospitals WHERE user_id = ?", [$userId]);
if (!$hospital) {
    $hId = $_SESSION['user']['hospital_id'] ?? 1;
    $hospital = db_fetch_one("SELECT * FROM hospitals WHERE id = ?", [$hId]);
}
$hospitalId = (int)$hospital['id'];

// Process Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $regNumber = sanitize($_POST['reg_number'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $location = sanitize($_POST['location'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $operatingHours = sanitize($_POST['operating_hours'] ?? '08:00 AM - 05:00 PM');

            if (empty($name) || empty($city) || empty($phone)) {
                set_flash('error', "Hospital name, city, and phone are mandatory.");
            } else {
                db_execute("
                    UPDATE hospitals 
                    SET name = ?, reg_number = ?, city = ?, location = ?, phone = ?, email = ?, operating_hours = ?
                    WHERE id = ?
                ", [$name, $regNumber, $city, $location, $phone, $email, $operatingHours, $hospitalId]);

                set_flash('success', "Facility profile updated successfully.");
                header("Location: " . BASE_URL . "/hospital/profile.php");
                exit;
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            $userRow = db_fetch_one("SELECT password FROM users WHERE id = ?", [$userId]);

            if (!password_verify($currentPass, $userRow['password'])) {
                set_flash('error', "Current staff password is incorrect.");
            } elseif (strlen($newPass) < 6) {
                set_flash('error', "New password must be at least 6 characters.");
            } elseif ($newPass !== $confirmPass) {
                set_flash('error', "New passwords do not match.");
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                db_execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                set_flash('success', "Hospital login password successfully updated.");
                header("Location: " . BASE_URL . "/hospital/profile.php");
                exit;
            }
        }
    }
}

// Refresh hospital data
$hospital = db_fetch_one("SELECT * FROM hospitals WHERE id = ?", [$hospitalId]);

$pageTitle = "Facility Profile - " . htmlspecialchars($hospital['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Hospital Facility Profile</h1>
            <p class="text-muted small mb-0">Manage public listing information, address, operating hours, and staff credentials.</p>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Profile Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-building-circle-check text-primary"></i> Facility Information
                    </h5>
                    <form method="POST" action="<?= BASE_URL ?>/hospital/profile.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Hospital / Clinic Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($hospital['name']) ?>" class="form-control">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold small text-dark">Registration No.</label>
                                <input type="text" name="reg_number" value="<?= htmlspecialchars($hospital['reg_number'] ?? '') ?>" class="form-control">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold small text-dark">City / District <span class="text-danger">*</span></label>
                                <input type="text" name="city" required value="<?= htmlspecialchars($hospital['city']) ?>" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Physical Location / Address</label>
                            <textarea name="location" rows="2" class="form-control"><?= htmlspecialchars($hospital['location']) ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold small text-dark">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" required value="<?= htmlspecialchars($hospital['phone']) ?>" class="form-control">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold small text-dark">Official Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($hospital['email']) ?>" class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-dark">Operating Hours</label>
                            <input type="text" name="operating_hours" value="<?= htmlspecialchars($hospital['operating_hours'] ?? '08:00 AM - 05:00 PM') ?>" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Facility Details
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-lock text-primary"></i> Change Staff Password
                    </h5>
                    <form method="POST" action="<?= BASE_URL ?>/hospital/profile.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" required placeholder="••••••••" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters" class="form-control">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-dark">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" required minlength="6" placeholder="Repeat new password" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-outline-secondary w-100 py-2 fw-semibold">
                            <i class="fa-solid fa-key me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
