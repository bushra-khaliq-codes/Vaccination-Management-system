<?php
/*
 * Administrator - All Child Details Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Handle Delete Child (Admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $child = db_fetch_one("SELECT name FROM children WHERE id = ?", [$id]);
    if ($child) {
        db_execute("DELETE FROM children WHERE id = ?", [$id]);
        db_execute("DELETE FROM vaccination_records WHERE child_id = ?", [$id]);
        db_execute("DELETE FROM appointments WHERE child_id = ?", [$id]);
        set_flash('success', "Child record for '{$child['name']}' removed from registry.");
    }
    header("Location: " . BASE_URL . "/admin/children.php");
    exit;
}

// Selected child for detail modal view 
$selectedChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : null;
$selectedChild = null;
$selectedSchedule = [];

if ($selectedChildId) {
    $selectedChild = db_fetch_one("
        SELECT c.*, u.name as parent_name, u.email as parent_email, u.phone as parent_phone, u.address as parent_address
        FROM children c
        JOIN users u ON c.parent_id = u.id
        WHERE c.id = ?
    ", [$selectedChildId]);

    if ($selectedChild) {
        $selectedSchedule = get_child_immunization_schedule($selectedChildId);
    }
}

// Fetch all children with parent details
$children = db_fetch_all("
    SELECT c.*, u.name as parent_name, u.email as parent_email, u.phone as parent_phone, u.address as parent_address,
           (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id AND vr.status = 'Vaccinated') as completed_doses
    FROM children c
    JOIN users u ON c.parent_id = u.id
    ORDER BY c.dob DESC
");

$pageTitle = "All Child Details - Administrator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">All Registered Children (<?= count($children) ?>)</h1>
        <p class="page-subtitle">Review infant profiles, parental guardianship records, and immunization completion progress.</p>
    </div>
    <div class="search-box-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" data-table-search="childrenTable" placeholder="Search child, parent, phone..." class="form-control">
    </div>
</div>

<!-- Child Detail Modal Section  -->
<?php if ($selectedChild): ?>
    <div class="card-healthcare p-4 mb-4 border-primary" id="timelineModal">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary mb-1">
                    <i class="fa-solid fa-file-waveform me-1"></i> Detailed Immunization Dossier
                </span>
                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($selectedChild['name']) ?></h3>
                <p class="text-muted small mb-0">
                    Born: <strong><?= format_date($selectedChild['dob']) ?></strong> (<?= calculate_age($selectedChild['dob']) ?>) &bull;
                    Guardian: <strong><?= htmlspecialchars($selectedChild['parent_name']) ?></strong> (<?= htmlspecialchars($selectedChild['parent_phone']) ?>)
                </p>
            </div>
            <a href="<?= BASE_URL ?>/admin/children.php" class="btn btn-outline-secondary btn-sm align-self-start">
                <i class="fa-solid fa-xmark me-1"></i> Close Dossier
            </a>
        </div>

        <!-- Schedule & Record Breakdown Table -->
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Vaccine Name</th>
                        <th>Recommended Age</th>
                        <th>Target Due Date</th>
                        <th>Status</th>
                        <th>Hospital Facility</th>
                        <th>Batch / Lot</th>
                        <th>Administered By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedSchedule as $item): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['vaccine_name']) ?></td>
                            <td><?= htmlspecialchars($item['recommended_age']) ?></td>
                            <td><?= format_date($item['due_date']) ?></td>
                            <td><?= render_status_badge($item['status']) ?></td>
                            <td><small class="text-secondary"><?= htmlspecialchars($item['record']['hospital_name'] ?? $item['appointment']['hospital_name'] ?? '—') ?></small></td>
                            <td><span class="font-monospace text-muted small"><?= htmlspecialchars($item['record']['batch_number'] ?? '—') ?></span></td>
                            <td><small class="text-secondary"><?= htmlspecialchars($item['record']['doctor_nurse_name'] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Children Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table id="childrenTable" class="table-custom">
            <thead>
                <tr>
                    <th>Child Profile</th>
                    <th>Gender &amp; Blood</th>
                    <th>Parent / Guardian</th>
                    <th>Birth Hospital</th>
                    <th>Allergies &amp; Notes</th>
                    <th>Completed Doses</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($children)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-children"></i>
                                </div>
                                <h6 class="empty-state-title">No Registered Children</h6>
                                <p class="empty-state-text">The child registry is currently empty. Profiles will appear here as parents register.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($children as $c): ?>
                        <tr>
                            <td>
                                <div class="table-profile-cell">
                                    <div class="table-avatar <?= $c['gender'] === 'Male' ? 'table-avatar-blue' : 'table-avatar-pink' ?>">
                                        <i class="fa-solid fa-baby"></i>
                                    </div>
                                    <div>
                                        <span class="profile-name"><?= htmlspecialchars($c['name']) ?></span>
                                        <span class="profile-sub">
                                            DOB: <?= format_date($c['dob']) ?> &bull; <strong class="text-primary"><?= calculate_age($c['dob']) ?></strong>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 align-items-start">
                                    <span class="badge <?= $c['gender'] === 'Male' ? 'bg-info-subtle text-info border' : 'bg-danger-subtle text-danger border' ?>">
                                        <?= htmlspecialchars($c['gender']) ?>
                                    </span>
                                    <?php if ($c['blood_group']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-solid fa-droplet text-danger me-1"></i><?= htmlspecialchars($c['blood_group']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($c['parent_name']) ?></div>
                                <div class="text-muted small"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['parent_phone']) ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 180px;"><?= htmlspecialchars($c['parent_address']) ?></div>
                            </td>
                            <td>
                                <small class="text-secondary"><?= htmlspecialchars($c['birth_hospital'] ?: 'Not specified') ?></small>
                            </td>
                            <td>
                                <?php if ($c['allergies']): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border d-inline-block mb-1">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($c['allergies']) ?>
                                    </span><br>
                                <?php endif; ?>
                                <small class="text-muted"><?= htmlspecialchars($c['notes'] ?: 'No medical notes') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border">
                                    <i class="fa-solid fa-shield-check me-1"></i> <?= (int)$c['completed_doses'] ?> Doses
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?= BASE_URL ?>/admin/children.php?child_id=<?= $c['id'] ?>#timelineModal" class="btn btn-outline-primary btn-sm py-1 px-2" title="View Dossier">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/children.php?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars(addslashes($c['name'])) ?> from the registry?')" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Profile">
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
