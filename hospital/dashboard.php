<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('hospital');

$userId = (int)current_user()['id'];
$hospital = db_fetch_one("SELECT * FROM hospitals WHERE user_id = ?", [$userId]);
if (!$hospital) {
    // If not directly linked by user_id, pick by session hospital_id first
    $hId = $_SESSION['user']['hospital_id'] ?? 1;
    $hospital = db_fetch_one("SELECT * FROM hospitals WHERE id = ?", [$hId]);
}

$hospitalId = (int)$hospital['id'];

$pendingAppointmentsCount = db_fetch_one("
    SELECT COUNT(*) as cnt FROM appointments 
    WHERE hospital_id = ? AND status = 'approved'
", [$hospitalId])['cnt'] ?? 0;

$completedVaccinationsCount = db_fetch_one("
    SELECT COUNT(*) as cnt FROM vaccination_records 
    WHERE hospital_id = ? AND status = 'Vaccinated'
", [$hospitalId])['cnt'] ?? 0;

$todayCount = db_fetch_one("
    SELECT COUNT(*) as cnt FROM appointments 
    WHERE hospital_id = ? AND status = 'approved' AND requested_date = CURRENT_DATE
", [$hospitalId])['cnt'] ?? 0;

$queue = db_fetch_all("
    SELECT a.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender, c.blood_group, c.allergies,
           u.name as parent_name, u.phone as parent_phone,
           v.name as vaccine_name, v.disease_prevented
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN users u ON a.parent_id = u.id
    JOIN vaccines v ON a.vaccine_id = v.id
    WHERE a.hospital_id = ? AND a.status = 'approved'
    ORDER BY a.requested_date ASC, a.requested_time_slot ASC
    LIMIT 6
", [$hospitalId]);

$pageTitle = "Hospital Clinical Dashboard - " . htmlspecialchars($hospital['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Hospital Header Hero Banner -->
    <div class="hospital-banner mb-4">
        <div class="hospital-hero-content">
            <div class="hospital-badge">
                <i class="fa-solid fa-shield-heart"></i>
                Verified Vaccination Clinic
            </div>
            <h1 class="hospital-title mb-3">With Vaccination,<br class="d-none d-lg-block"> We Can End This Pandemic</h1>
            <p class="hospital-hero-description">
                Protect children through timely immunizations. Manage appointments, update vaccination records, and help families stay on schedule.
            </p>
            <div class="hospital-meta-row">
                <span class="hospital-meta-pill">
                    <i class="fa-solid fa-hospital"></i>
                    <?= htmlspecialchars($hospital['name']) ?> &bull; <?= htmlspecialchars($hospital['reg_number'] ?: 'VERIFIED') ?>
                </span>
                <span class="hospital-meta-pill">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= htmlspecialchars($hospital['location']) ?> (<?= htmlspecialchars($hospital['city']) ?>)
                </span>
                <span class="hospital-meta-pill">
                    <i class="fa-solid fa-clock"></i>
                    <?= htmlspecialchars($hospital['operating_hours'] ?? '08:00 AM - 05:00 PM') ?>
                </span>
            </div>
            <div class="hospital-hero-actions">
                <a href="<?= BASE_URL ?>/hospital/appointments.php" class="btn hospital-hero-primary">
                    <i class="fa-solid fa-clipboard-list"></i>
                    View Appointments (<?= $pendingAppointmentsCount ?>)
                </a>
                <a href="<?= BASE_URL ?>/hospital/update_status.php" class="btn hospital-hero-secondary">
                    <i class="fa-solid fa-syringe"></i>
                    Update Vaccine Status
                </a>
            </div>
        </div>
        <div class="hospital-hero-visual">
            <img src="<?= BASE_URL ?>/assets/images/hospital.png" alt="Healthcare team supporting child vaccination" class="hospital-hero-image">
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4 ">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Today's Scheduled Patients</span>
                        <h2 class="fw-bold text-primary mb-0 mt-1"><?= $todayCount ?></h2>
                        <small class="text-muted">Approved by Admin for today</small>
                    </div>
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Awaiting Administration</span>
                        <h2 class="fw-bold text-warning mb-0 mt-1"><?= $pendingAppointmentsCount ?></h2>
                        <small class="text-muted">Ready for clinical status update</small>
                    </div>
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-syringe"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small d-block" style="font-size: 0.7rem;">Total Administered Doses</span>
                        <h2 class="fw-bold text-success mb-0 mt-1"><?= $completedVaccinationsCount ?></h2>
                        <small class="text-muted">Verified digital records issued</small>
                    </div>
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Admin Queue Section -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-3 border-bottom gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Upcoming Clinical Queue</h5>
                    <p class="text-muted small mb-0">Approved patients scheduled for immunization arrival at this clinic</p>
                </div>
                <a href="<?= BASE_URL ?>/hospital/appointments.php" class="btn btn-outline-primary btn-sm fw-semibold">
                    View All Queue &rarr;
                </a>
            </div>

            <?php if (empty($queue)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-check fs-1 text-secondary mb-3 d-block"></i>
                    <h6 class="fw-bold text-dark">No pending appointments in your hospital queue.</h6>
                    <p class="small text-muted mb-0">When parents book and administrators approve, appointments will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th class="ps-3">Ref Code</th>
                                <th>Schedule Date & Slot</th>
                                <th>Child Profile</th>
                                <th>Parent Contact</th>
                                <th>Vaccine to Administer</th>
                                <th>Allergies / Warnings</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue as $item): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($item['booking_reference']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= format_date($item['requested_date']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($item['requested_time_slot']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['child_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= calculate_age($item['child_dob']) ?> &bull; <?= htmlspecialchars($item['child_gender']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($item['parent_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($item['parent_phone']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($item['vaccine_name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($item['disease_prevented']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($item['allergies']): ?>
                                            <span class="badge bg-danger-subtle text-danger fw-bold">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($item['allergies']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">None reported</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>/hospital/update_status.php?booking_id=<?= $item['id'] ?>" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                                            <i class="fa-solid fa-square-check"></i> Update Status
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
