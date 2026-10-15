<?php
/*
 * Parent - Profile Management Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$userId = (int)current_user()['id'];

// Process Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $address = sanitize($_POST['address'] ?? '');

            if (empty($name) || empty($phone)) {
                set_flash('error', "Name and phone number are required.");
            } else {
                db_execute("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?", [$name, $phone, $address, $userId]);
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['phone'] = $phone;
                $_SESSION['user']['address'] = $address;
                set_flash('success', "Profile details successfully updated.");
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            $userRow = db_fetch_one("SELECT password FROM users WHERE id = ?", [$userId]);

            if (!password_verify($currentPass, $userRow['password'])) {
                set_flash('error', "Current password is incorrect.");
            } elseif (strlen($newPass) < 6) {
                set_flash('error', "New password must be at least 6 characters.");
            } elseif ($newPass !== $confirmPass) {
                set_flash('error', "New passwords do not match.");
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                db_execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                set_flash('success', "Password successfully updated.");
            }
        }
    }
    header("Location: " . BASE_URL . "/parent/profile.php");
    exit;
}

// Fetch fresh user data
$userData = db_fetch_one("SELECT * FROM users WHERE id = ?", [$userId]);

// Stats
$totalChildren = db_fetch_one("SELECT COUNT(*) as cnt FROM children WHERE parent_id = ?", [$userId])['cnt'] ?? 0;
$totalBookings = db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE parent_id = ?", [$userId])['cnt'] ?? 0;

$pageTitle = "My Profile - Parent Portal";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Guardian Account Profile</h1>
            <p class="text-muted small mb-0">Maintain your contact information for vaccination appointment confirmations and health reminders.</p>
        </div>
    </div>

    <!--2 Header -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-baby"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Registered Children</span>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalChildren ?> Profiles</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Total Bookings</span>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalBookings ?> Requests</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Account Status</span>
                        <h5 class="fw-bold text-success mb-0">Active & Verified</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Grid -->
    <div class="row g-4">
        
        <!-- Profile Details Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-regular fa-user text-primary"></i> Personal Information
                    </h5>
                    <form method="POST" action="<?= BASE_URL ?>/parent/profile.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($userData['name']) ?>" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Email Address (Read-only)</label>
                            <input type="email" disabled value="<?= htmlspecialchars($userData['email']) ?>" class="form-control bg-light text-muted">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Primary Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" required value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" class="form-control">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-dark">Residential Address</label>
                            <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security / Password Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-lock text-primary"></i> Change Password
                    </h5>
                    <form method="POST" action="<?= BASE_URL ?>/parent/profile.php">
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
