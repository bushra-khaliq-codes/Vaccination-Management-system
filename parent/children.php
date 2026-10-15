<?php
/*
 * Parent - Child Details Management Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

// Handle Delete Child
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Verify ownership
    $child = db_fetch_one("SELECT id, name FROM children WHERE id = ? AND parent_id = ?", [$id, $parentId]);
    if ($child) {
        db_execute("DELETE FROM children WHERE id = ?", [$id]);
        set_flash('success', "Child record for '{$child['name']}' removed.");
    } else {
        set_flash('error', "Child profile not found or access denied.");
    }
    header("Location: " . BASE_URL . "/parent/children.php");
    exit;
}

// Handle Form Submissions (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
        header("Location: " . BASE_URL . "/parent/children.php");
        exit;
    }

    $name = sanitize($_POST['name'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Male');
    $bloodGroup = sanitize($_POST['blood_group'] ?? '');
    $birthHospital = sanitize($_POST['birth_hospital'] ?? '');
    $allergies = sanitize($_POST['allergies'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($name) || empty($dob) || empty($gender)) {
        set_flash('error', "Child name, date of birth, and gender are mandatory.");
    } else {
        if (!empty($_POST['child_id'])) {
            // Update Existing Child (verify parent ownership)
            $childId = (int)$_POST['child_id'];
            $child = db_fetch_one("SELECT id FROM children WHERE id = ? AND parent_id = ?", [$childId, $parentId]);
            if ($child) {
                db_execute("
                    UPDATE children 
                    SET name = ?, dob = ?, gender = ?, blood_group = ?, birth_hospital = ?, allergies = ?, notes = ?
                    WHERE id = ? AND parent_id = ?
                ", [$name, $dob, $gender, $bloodGroup, $birthHospital, $allergies, $notes, $childId, $parentId]);
                set_flash('success', "Child record for '{$name}' successfully updated.");
            } else {
                set_flash('error', "Profile not found or access denied.");
            }
        } else {
            // Add New Child
            db_execute("
                INSERT INTO children (parent_id, name, dob, gender, blood_group, birth_hospital, allergies, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [$parentId, $name, $dob, $gender, $bloodGroup, $birthHospital, $allergies, $notes]);
            $newChildId = db_last_id();

            // Notification
            db_execute("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, ?, ?, 'info')
            ", [
                $parentId,
                "Child Profile Registered: {$name}",
                "{$name}'s immunization schedule is now active. You can review scheduled vaccines and book slots."
            ]);

            set_flash('success', "Child profile for '{$name}' registered! Immunization schedule is now active.");
        }

        header("Location: " . BASE_URL . "/parent/children.php");
        exit;
    }
}

// Fetch child for edit if requested
$editChild = null;
if ($editId) {
    $editChild = db_fetch_one("SELECT * FROM children WHERE id = ? AND parent_id = ?", [$editId, $parentId]);
}

// Fetch all children for this parent
$children = db_fetch_all("
    SELECT c.*,
           (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id AND vr.status = 'Vaccinated') as completed_doses
    FROM children c
    WHERE c.parent_id = ?
    ORDER BY c.dob DESC
", [$parentId]);

$pageTitle = "My Children - Parent Portal";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Manage Child Profiles (<?= count($children) ?>)</h1>
        <p class="page-subtitle">Maintain vital health records, birth dates, allergies, and access digital immunization tracking.</p>
    </div>
    <button type="button" onclick="document.getElementById('childFormCard').classList.toggle('d-none')" class="btn btn-success">
        <i class="fa-solid fa-plus me-1"></i> <?= ($editChild || $action === 'add') ? 'Close Form' : 'Register New Child' ?>
    </button>
</div>

<!-- Child Add  -->
<div id="childFormCard" class="form-card <?= ($editChild || $action === 'add') ? '' : 'd-none' ?> border-success">
    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-baby text-success me-2"></i>
            <?= $editChild ? 'Update Profile: ' . htmlspecialchars($editChild['name']) : 'Register New Child / Infant' ?>
        </h5>
        <button type="button" onclick="document.getElementById('childFormCard').classList.add('d-none')" class="btn-close" aria-label="Close"></button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/parent/children.php" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <?php if ($editChild): ?>
            <input type="hidden" name="child_id" value="<?= $editChild['id'] ?>">
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label-custom">Child Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editChild['name'] ?? '') ?>" placeholder="e.g. Leo Jenkins" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Date of Birth (DOB) <span class="text-danger">*</span></label>
                <input type="date" name="dob" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($editChild['dob'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select">
                    <option value="Male" <?= ($editChild && $editChild['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($editChild && $editChild['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($editChild && $editChild['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label-custom">Blood Group</label>
                <select name="blood_group" class="form-select">
                    <option value="">Unknown / Select</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                        <option value="<?= $bg ?>" <?= ($editChild && $editChild['blood_group'] === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Birth Hospital / Maternity Center</label>
                <input type="text" name="birth_hospital" value="<?= htmlspecialchars($editChild['birth_hospital'] ?? '') ?>" placeholder="e.g. City General Hospital" class="form-control">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label-custom">Known Allergies / Sensitivities</label>
                <input type="text" name="allergies" value="<?= htmlspecialchars($editChild['allergies'] ?? '') ?>" placeholder="e.g. Egg protein, Penicillin, None" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Special Clinical / Growth Notes</label>
                <input type="text" name="notes" value="<?= htmlspecialchars($editChild['notes'] ?? '') ?>" placeholder="e.g. Pre-term delivery, healthy birth weight" class="form-control">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/parent/children.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-save me-1"></i> <?= $editChild ? 'Update Child Record' : 'Save & Register Child' ?>
            </button>
        </div>
    </form>
</div>

<!-- Children Cards Grid -->
<div class="row g-4">
    <?php if (empty($children)): ?>
        <div class="col-12">
            <div class="card-healthcare p-5 text-center">
                <div class="empty-state-icon text-success">
                    <i class="fa-solid fa-baby-carriage"></i>
                </div>
                <h5 class="fw-bold text-dark">No Children Registered Yet</h5>
                <p class="text-muted small mx-auto mb-3" style="max-width: 400px;">
                    Please add your child's profile to activate automated immunization due date tracking according to WHO standards.
                </p>
                <button type="button" onclick="document.getElementById('childFormCard').classList.remove('d-none')" class="btn btn-success">
                    <i class="fa-solid fa-plus me-1"></i> Register Child Now
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($children as $c): ?>
            <div class="col-md-6 col-lg-4">
                <div class="child-dossier-card">
                    <div>
                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="child-avatar-circle">
                                    <i class="fa-solid fa-baby"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($c['name']) ?></h5>
                                    <small class="text-muted">Born <?= format_date($c['dob']) ?></small>
                                </div>
                            </div>
                            <span class="badge <?= $c['gender'] === 'Male' ? 'bg-info-subtle text-info border' : 'bg-danger-subtle text-danger border' ?>">
                                <?= htmlspecialchars($c['gender']) ?>
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="py-2 border-top border-bottom mb-3">
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Current Age:</span>
                                <span class="fw-bold text-dark"><?= calculate_age($c['dob']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Blood Group:</span>
                                <span class="fw-bold text-danger"><?= htmlspecialchars($c['blood_group'] ?: 'Not recorded') ?></span>
                            </div>
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-muted">Doses Completed:</span>
                                <span class="fw-bold text-success"><i class="fa-solid fa-shield-check me-1"></i><?= (int)$c['completed_doses'] ?> Doses</span>
                            </div>
                            <?php if ($c['allergies']): ?>
                                <div class="small text-danger fw-semibold pt-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Allergies: <?= htmlspecialchars($c['allergies']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div>
                        <div class="d-flex gap-2 mb-2">
                            <a href="<?= BASE_URL ?>/parent/schedule.php?child_id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fa-solid fa-calendar-days me-1"></i> Due Dates
                            </a>
                            <a href="<?= BASE_URL ?>/parent/certificate.php?child_id=<?= $c['id'] ?>" class="btn btn-outline-success btn-sm flex-fill">
                                <i class="fa-solid fa-id-card me-1"></i> Digital Card
                            </a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <a href="<?= BASE_URL ?>/parent/children.php?edit_id=<?= $c['id'] ?>" class="text-primary text-decoration-none small fw-semibold">
                                <i class="fa-solid fa-pen me-1"></i> Edit Details
                            </a>
                            <a href="<?= BASE_URL ?>/parent/children.php?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to remove this child profile?');" class="text-danger text-decoration-none small">
                                <i class="fa-solid fa-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
