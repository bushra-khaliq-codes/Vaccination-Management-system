<?php
/*
 * Administrator Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Fetch dashboard 
$totalChildren = db_fetch_one("SELECT COUNT(*) as cnt FROM children")['cnt'] ?? 0;
$totalHospitals = db_fetch_one("SELECT COUNT(*) as cnt FROM hospitals WHERE status = 'active'")['cnt'] ?? 0;
$pendingRequests = db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")['cnt'] ?? 0;
$totalVaccinated = db_fetch_one("SELECT COUNT(*) as cnt FROM vaccination_records WHERE status = 'Vaccinated'")['cnt'] ?? 0;
$availableVaccines = db_fetch_one("SELECT COUNT(*) as cnt FROM vaccines WHERE status = 'available'")['cnt'] ?? 0;

// Fetch pending parent requests needing Admin approval
$pendingBookings = db_fetch_all("
    SELECT a.*, c.name as child_name, c.dob as child_dob, 
           u.name as parent_name, u.phone as parent_phone,
           v.name as vaccine_name, h.name as hospital_name
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN users u ON a.parent_id = u.id
    JOIN vaccines v ON a.vaccine_id = v.id
    JOIN hospitals h ON a.hospital_id = h.id
    WHERE a.status = 'pending'
    ORDER BY a.requested_date ASC, a.created_at ASC
    LIMIT 5
");

// Fetch recent completed vaccinations
$recentVaccinations = db_fetch_all("
    SELECT vr.*, c.name as child_name, v.name as vaccine_name, h.name as hospital_name
    FROM vaccination_records vr
    JOIN children c ON vr.child_id = c.id
    JOIN vaccines v ON vr.vaccine_id = v.id
    JOIN hospitals h ON vr.hospital_id = h.id
    ORDER BY vr.created_at DESC
    LIMIT 5
");

$pageTitle = "Administrator Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Admin Dashboard Greeting & Header -->
<div class="admin-header-banner">
    <div>
        <span class="admin-header-badge">
            <i class="fa-solid fa-shield-halved"></i> Master Management Console
        </span>
        <h1 class="admin-title">Administrator Health Management Console</h1>
        <p class="admin-subtitle">Central coordination for child profiles, parent requests, hospital partners, and immunization tracking.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-purple">
            <i class="fa-solid fa-list-check"></i> Review Requests (<?= $pendingRequests ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-light">
            <i class="fa-solid fa-file-export"></i> Reports
        </a>
    </div>
</div>

<!-- Key Performance Indicators (Grid) -->
<div class="row g-3 mb-4">
    <!-- Registered Children -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="metric-card metric-purple">
            <div class="metric-header">
                <span class="metric-label">Registered Children</span>
                <span class="metric-icon"><i class="fa-solid fa-children"></i></span>
            </div>
            <div class="metric-value"><?= $totalChildren ?></div>
            <a href="<?= BASE_URL ?>/admin/children.php" class="metric-link">
                View all profiles <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="metric-card metric-amber">
            <div class="metric-header">
                <span class="metric-label">Pending Requests</span>
                <span class="metric-icon"><i class="fa-solid fa-clock"></i></span>
            </div>
            <div class="metric-value text-warning"><?= $pendingRequests ?></div>
            <a href="<?= BASE_URL ?>/admin/bookings.php?status=pending" class="metric-link">
                Needs approval <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Vaccinated -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="metric-card metric-emerald">
            <div class="metric-header">
                <span class="metric-label">Total Vaccinated</span>
                <span class="metric-icon"><i class="fa-solid fa-syringe"></i></span>
            </div>
            <div class="metric-value text-success"><?= $totalVaccinated ?></div>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="metric-link">
                Date-wise reports <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Active Hospitals -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="metric-card metric-indigo">
            <div class="metric-header">
                <span class="metric-label">Active Hospitals</span>
                <span class="metric-icon"><i class="fa-solid fa-hospital"></i></span>
            </div>
            <div class="metric-value"><?= $totalHospitals ?></div>
            <a href="<?= BASE_URL ?>/admin/hospitals.php" class="metric-link">
                Manage facilities <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Vaccines Listed -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label">Vaccines Listed</span>
                <span class="metric-icon"><i class="fa-solid fa-vial"></i></span>
            </div>
            <div class="metric-value"><?= $availableVaccines ?> <small class="fs-6 text-muted fw-normal">active</small></div>
            <a href="<?= BASE_URL ?>/admin/vaccines.php" class="metric-link">
                Check inventory <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="row g-4">
    
    <!-- Left Column -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Pending Parent Requests</h5>
                    <small class="text-muted">Parent appointment submissions awaiting Admin verification</small>
                </div>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-primary btn-sm">
                    View All Requests <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <?php if (empty($pendingBookings)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon text-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h6 class="empty-state-title">No Pending Requests</h6>
                        <p class="empty-state-text">All received booking requests have been reviewed and processed.</p>
                    </div>
                <?php else: ?>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Child &amp; Guardian</th>
                                <th>Vaccine</th>
                                <th>Hospital</th>
                                <th>Date &amp; Slot</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBookings as $b): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($b['booking_reference']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['child_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['parent_name']) ?> (<?= htmlspecialchars($b['parent_phone']) ?>)</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border"><?= htmlspecialchars($b['vaccine_name']) ?></span>
                                    </td>
                                    <td>
                                        <small class="fw-semibold text-secondary"><?= htmlspecialchars($b['hospital_name']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= format_date($b['requested_date']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['requested_time_slot']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Approve Form -->
                                            <form method="POST" action="<?= BASE_URL ?>/admin/bookings.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="btn-action-approve" title="Approve Request">
                                                    <i class="fa-solid fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <!-- Reject Link -->
                                            <a href="<?= BASE_URL ?>/admin/bookings.php?reject_id=<?= $b['id'] ?>" class="btn-action-reject" title="Reject Request">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        
        <!-- Recent Completed Doses Feed -->
        <div class="card-healthcare p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-shield-virus text-success me-1"></i> Recent Vaccinations
                </h6>
                <span class="badge bg-success-subtle text-success">Live Feed</span>
            </div>

            <?php if (empty($recentVaccinations)): ?>
                <p class="text-muted small text-center py-3 mb-0">No vaccination records entered yet.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($recentVaccinations as $r): ?>
                        <div class="p-2 rounded-3 bg-light border">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($r['child_name']) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= format_date($r['vaccination_date'], 'M d') ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <span class="text-primary small fw-semibold"><?= htmlspecialchars($r['vaccine_name']) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-hospital me-1"></i><?= htmlspecialchars($r['hospital_name']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Admin Quick Operations -->
        <div class="card-healthcare p-3">
            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                <i class="fa-solid fa-bolt text-warning me-1"></i> Quick Operations
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>/admin/hospitals.php?action=add" class="quick-action-card">
                    <div class="quick-action-icon text-primary">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Add Hospital</div>
                        <p class="quick-action-subtitle">Register a new healthcare facility</p>
                    </div>
                </a>

                <a href="<?= BASE_URL ?>/admin/vaccines.php?action=add" class="quick-action-card">
                    <div class="quick-action-icon text-success">
                        <i class="fa-solid fa-vial"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Add Vaccine</div>
                        <p class="quick-action-subtitle">Expand immunization catalog</p>
                    </div>
                </a>

                <a href="<?= BASE_URL ?>/admin/schedule.php" class="quick-action-card">
                    <div class="quick-action-icon text-purple">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Upcoming Schedule</div>
                        <p class="quick-action-subtitle">View impending doses across all children</p>
                    </div>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
