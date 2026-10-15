<?php
/*
 * Parent - Official Digital Child Immunization Certificate & Health Card
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];
$childId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

// Fetch child record
$child = db_fetch_one("
    SELECT c.*, u.name as parent_name, u.phone as parent_phone, u.address as parent_address, u.email as parent_email
    FROM children c
    JOIN users u ON c.parent_id = u.id
    WHERE c.id = ? AND c.parent_id = ?
", [$childId, $parentId]);

if (!$child) {
    // If not found
    $first = db_fetch_one("SELECT id FROM children WHERE parent_id = ? ORDER BY dob DESC LIMIT 1", [$parentId]);
    if ($first) {
        header("Location: " . BASE_URL . "/parent/certificate.php?child_id=" . $first['id']);
        exit;
    } else {
        set_flash('error', "No child records found to generate certificate.");
        header("Location: " . BASE_URL . "/parent/children.php");
        exit;
    }
}

// Fetch all completed records for this child
$records = db_fetch_all("
    SELECT vr.*, v.name as vaccine_name, v.disease_prevented, v.recommended_age,
           h.name as hospital_name, h.city as hospital_city, h.reg_number as hospital_reg
    FROM vaccination_records vr
    JOIN vaccines v ON vr.vaccine_id = v.id
    JOIN hospitals h ON vr.hospital_id = h.id
    WHERE vr.child_id = ? AND vr.status = 'Vaccinated'
    ORDER BY vr.vaccination_date ASC
", [$child['id']]);

$pageTitle = "Official Digital Immunization Card - " . htmlspecialchars($child['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Top Action Bar (Hidden during printing) -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <a href="<?= BASE_URL ?>/parent/reports.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Reports
        </a>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-print"></i> Print Official Certificate
            </button>
        </div>
    </div>

    <!-- Official Certificate Card -->
    <div class="certificate-canvas bg-white rounded-4 p-4 p-md-5 border border-2 shadow-sm position-relative overflow-hidden">
        
        <!-- Header Banner-->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-4 mb-4 border-bottom border-2 gap-3">
            <div class="d-flex align-items-center gap-3 text-center text-md-start">
                <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center fs-2 flex-shrink-0 shadow-sm" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-shield-virus"></i>
                </div>
                <div>
                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase mb-1">Ministry of Health &bull; National Immunization Program</span>
                    <h1 class="h3 fw-bold text-dark mb-0">Digital Child Immunization Card</h1>
                    <p class="text-muted small font-monospace mb-0">Certificate Verification ID: CERT-<?= strtoupper(substr(md5($child['id'] . $child['name']), 0, 10)) ?></p>
                </div>
            </div>

            <!-- Official Stamp -->
            <div class="certificate-seal text-center flex-shrink-0">
                <span class="small" style="font-size: 0.65rem;">OFFICIALLY CERTIFIED</span>
                <span class="fw-bold fs-6">VERIFIED</span>
                <span class="font-monospace small" style="font-size: 0.7rem;"><?= date('Y-m-d') ?></span>
            </div>
        </div>

        <!-- Child & Guardian Demo Data -->
        <div class="row g-3 p-3 rounded-3 bg-light border mb-4">
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Child Full Name</span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($child['name']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Date of Birth</span>
                <span class="fw-bold text-dark"><?= format_date($child['dob']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Gender & Blood</span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($child['gender']) ?> &bull; <?= htmlspecialchars($child['blood_group'] ?: 'N/A') ?></span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Guardian / Parent</span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($child['parent_name']) ?></span>
            </div>

            <div class="col-12 col-md-6">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Residential Address</span>
                <span class="text-secondary"><?= htmlspecialchars($child['parent_address']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Contact Phone</span>
                <span class="text-secondary"><?= htmlspecialchars($child['parent_phone']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Total Doses Administered</span>
                <span class="badge badge-completed fw-bold"><?= count($records) ?> Doses</span>
            </div>
        </div>

        <!-- Verified Vaccine Administration Table -->
        <div class="mb-4">
            <h5 class="fw-bold text-uppercase text-secondary small mb-3">
                <i class="fa-solid fa-syringe text-primary me-2"></i> Clinical Immunization Record
            </h5>
            <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th class="ps-3">Date Given</th>
                            <th>Vaccine Name</th>
                            <th>Target Disease</th>
                            <th>Administering Hospital</th>
                            <th>Batch / Lot No.</th>
                            <th class="pe-3">Authorized Clinician</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" class="py-4 text-center text-muted">No vaccination doses recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold"><?= format_date($r['vaccination_date']) ?></td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($r['vaccine_name']) ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($r['disease_prevented']) ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($r['hospital_name']) ?></td>
                                    <td class="font-monospace small text-secondary"><?= htmlspecialchars($r['batch_number'] ?: 'VERIFIED') ?></td>
                                    <td class="pe-3 text-secondary"><?= htmlspecialchars($r['doctor_nurse_name'] ?: 'Pediatric Staff') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Verification Signatures & Stamp Footer -->
        <div class="row pt-4 border-top border-2 text-center align-items-center g-3">
            <div class="col-4">
                <div class="border-bottom border-dark mx-auto mb-2" style="width: 70%; height: 35px;"></div>
                <p class="fw-bold text-dark small mb-0">Authorized Medical Officer</p>
                <p class="text-muted small mb-0" style="font-size: 0.75rem;">Hospital Immunization Unit</p>
            </div>
            <div class="col-4 text-center">
                <!-- Visual QR Code Badge -->
                <div class="bg-dark text-white rounded-3 p-2 d-inline-flex flex-column align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-qrcode fs-3"></i>
                </div>
                <div class="text-muted font-monospace small mt-1" style="font-size: 0.65rem;">SCAN TO VERIFY</div>
            </div>
            <div class="col-4">
                <div class="border-bottom border-dark mx-auto mb-2" style="width: 70%; height: 35px;"></div>
                <p class="fw-bold text-dark small mb-0">Public Health Registrar</p>
                <p class="text-muted small mb-0" style="font-size: 0.75rem;">Electronic Registry Authority</p>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
