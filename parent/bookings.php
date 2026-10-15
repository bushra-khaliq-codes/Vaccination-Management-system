<?php
/*
 * Parent - Track Appointment Requests & Status Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];
$statusFilter = $_GET['status'] ?? 'all';

// Handle Cancel Request (if pending)
if (isset($_GET['cancel_id'])) {
    $cancelId = (int)$_GET['cancel_id'];
    $apt = db_fetch_one("SELECT * FROM appointments WHERE id = ? AND parent_id = ? AND status = 'pending'", [$cancelId, $parentId]);
    if ($apt) {
        db_execute("DELETE FROM appointments WHERE id = ?", [$cancelId]);
        set_flash('success', "Appointment request {$apt['booking_reference']} cancelled.");
    } else {
        set_flash('error', "Cannot cancel this appointment (it may have already been approved or processed).");
    }
    header("Location: " . BASE_URL . "/parent/bookings.php");
    exit;
}

// Fetch requests
$sql = "
    SELECT a.*, c.name as child_name, c.dob as child_dob,
           v.name as vaccine_name, v.disease_prevented,
           h.name as hospital_name, h.location as hospital_location, h.phone as hospital_phone
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN vaccines v ON a.vaccine_id = v.id
    JOIN hospitals h ON a.hospital_id = h.id
    WHERE a.parent_id = ?
";
$params = [$parentId];

if ($statusFilter !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY a.requested_date DESC, a.id DESC";

$bookings = db_fetch_all($sql, $params);

$pageTitle = "My Appointment Requests - Parent Portal";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Hospital Appointment Requests</h1>
        <p class="page-subtitle">Track the approval and administration status of your child's vaccination bookings.</p>
    </div>
    <a href="<?= BASE_URL ?>/parent/book.php" class="btn btn-success">
        <i class="fa-solid fa-plus me-1"></i> New Booking Request
    </a>
</div>

<!-- Status Filter Pills -->
<div class="filter-pills-bar">
    <a href="<?= BASE_URL ?>/parent/bookings.php?status=all" class="filter-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">
        All Requests
    </a>
    <a href="<?= BASE_URL ?>/parent/bookings.php?status=pending" class="filter-pill pill-pending <?= $statusFilter === 'pending' ? 'active' : '' ?>">
        <i class="fa-solid fa-clock text-warning"></i> Pending Approval
    </a>
    <a href="<?= BASE_URL ?>/parent/bookings.php?status=approved" class="filter-pill pill-approved <?= $statusFilter === 'approved' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-check text-primary"></i> Approved by Admin
    </a>
    <a href="<?= BASE_URL ?>/parent/bookings.php?status=completed" class="filter-pill pill-completed <?= $statusFilter === 'completed' ? 'active' : '' ?>">
        <i class="fa-solid fa-shield-virus text-success"></i> Completed / Vaccinated
    </a>
    <a href="<?= BASE_URL ?>/parent/bookings.php?status=rejected" class="filter-pill pill-rejected <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-xmark text-danger"></i> Rejected
    </a>
</div>

<!-- Bookings Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Child Name</th>
                    <th>Vaccine Requested</th>
                    <th>Hospital Center</th>
                    <th>Schedule Requested</th>
                    <th>Current Status</th>
                    <th>Clinical / Admin Remarks</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                </div>
                                <h6 class="empty-state-title">No Booking Requests Found</h6>
                                <p class="empty-state-text">You have not submitted any appointment requests under this status.</p>
                                <a href="<?= BASE_URL ?>/parent/book.php" class="btn btn-success btn-sm mt-2">
                                    <i class="fa-solid fa-plus me-1"></i> Book New Appointment
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($b['booking_reference']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($b['child_name']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($b['vaccine_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($b['disease_prevented']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($b['hospital_name']) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($b['hospital_phone']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= format_date($b['requested_date']) ?></div>
                                <small class="badge bg-light text-secondary border"><?= htmlspecialchars($b['requested_time_slot']) ?></small>
                            </td>
                            <td>
                                <?= render_status_badge($b['status']) ?>
                            </td>
                            <td>
                                <?php if ($b['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border d-block mb-1 text-wrap" style="max-width: 180px;">
                                        Reason: <?= htmlspecialchars($b['rejection_reason'] ?: 'Not specified') ?>
                                    </span>
                                <?php endif; ?>
                                <small class="text-muted d-block" style="max-width: 180px;"><?= htmlspecialchars($b['admin_notes'] ?: 'No notes') ?></small>
                                <?php if ($b['hospital_remarks']): ?>
                                    <small class="text-success d-block">
                                        <i class="fa-solid fa-notes-medical me-1"></i><?= htmlspecialchars($b['hospital_remarks']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <a href="<?= BASE_URL ?>/parent/bookings.php?cancel_id=<?= $b['id'] ?>" onclick="return confirm('Cancel this appointment request?');" class="btn btn-outline-danger btn-sm py-1 px-2">
                                        Cancel
                                    </a>
                                <?php elseif ($b['status'] === 'completed'): ?>
                                    <a href="<?= BASE_URL ?>/parent/certificate.php?child_id=<?= $b['child_id'] ?>" class="btn btn-outline-success btn-sm py-1 px-2">
                                        <i class="fa-solid fa-id-card me-1"></i> Card
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Awaiting Visit</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
