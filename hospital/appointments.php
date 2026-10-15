<?php
/*
 * Hospital - Received Appointments Roster Module
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

$filter = $_GET['status'] ?? 'approved';

// Build query
$sql = "
    SELECT a.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender, c.blood_group, c.allergies,
           u.name as parent_name, u.phone as parent_phone, u.address as parent_address,
           v.name as vaccine_name, v.disease_prevented
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN users u ON a.parent_id = u.id
    JOIN vaccines v ON a.vaccine_id = v.id
    WHERE a.hospital_id = ?
";
$params = [$hospitalId];

if ($filter !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY a.requested_date DESC, a.id DESC";

$appointments = db_fetch_all($sql, $params);

// Counts
$counts = [
    'approved' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE hospital_id = ? AND status = 'approved'", [$hospitalId])['cnt'] ?? 0,
    'completed' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE hospital_id = ? AND status = 'completed'", [$hospitalId])['cnt'] ?? 0,
    'missed' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE hospital_id = ? AND status = 'missed'", [$hospitalId])['cnt'] ?? 0,
    'all' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE hospital_id = ?", [$hospitalId])['cnt'] ?? 0
];

$pageTitle = "Appointments Received - " . htmlspecialchars($hospital['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Hospital Appointment Registry</h1>
            <p class="text-muted small mb-0">Review approved patients assigned to <?= htmlspecialchars($hospital['name']) ?> and update clinical administration status.</p>
        </div>
        <div style="min-width: 260px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" data-table-search="hospitalApptsTable" placeholder="Search child, ref code, parent..." class="form-control border-start-0">
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4 pb-2 border-bottom">
        <a href="<?= BASE_URL ?>/hospital/appointments.php?status=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-primary fw-bold shadow-sm' : 'btn-outline-secondary' ?>">
            <i class="fa-solid fa-clock me-1"></i> Awaiting Visit (<?= $counts['approved'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/hospital/appointments.php?status=completed" class="btn btn-sm <?= $filter === 'completed' ? 'btn-success fw-bold shadow-sm' : 'btn-outline-secondary' ?>">
            <i class="fa-solid fa-circle-check me-1"></i> Completed / Vaccinated (<?= $counts['completed'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/hospital/appointments.php?status=missed" class="btn btn-sm <?= $filter === 'missed' ? 'btn-danger fw-bold shadow-sm' : 'btn-outline-secondary' ?>">
            <i class="fa-solid fa-circle-xmark me-1"></i> Not Vaccinated / Missed (<?= $counts['missed'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/hospital/appointments.php?status=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-dark fw-bold shadow-sm' : 'btn-outline-secondary' ?>">
            <i class="fa-solid fa-list me-1"></i> All Records (<?= $counts['all'] ?>)
        </a>
    </div>

    <!-- Appointments Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="hospitalApptsTable" class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th class="ps-4">Ref Code</th>
                            <th>Schedule Date & Slot</th>
                            <th>Child Profile</th>
                            <th>Guardian Contact</th>
                            <th>Vaccine Target</th>
                            <th>Status</th>
                            <th>Administration Notes</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="py-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fs-2 mb-3" style="width: 56px; height: 56px;">
                                            <i class="fa-solid fa-hospital-user"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">No Appointments in this Category</h6>
                                        <p class="text-muted small mb-0" style="max-width: 450px; margin: 0 auto;">
                                            When fresh appointments are approved and routed to this hospital center, they will appear here for clinical administration and status updates.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-dark"><?= htmlspecialchars($a['booking_reference']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= format_date($a['requested_date']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($a['requested_time_slot']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($a['child_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">Born <?= format_date($a['child_dob']) ?> (<?= calculate_age($a['child_dob']) ?>) &bull; <?= htmlspecialchars($a['child_gender']) ?></div>
                                        <?php if ($a['allergies']): ?>
                                            <span class="badge bg-danger-subtle text-danger fw-bold mt-1 d-inline-block">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($a['allergies']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($a['parent_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($a['parent_phone']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($a['vaccine_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($a['disease_prevented']) ?></div>
                                    </td>
                                    <td>
                                        <?= render_status_badge($a['status']) ?>
                                    </td>
                                    <td class="small" style="max-width: 200px;">
                                        <?php if ($a['vaccine_batch_no']): ?>
                                            <div class="font-monospace text-success fw-bold" style="font-size: 0.75rem;">Batch: <?= htmlspecialchars($a['vaccine_batch_no']) ?></div>
                                        <?php endif; ?>
                                        <div class="text-secondary"><?= htmlspecialchars($a['hospital_remarks'] ?: $a['admin_notes'] ?: 'No notes') ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($a['status'] === 'approved'): ?>
                                            <a href="<?= BASE_URL ?>/hospital/update_status.php?booking_id=<?= $a['id'] ?>" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                                                <i class="fa-solid fa-square-check"></i> Update Status
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Record Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
