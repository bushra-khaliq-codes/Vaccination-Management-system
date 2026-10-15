<?php
/*
 * Parent - Vaccination History Report Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];
$childFilter = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

// Fetch parent's children for dropdown filter
$myChildren = db_fetch_all("SELECT id, name FROM children WHERE parent_id = ? ORDER BY name ASC", [$parentId]);

// Fetch completed records
$sql = "
    SELECT vr.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender,
           v.name as vaccine_name, v.disease_prevented, v.recommended_age,
           h.name as hospital_name, h.city as hospital_city
    FROM vaccination_records vr
    JOIN children c ON vr.child_id = c.id
    JOIN vaccines v ON vr.vaccine_id = v.id
    JOIN hospitals h ON vr.hospital_id = h.id
    WHERE c.parent_id = ?
";
$params = [$parentId];

if ($childFilter > 0) {
    $sql .= " AND vr.child_id = ?";
    $params[] = $childFilter;
}
$sql .= " ORDER BY vr.vaccination_date DESC, vr.id DESC";

$records = db_fetch_all($sql, $params);

$pageTitle = "Vaccination History Reports - Parent Portal";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Vaccination History & Records</h1>
            <p class="text-muted small mb-0">Verified records of all immunizations administered to your infants across partner hospitals.</p>
        </div>

        <!-- Filter by Child -->
        <form method="GET" action="<?= BASE_URL ?>/parent/reports.php" class="d-flex align-items-center gap-2">
            <label class="form-label fw-semibold small text-muted mb-0">Child:</label>
            <select name="child_id" onchange="this.form.submit()" class="form-select form-select-sm" style="min-width: 180px;">
                <option value="0">All Children</option>
                <?php foreach ($myChildren as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $childFilter === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Records Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th class="ps-4">Date Given</th>
                            <th>Child Name</th>
                            <th>Vaccine & Target</th>
                            <th>Hospital Facility</th>
                            <th>Batch / Lot</th>
                            <th>Administered By</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Digital Card</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fa-solid fa-file-medical fs-1 text-secondary mb-3 d-block"></i>
                                        <h6 class="fw-bold text-dark">No Vaccination Records Found</h6>
                                        <p class="small text-muted mb-0">Book an appointment to begin or update your child's immunization record.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-dark"><?= format_date($r['vaccination_date']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($r['child_name']) ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($r['vaccine_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($r['disease_prevented']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($r['hospital_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($r['hospital_city']) ?></div>
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        <?= htmlspecialchars($r['batch_number'] ?: 'Verified Batch') ?>
                                    </td>
                                    <td class="text-secondary small">
                                        <?= htmlspecialchars($r['doctor_nurse_name'] ?: 'Clinical Staff') ?>
                                    </td>
                                    <td>
                                        <?= render_status_badge($r['status']) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= BASE_URL ?>/parent/certificate.php?child_id=<?= $r['child_id'] ?>" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1">
                                            <i class="fa-solid fa-id-card"></i> Digital Card
                                        </a>
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
