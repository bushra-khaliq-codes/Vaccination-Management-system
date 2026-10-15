<?php
/*
 * Administrator - Vaccine Management & Availability Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

// Handle Status Toggle (Quick Action)
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $vac = db_fetch_one("SELECT status FROM vaccines WHERE id = ?", [$id]);
    if ($vac) {
        $newStatus = ($vac['status'] === 'available') ? 'unavailable' : 'available';
        db_execute("UPDATE vaccines SET status = ? WHERE id = ?", [$newStatus, $id]);
        set_flash('success', "Vaccine status changed to " . ucfirst($newStatus));
    }
    header("Location: " . BASE_URL . "/admin/vaccines.php");
    exit;
}

// Handle Delete Vaccine
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $vac = db_fetch_one("SELECT name FROM vaccines WHERE id = ?", [$id]);
    if ($vac) {
        db_execute("DELETE FROM vaccines WHERE id = ?", [$id]);
        set_flash('success', "Vaccine '{$vac['name']}' removed from catalog.");
    }
    header("Location: " . BASE_URL . "/admin/vaccines.php");
    exit;
}

// Handle Form Submissions (Create / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
        header("Location: " . BASE_URL . "/admin/vaccines.php");
        exit;
    }

    $name = sanitize($_POST['name'] ?? '');
    $disease = sanitize($_POST['disease_prevented'] ?? '');
    $age = sanitize($_POST['recommended_age'] ?? '');
    $days = (int)($_POST['recommended_days_from_birth'] ?? 0);
    $doses = (int)($_POST['doses_required'] ?? 1);
    $status = sanitize($_POST['status'] ?? 'available');
    $stock = (int)($_POST['stock_quantity'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || empty($disease) || empty($age)) {
        set_flash('error', "Vaccine name, targeted disease, and recommended age are mandatory.");
    } else {
        if (!empty($_POST['vaccine_id'])) {
            // Update
            $vacId = (int)$_POST['vaccine_id'];
            db_execute("
                UPDATE vaccines 
                SET name = ?, disease_prevented = ?, recommended_age = ?, 
                    recommended_days_from_birth = ?, doses_required = ?, 
                    status = ?, stock_quantity = ?, description = ?
                WHERE id = ?
            ", [$name, $disease, $age, $days, $doses, $status, $stock, $description, $vacId]);
            set_flash('success', "Vaccine '{$name}' updated successfully.");
        } else {
            // Insert
            db_execute("
                INSERT INTO vaccines (name, disease_prevented, recommended_age, recommended_days_from_birth, doses_required, status, stock_quantity, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [$name, $disease, $age, $days, $doses, $status, $stock, $description]);
            set_flash('success', "New vaccine '{$name}' added to immunization catalog.");
        }
        header("Location: " . BASE_URL . "/admin/vaccines.php");
        exit;
    }
}

// Fetch vaccine for edit 
$editVaccine = null;
if ($editId) {
    $editVaccine = db_fetch_one("SELECT * FROM vaccines WHERE id = ?", [$editId]);
}

// Fetch all vaccines
$vaccines = db_fetch_all("SELECT * FROM vaccines ORDER BY recommended_days_from_birth ASC, id ASC");

$pageTitle = "Vaccine Inventory & Availability - Administrator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Vaccine Catalog &amp; Availability</h1>
        <p class="page-subtitle">Manage immunization inventory, recommended age milestones, and toggle availability status.</p>
    </div>
    <button type="button" onclick="document.getElementById('vaccineFormCard').classList.toggle('d-none')" class="btn btn-purple">
        <i class="fa-solid fa-plus me-1"></i> <?= ($editVaccine || $action === 'add') ? 'Close Form' : 'Add New Vaccine' ?>
    </button>
</div>

<!-- Vaccine Edit / Add Form Card -->
<div id="vaccineFormCard" class="form-card <?= ($editVaccine || $action === 'add') ? '' : 'd-none' ?> border-purple">
    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-vial text-purple me-2"></i>
            <?= $editVaccine ? 'Edit Vaccine: ' . htmlspecialchars($editVaccine['name']) : 'Register New Vaccine into Schedule' ?>
        </h5>
        <button type="button" onclick="document.getElementById('vaccineFormCard').classList.add('d-none')" class="btn-close" aria-label="Close"></button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/vaccines.php" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <?php if ($editVaccine): ?>
            <input type="hidden" name="vaccine_id" value="<?= $editVaccine['id'] ?>">
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label-custom">Vaccine Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editVaccine['name'] ?? '') ?>" placeholder="e.g. Pentavalent 1" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Target Disease(s) <span class="text-danger">*</span></label>
                <input type="text" name="disease_prevented" required value="<?= htmlspecialchars($editVaccine['disease_prevented'] ?? '') ?>" placeholder="e.g. Polio, Diphtheria" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">Recommended Age <span class="text-danger">*</span></label>
                <input type="text" name="recommended_age" required value="<?= htmlspecialchars($editVaccine['recommended_age'] ?? '') ?>" placeholder="e.g. 6 Weeks, At Birth" class="form-control">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label-custom">Days from Birth (Schedule Milestone)</label>
                <input type="number" name="recommended_days_from_birth" value="<?= (int)($editVaccine['recommended_days_from_birth'] ?? 0) ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Doses Required</label>
                <input type="number" name="doses_required" value="<?= (int)($editVaccine['doses_required'] ?? 1) ?>" min="1" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Stock Quantity (Doses)</label>
                <input type="number" name="stock_quantity" value="<?= (int)($editVaccine['stock_quantity'] ?? 100) ?>" min="0" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Availability Status</label>
                <select name="status" class="form-select">
                    <option value="available" <?= ($editVaccine && $editVaccine['status'] === 'available') ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= ($editVaccine && $editVaccine['status'] === 'unavailable') ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label-custom">Clinical Description &amp; Dosage Instructions</label>
            <textarea name="description" rows="2" placeholder="Administration route, clinical precautions..." class="form-control"><?= htmlspecialchars($editVaccine['description'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/admin/vaccines.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-purple">
                <i class="fa-solid fa-save me-1"></i> <?= $editVaccine ? 'Save Changes' : 'Register Vaccine' ?>
            </button>
        </div>
    </form>
</div>

<!-- Vaccines Catalog Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Vaccine Name</th>
                    <th>Disease Prevented</th>
                    <th>Schedule Milestone</th>
                    <th>Stock Units</th>
                    <th>Availability</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vaccines)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-vial"></i>
                                </div>
                                <h6 class="empty-state-title">No Vaccines in Catalog</h6>
                                <p class="empty-state-text">The vaccine inventory is currently empty. Add vaccines to build the infant immunization catalog.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vaccines as $v): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($v['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($v['description'] ?: 'Standard immunization dose') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($v['disease_prevented']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary"><?= htmlspecialchars($v['recommended_age']) ?></div>
                                <small class="text-muted">Day +<?= (int)$v['recommended_days_from_birth'] ?></small>
                            </td>
                            <td>
                                <span class="badge <?= (int)$v['stock_quantity'] < 20 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> border">
                                    <i class="fa-solid fa-boxes-stacked me-1"></i><?= (int)$v['stock_quantity'] ?> doses
                                </span>
                            </td>
                            <td>
                                <?= render_status_badge($v['status']) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- Toggle Status Button -->
                                    <a href="<?= BASE_URL ?>/admin/vaccines.php?action=toggle&id=<?= $v['id'] ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Toggle Status">
                                        <i class="fa-solid fa-power-off me-1"></i> <?= $v['status'] === 'available' ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                    <!-- Edit Button -->
                                    <a href="<?= BASE_URL ?>/admin/vaccines.php?edit_id=<?= $v['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="Edit Details">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <!-- Delete Button -->
                                    <a href="<?= BASE_URL ?>/admin/vaccines.php?action=delete&id=<?= $v['id'] ?>" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars(addslashes($v['name'])) ?> from the vaccine catalog?')" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Vaccine">
                                        <i class="fa-solid fa-trash"></i>
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
