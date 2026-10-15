<?php
/*
 * Administrator - Hospital Management Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

// Delete Hospital
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if hospital has existing appointments or records
    $hasAppointments = db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE hospital_id = ?", [$id])['cnt'] ?? 0;
    if ($hasAppointments > 0) {
        // Soft delete / set inactive to protect foreign key relationships
        db_execute("UPDATE hospitals SET status = 'inactive' WHERE id = ?", [$id]);
        set_flash('warning', "Hospital has existing appointments. Status set to 'Inactive' instead of permanent deletion.");
    } else {
        db_execute("DELETE FROM hospitals WHERE id = ?", [$id]);
        set_flash('success', "Hospital removed successfully.");
    }
    header("Location: " . BASE_URL . "/admin/hospitals.php");
    exit;
}

// Handle Form Submissions (Create / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
        header("Location: " . BASE_URL . "/admin/hospitals.php");
        exit;
    }

    $name = sanitize($_POST['name'] ?? '');
    $regNumber = sanitize($_POST['reg_number'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    $hours = sanitize($_POST['operating_hours'] ?? '08:00 AM - 05:00 PM');

    if (empty($name) || empty($city) || empty($phone)) {
        set_flash('error', "Hospital name, city, and phone number are required.");
    } else {
        if (!empty($_POST['hospital_id'])) {
            // Update Existing Hospital
            $hospId = (int)$_POST['hospital_id'];
            db_execute("
                UPDATE hospitals 
                SET name = ?, reg_number = ?, city = ?, location = ?, phone = ?, email = ?, status = ?, operating_hours = ?
                WHERE id = ?
            ", [$name, $regNumber, $city, $location, $phone, $email, $status, $hours, $hospId]);

            // Update linked user email
            $hosp = db_fetch_one("SELECT user_id FROM hospitals WHERE id = ?", [$hospId]);
            if ($hosp && $hosp['user_id']) {
                db_execute("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?", [$name, $email, $phone, $hosp['user_id']]);
            }

            set_flash('success', "Hospital details for '{$name}' updated successfully.");
        } else {
            // Add New Hospital
            $defaultPassword = $_POST['password'] ?? 'hospital123';
            $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            // Check if user email exists
            $existing = db_fetch_one("SELECT id FROM users WHERE LOWER(email) = ?", [strtolower($email)]);
            if ($existing) {
                $userId = $existing['id'];
            } else {
                db_execute("
                    INSERT INTO users (name, email, password, role, phone, address)
                    VALUES (?, ?, ?, 'hospital', ?, ?)
                ", [$name, $email, $hash, $phone, $location]);
                $userId = db_last_id();
            }

            db_execute("
                INSERT INTO hospitals (user_id, name, reg_number, city, location, phone, email, status, operating_hours)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$userId, $name, $regNumber, $city, $location, $phone, $email, $status, $hours]);

            set_flash('success', "New Hospital '{$name}' registered. Staff login email: {$email}");
        }
        header("Location: " . BASE_URL . "/admin/hospitals.php");
        exit;
    }
}

// Fetch hospital for edit 
$editHosp = null;
if ($editId) {
    $editHosp = db_fetch_one("SELECT * FROM hospitals WHERE id = ?", [$editId]);
}

// Fetch all hospitals
$hospitals = db_fetch_all("
    SELECT h.*, 
           (SELECT COUNT(*) FROM appointments a WHERE a.hospital_id = h.id) as total_appointments,
           (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.hospital_id = h.id AND vr.status = 'Vaccinated') as total_vaccinated
    FROM hospitals h 
    ORDER BY h.name ASC
");

$pageTitle = "Manage Hospitals - Administrator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Hospital Network (<?= count($hospitals) ?> Facilities)</h1>
        <p class="page-subtitle">Add, update, activate, or decommission immunization partner hospitals and pediatric clinics.</p>
    </div>
    <button type="button" onclick="document.getElementById('hospitalFormCard').classList.toggle('d-none')" class="btn btn-purple">
        <i class="fa-solid fa-plus me-1"></i> <?= ($editHosp || $action === 'add') ? 'Close Form' : 'Add New Hospital' ?>
    </button>
</div>

<!-- Hospital Add/Edit Form Card -->
<div id="hospitalFormCard" class="form-card <?= ($editHosp || $action === 'add') ? '' : 'd-none' ?> border-purple">
    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-hospital text-purple me-2"></i>
            <?= $editHosp ? 'Update Hospital: ' . htmlspecialchars($editHosp['name']) : 'Register New Partner Healthcare Facility' ?>
        </h5>
        <button type="button" onclick="document.getElementById('hospitalFormCard').classList.add('d-none')" class="btn-close" aria-label="Close"></button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/hospitals.php" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <?php if ($editHosp): ?>
            <input type="hidden" name="hospital_id" value="<?= $editHosp['id'] ?>">
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="form-label-custom">Hospital / Clinic Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editHosp['name'] ?? '') ?>" placeholder="e.g. Hope Children Hospital" class="form-control">
            </div>
            <div class="col-md-5">
                <label class="form-label-custom">Registration / License Code</label>
                <input type="text" name="reg_number" value="<?= htmlspecialchars($editHosp['reg_number'] ?? '') ?>" placeholder="e.g. HOSP-2026-90" class="form-control">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label-custom">City / Municipality <span class="text-danger">*</span></label>
                <input type="text" name="city" required value="<?= htmlspecialchars($editHosp['city'] ?? '') ?>" placeholder="e.g. Springfield" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Contact Phone <span class="text-danger">*</span></label>
                <input type="tel" name="phone" required value="<?= htmlspecialchars($editHosp['phone'] ?? '') ?>" placeholder="+1 555-0100" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Operating Hours</label>
                <input type="text" name="operating_hours" value="<?= htmlspecialchars($editHosp['operating_hours'] ?? '08:00 AM - 05:00 PM') ?>" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label-custom">Physical Location / Address <span class="text-danger">*</span></label>
            <input type="text" name="location" required value="<?= htmlspecialchars($editHosp['location'] ?? '') ?>" placeholder="Building, Street, Landmark, District" class="form-control">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-<?= $editHosp ? '6' : '4' ?>">
                <label class="form-label-custom">Official Hospital Staff Email <span class="text-danger">*</span></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editHosp['email'] ?? '') ?>" placeholder="pediatrics@hospital.org" class="form-control">
            </div>
            <?php if (!$editHosp): ?>
                <div class="col-md-4">
                    <label class="form-label-custom">Initial Staff Password</label>
                    <input type="text" name="password" value="hospital123" class="form-control">
                </div>
            <?php endif; ?>
            <div class="col-md-<?= $editHosp ? '6' : '4' ?>">
                <label class="form-label-custom">Facility Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($editHosp && $editHosp['status'] === 'active') ? 'selected' : '' ?>>Active (Accepts Bookings)</option>
                    <option value="inactive" <?= ($editHosp && $editHosp['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Suspended)</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/admin/hospitals.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-purple">
                <i class="fa-solid fa-save me-1"></i> <?= $editHosp ? 'Update Hospital Record' : 'Save & Register Facility' ?>
            </button>
        </div>
    </form>
</div>

<!-- Hospitals Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Hospital Name &amp; Reg</th>
                    <th>City &amp; Location</th>
                    <th>Contact Info</th>
                    <th>Operating Hours</th>
                    <th>Activity Stats</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hospitals)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-hospital"></i>
                                </div>
                                <h6 class="empty-state-title">No Hospitals Listed</h6>
                                <p class="empty-state-text">No healthcare facilities registered yet. Click "Add New Hospital" to register partner hospitals.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hospitals as $h): ?>
                        <tr>
                            <td>
                                <div class="table-profile-cell">
                                    <div class="table-avatar table-avatar-purple">
                                        <i class="fa-solid fa-hospital"></i>
                                    </div>
                                    <div>
                                        <span class="profile-name"><?= htmlspecialchars($h['name']) ?></span>
                                        <span class="profile-sub font-monospace"><?= htmlspecialchars($h['reg_number'] ?: 'License Verified') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($h['city']) ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width: 200px;"><?= htmlspecialchars($h['location']) ?></small>
                            </td>
                            <td>
                                <div class="small fw-semibold"><i class="fa-solid fa-phone text-muted me-1"></i><?= htmlspecialchars($h['phone']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($h['email']) ?></small>
                            </td>
                            <td>
                                <small class="badge bg-light text-secondary border">
                                    <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($h['operating_hours']) ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 align-items-start">
                                    <span class="badge bg-primary-subtle text-primary border">
                                        <?= (int)$h['total_appointments'] ?> Appointments
                                    </span>
                                    <span class="badge bg-success-subtle text-success border">
                                        <?= (int)$h['total_vaccinated'] ?> Vaccinated
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?= render_status_badge($h['status']) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- Edit Button -->
                                    <a href="<?= BASE_URL ?>/admin/hospitals.php?edit_id=<?= $h['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="Edit Facility">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <!-- Delete  Button -->
                                    <a href="<?= BASE_URL ?>/admin/hospitals.php?action=delete&id=<?= $h['id'] ?>" onclick="return confirm('Are you sure you want to delete or deactivate this hospital?');" class="btn btn-outline-danger btn-sm py-1 px-2" title="Decommission Facility">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
