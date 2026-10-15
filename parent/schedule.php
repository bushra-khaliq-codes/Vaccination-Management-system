<?php
/*
 * Parent - Child Personalized Vaccination Timetable Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];

// Fetch all children of this parent
$myChildren = db_fetch_all("SELECT * FROM children WHERE parent_id = ? ORDER BY dob DESC", [$parentId]);

if (empty($myChildren)) {
    set_flash('info', "Please register your child first to generate their immunization schedule.");
    header("Location: " . BASE_URL . "/parent/children.php?action=add");
    exit;
}

// Select active child 
$activeChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (int)$myChildren[0]['id'];
$activeChild = db_fetch_one("SELECT * FROM children WHERE id = ? AND parent_id = ?", [$activeChildId, $parentId]) ?? $myChildren[0];

// Generate timetable for selected child
$schedule = get_child_immunization_schedule($activeChild['id']);

// Calculate completion stats
$completedCount = count(array_filter($schedule, fn($s) => $s['status'] === 'Vaccinated'));
$totalCount = count($schedule);
$percentComplete = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

$pageTitle = "Vaccination Schedule - " . htmlspecialchars($activeChild['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar  -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Personalized Immunization Schedule</h1>
        <p class="page-subtitle">Automatic due dates calculated from birth date based on WHO pediatric guidelines.</p>
    </div>
    
    <!-- Child Selector Pills -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="small text-muted fw-bold me-1">Select Child:</span>
        <?php foreach ($myChildren as $c): ?>
            <a href="<?= BASE_URL ?>/parent/schedule.php?child_id=<?= $c['id'] ?>" class="btn btn-sm <?= (int)$c['id'] === (int)$activeChild['id'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                <i class="fa-solid fa-baby me-1"></i> <?= htmlspecialchars($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Active Child Hero Dossier Card -->
<div class="card-healthcare p-4 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-3">
                <div class="table-avatar table-avatar-green fs-3" style="width: 58px; height: 58px; border-radius: 1rem;">
                    <i class="fa-solid fa-child"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($activeChild['name']) ?></h3>
                        <span class="badge <?= $activeChild['gender'] === 'Male' ? 'bg-info-subtle text-info border' : 'bg-danger-subtle text-danger border' ?>">
                            <?= htmlspecialchars($activeChild['gender']) ?>
                        </span>
                        <?php if ($activeChild['blood_group']): ?>
                            <span class="badge bg-danger-subtle text-danger border">
                                <i class="fa-solid fa-droplet me-1"></i><?= htmlspecialchars($activeChild['blood_group']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-0 mt-1">
                        Date of Birth: <strong class="text-dark"><?= format_date($activeChild['dob']) ?></strong> &bull;
                        Current Age: <strong class="text-success"><?= calculate_age($activeChild['dob']) ?></strong>
                    </p>
                    <?php if ($activeChild['allergies']): ?>
                        <small class="text-danger fw-semibold d-block mt-1">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Allergies: <?= htmlspecialchars($activeChild['allergies']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Progress & Card Button -->
        <div class="col-lg-5">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-lg-end gap-3 text-center text-sm-start">
                <div>
                    <div class="small fw-bold text-muted text-uppercase">Immunization Progress</div>
                    <div class="fs-4 fw-black text-success"><?= $completedCount ?> / <?= $totalCount ?> Doses (<?= $percentComplete ?>%)</div>
                    <div class="progress mt-1" style="height: 8px; width: 180px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentComplete ?>%" aria-valuenow="<?= $percentComplete ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/parent/certificate.php?child_id=<?= $activeChild['id'] ?>" class="btn btn-dark">
                    <i class="fa-solid fa-id-card me-1"></i> Digital Card
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Immunization Timeline Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Milestone Age</th>
                    <th>Due Date</th>
                    <th>Vaccine Name</th>
                    <th>Disease Protected</th>
                    <th>Status</th>
                    <th>Administered Record</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule as $item): ?>
                    <tr class="<?= in_array($item['status'], ['Due Now', 'Overdue']) ? 'bg-warning-subtle' : '' ?>">
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['recommended_age']) ?></span>
                        </td>
                        <td>
                            <span class="font-monospace fw-bold text-dark"><?= format_date($item['due_date']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars($item['vaccine_name']) ?></div>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($item['disease']) ?></small>
                        </td>
                        <td>
                            <?= render_status_badge($item['status']) ?>
                        </td>
                        <td>
                            <?php if ($item['record']): ?>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($item['record']['hospital_name'] ?? 'Partner Hospital') ?></div>
                                <small class="text-muted font-monospace d-block">Lot: <?= htmlspecialchars($item['record']['batch_number'] ?: 'Verified') ?> &bull; <?= format_date($item['record']['vaccination_date']) ?></small>
                            <?php elseif ($item['appointment']): ?>
                                <div class="text-primary fw-medium small"><?= htmlspecialchars($item['appointment']['hospital_name']) ?></div>
                                <small class="text-muted d-block"><?= format_date($item['appointment']['requested_date']) ?> (<?= htmlspecialchars($item['appointment']['status']) ?>)</small>
                            <?php else: ?>
                                <small class="text-muted fst-italic">Not yet administered</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($item['status'] === 'Vaccinated'): ?>
                                <span class="badge bg-success-subtle text-success border">
                                    <i class="fa-solid fa-circle-check me-1"></i> Complete
                                </span>
                            <?php elseif (in_array($item['status'], ['Approved', 'Pending Approval'])): ?>
                                <a href="<?= BASE_URL ?>/parent/bookings.php" class="btn btn-outline-primary btn-sm py-1 px-2">
                                    View Slot
                                </a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/parent/book.php?child_id=<?= $activeChild['id'] ?>&vaccine_id=<?= $item['vaccine_id'] ?>" class="btn btn-success btn-sm py-1 px-2">
                                    <i class="fa-solid fa-calendar-plus me-1"></i> Book
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
