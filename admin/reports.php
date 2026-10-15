<?php
/*
 * Administrator - Date-wise Vaccination Report Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Filter inputs
$fromDate = $_GET['from_date'] ?? date('Y-01-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$vaccineId = !empty($_GET['vaccine_id']) ? (int)$_GET['vaccine_id'] : 0;
$hospitalId = !empty($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 0;
$statusFilter = $_GET['status'] ?? 'Vaccinated';

// CSV Export Request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vaccination_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Record ID', 'Vaccination Date', 'Child Name', 'Child DOB', 'Parent Name', 'Parent Phone', 'Vaccine Name', 'Disease Prevented', 'Dose No', 'Hospital Name', 'Batch Number', 'Administered By', 'Status', 'Remarks']);

    $csvSql = "
        SELECT vr.*, c.name as child_name, c.dob as child_dob, 
               u.name as parent_name, u.phone as parent_phone,
               v.name as vaccine_name, v.disease_prevented,
               h.name as hospital_name
        FROM vaccination_records vr
        JOIN children c ON vr.child_id = c.id
        JOIN users u ON c.parent_id = u.id
        JOIN vaccines v ON vr.vaccine_id = v.id
        JOIN hospitals h ON vr.hospital_id = h.id
        WHERE vr.vaccination_date BETWEEN ? AND ?
    ";
    $csvParams = [$fromDate, $toDate];
    if ($vaccineId > 0) {
        $csvSql .= " AND vr.vaccine_id = ?";
        $csvParams[] = $vaccineId;
    }
    if ($hospitalId > 0) {
        $csvSql .= " AND vr.hospital_id = ?";
        $csvParams[] = $hospitalId;
    }
    if ($statusFilter !== 'all') {
        $csvSql .= " AND vr.status = ?";
        $csvParams[] = $statusFilter;
    }
    $csvSql .= " ORDER BY vr.vaccination_date DESC";

    $csvRows = db_fetch_all($csvSql, $csvParams);
    foreach ($csvRows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['vaccination_date'],
            $row['child_name'],
            $row['child_dob'],
            $row['parent_name'],
            $row['parent_phone'],
            $row['vaccine_name'],
            $row['disease_prevented'],
            $row['dose_number'],
            $row['hospital_name'],
            $row['batch_number'],
            $row['doctor_nurse_name'],
            $row['status'],
            $row['remarks']
        ]);
    }
    fclose($output);
    exit;
}

// Build filtered query
$sql = "
    SELECT vr.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender,
           u.name as parent_name, u.phone as parent_phone,
           v.name as vaccine_name, v.disease_prevented,
           h.name as hospital_name, h.city as hospital_city
    FROM vaccination_records vr
    JOIN children c ON vr.child_id = c.id
    JOIN users u ON c.parent_id = u.id
    JOIN vaccines v ON vr.vaccine_id = v.id
    JOIN hospitals h ON vr.hospital_id = h.id
    WHERE vr.vaccination_date BETWEEN ? AND ?
";
$params = [$fromDate, $toDate];

if ($vaccineId > 0) {
    $sql .= " AND vr.vaccine_id = ?";
    $params[] = $vaccineId;
}
if ($hospitalId > 0) {
    $sql .= " AND vr.hospital_id = ?";
    $params[] = $hospitalId;
}
if ($statusFilter !== 'all') {
    $sql .= " AND vr.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY vr.vaccination_date DESC, vr.id DESC";
$reportRecords = db_fetch_all($sql, $params);

// Fetch dropdown filters
$allVaccines = db_fetch_all("SELECT id, name FROM vaccines ORDER BY name ASC");
$allHospitals = db_fetch_all("SELECT id, name FROM hospitals ORDER BY name ASC");

$pageTitle = "Vaccination Date-wise Report - Administrator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Vaccination Date-Wise Clinical Report</h1>
        <p class="page-subtitle">Audit child immunizations across date periods, facilities, and vaccine categories.</p>
    </div>
    <div class="d-flex align-items-center gap-2 no-print">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fa-solid fa-print me-1"></i> Print Report
        </button>
        <?php 
            $exportUrl = BASE_URL . "/admin/reports.php?export=csv&from_date=" . urlencode($fromDate) . "&to_date=" . urlencode($toDate) . "&vaccine_id=" . $vaccineId . "&hospital_id=" . $hospitalId . "&status=" . urlencode($statusFilter);
        ?>
        <a href="<?= $exportUrl ?>" class="btn btn-purple">
            <i class="fa-solid fa-file-csv me-1"></i> Export to CSV
        </a>
    </div>
</div>

<!-- Filter Form Card  -->
<div class="card-healthcare p-4 mb-4 no-print">
    <form method="GET" action="<?= BASE_URL ?>/admin/reports.php">
        <div class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label-custom">From Date</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="form-control">
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label-custom">To Date</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="form-control">
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label-custom">Vaccine</label>
                <select name="vaccine_id" class="form-select">
                    <option value="0">All Vaccines</option>
                    <?php foreach ($allVaccines as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $vaccineId === (int)$v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label-custom">Hospital Facility</label>
                <select name="hospital_id" class="form-select">
                    <option value="0">All Facilities</option>
                    <?php foreach ($allHospitals as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $hospitalId === (int)$h['id'] ? 'selected' : '' ?>><?= htmlspecialchars($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 text-end mt-3">
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-secondary me-2">Reset</a>
                <button type="submit" class="btn btn-purple">
                    <i class="fa-solid fa-filter me-1"></i> Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Report Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card metric-purple">
            <div class="metric-header">
                <span class="metric-label">Total Doses</span>
                <span class="metric-icon"><i class="fa-solid fa-syringe"></i></span>
            </div>
            <div class="metric-value"><?= count($reportRecords) ?></div>
            <span class="small text-muted">Filtered period</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card metric-emerald">
            <div class="metric-header">
                <span class="metric-label">Distinct Children</span>
                <span class="metric-icon"><i class="fa-solid fa-children"></i></span>
            </div>
            <div class="metric-value"><?= count(array_unique(array_column($reportRecords, 'child_id'))) ?></div>
            <span class="small text-muted">Unique patients</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card metric-indigo">
            <div class="metric-header">
                <span class="metric-label">Hospitals</span>
                <span class="metric-icon"><i class="fa-solid fa-hospital"></i></span>
            </div>
            <div class="metric-value"><?= count(array_unique(array_column($reportRecords, 'hospital_id'))) ?></div>
            <span class="small text-muted">Participating centers</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label">Audit Status</span>
                <span class="metric-icon"><i class="fa-solid fa-clipboard-check"></i></span>
            </div>
            <div class="metric-value fs-4"><?= htmlspecialchars(ucwords($statusFilter)) ?></div>
            <span class="small text-muted"><?= format_date($fromDate, 'M d') ?> – <?= format_date($toDate, 'M d, Y') ?></span>
        </div>
    </div>
</div>

<!-- Report Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Date Given</th>
                    <th>Child Details</th>
                    <th>Parent / Guardian</th>
                    <th>Vaccine &amp; Disease</th>
                    <th>Hospital Facility</th>
                    <th>Batch / Lot</th>
                    <th>Medical Staff</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportRecords)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-file-medical"></i>
                                </div>
                                <h6 class="empty-state-title">No Vaccination Records Found</h6>
                                <p class="empty-state-text">No records match the chosen date range and filter criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportRecords as $r): ?>
                        <tr>
                            <td>
                                <span class="font-monospace fw-bold text-dark"><?= format_date($r['vaccination_date']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['child_name']) ?></div>
                                <small class="text-muted">Born: <?= format_date($r['child_dob']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($r['parent_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['parent_phone']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($r['vaccine_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['disease_prevented']) ?></small>
                            </td>
                            <td>
                                <span class="text-dark small fw-semibold"><?= htmlspecialchars($r['hospital_name']) ?></span>
                            </td>
                            <td>
                                <span class="font-monospace small text-muted"><?= htmlspecialchars($r['batch_number'] ?: 'Verified Lot') ?></span>
                            </td>
                            <td>
                                <small class="text-secondary"><?= htmlspecialchars($r['doctor_nurse_name'] ?: 'Clinical Staff') ?></small>
                            </td>
                            <td>
                                <?= render_status_badge($r['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
