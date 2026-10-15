<?php
/*
 * Administrator - Parent Requests & Booking Management Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$filterStatus = $_GET['status'] ?? 'all';
$rejectBookingId = isset($_GET['reject_id']) ? (int)$_GET['reject_id'] : null;

// Handle Form Submissions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
        header("Location: " . BASE_URL . "/admin/bookings.php");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $adminNotes = sanitize($_POST['admin_notes'] ?? '');

    $booking = db_fetch_one("
        SELECT a.*, c.name as child_name, u.id as parent_user_id, u.name as parent_name, 
               v.name as vaccine_name, h.name as hospital_name
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN users u ON a.parent_id = u.id
        JOIN vaccines v ON a.vaccine_id = v.id
        JOIN hospitals h ON a.hospital_id = h.id
        WHERE a.id = ?
    ", [$bookingId]);

    if (!$booking) {
        set_flash('error', "Appointment record not found.");
        header("Location: " . BASE_URL . "/admin/bookings.php");
        exit;
    }

    if ($action === 'approve') {
        db_execute("
            UPDATE appointments 
            SET status = 'approved', admin_notes = ?, approved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ", [$adminNotes ?: 'Approved by Administrator. Transmitted to assigned hospital.', $bookingId]);

        // Send Notification to Parent
        db_execute("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'success')
        ", [
            $booking['parent_user_id'],
            'Appointment Approved!',
            "Your appointment {$booking['booking_reference']} for {$booking['child_name']} ({$booking['vaccine_name']}) at {$booking['hospital_name']} has been approved for " . format_date($booking['requested_date']) . "."
        ]);

        set_flash('success', "Appointment {$booking['booking_reference']} approved successfully and routed to {$booking['hospital_name']}.");
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['rejection_reason'] ?? 'Requested schedule slot unavailable or invalid profile info.');
        db_execute("
            UPDATE appointments 
            SET status = 'rejected', rejection_reason = ?, admin_notes = ?
            WHERE id = ?
        ", [$reason, $adminNotes, $bookingId]);

        // Send Notification to Parent
        db_execute("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'warning')
        ", [
            $booking['parent_user_id'],
            'Appointment Request Declined',
            "Your appointment {$booking['booking_reference']} for {$booking['child_name']} could not be approved. Reason: {$reason}"
        ]);

        set_flash('warning', "Appointment {$booking['booking_reference']} marked as Rejected.");
    }

    header("Location: " . BASE_URL . "/admin/bookings.php?status=" . urlencode($filterStatus));
    exit;
}

// Build query with filter
$sql = "
    SELECT a.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender,
           u.name as parent_name, u.phone as parent_phone, u.email as parent_email,
           v.name as vaccine_name, v.disease_prevented,
           h.name as hospital_name, h.city as hospital_city
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN users u ON a.parent_id = u.id
    JOIN vaccines v ON a.vaccine_id = v.id
    JOIN hospitals h ON a.hospital_id = h.id
";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " WHERE a.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY a.requested_date DESC, a.id DESC";

$bookings = db_fetch_all($sql, $params);

// Counts by status
$counts = [
    'all' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments")['cnt'] ?? 0,
    'pending' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")['cnt'] ?? 0,
    'approved' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'approved'")['cnt'] ?? 0,
    'completed' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'completed'")['cnt'] ?? 0,
    'rejected' => db_fetch_one("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'rejected'")['cnt'] ?? 0
];

$pageTitle = "Parent Requests & Bookings - Administrator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Parent Requests &amp; Booking Management</h1>
        <p class="page-subtitle">Review appointment submissions from parents. Approve slots to route to hospitals or reject with notes.</p>
    </div>
    <div class="search-box-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" data-table-search="bookingsTable" placeholder="Search ref, child, parent, hospital..." class="form-control">
    </div>
</div>

<!-- Status Filter Pills -->
<div class="filter-pills-bar">
    <a href="<?= BASE_URL ?>/admin/bookings.php?status=all" class="filter-pill <?= $filterStatus === 'all' ? 'active' : '' ?>">
        All Bookings (<?= $counts['all'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/bookings.php?status=pending" class="filter-pill pill-pending <?= $filterStatus === 'pending' ? 'active' : '' ?>">
        <i class="fa-solid fa-clock text-warning"></i> Pending Approval (<?= $counts['pending'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/bookings.php?status=approved" class="filter-pill pill-approved <?= $filterStatus === 'approved' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-check text-primary"></i> Approved (<?= $counts['approved'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/bookings.php?status=completed" class="filter-pill pill-completed <?= $filterStatus === 'completed' ? 'active' : '' ?>">
        <i class="fa-solid fa-shield-virus text-success"></i> Completed (<?= $counts['completed'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/bookings.php?status=rejected" class="filter-pill pill-rejected <?= $filterStatus === 'rejected' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-xmark text-danger"></i> Rejected (<?= $counts['rejected'] ?>)
    </a>
</div>

<!-- Rejection Reason Card if reject_id was clicked -->
<?php if ($rejectBookingId): 
    $targetToReject = db_fetch_one("SELECT a.*, c.name as child_name, v.name as vaccine_name FROM appointments a JOIN children c ON a.child_id = c.id JOIN vaccines v ON a.vaccine_id = v.id WHERE a.id = ?", [$rejectBookingId]);
?>
    <?php if ($targetToReject): ?>
        <div class="card-healthcare p-4 mb-4 border-danger">
            <div class="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3">
                <h5 class="fw-bold text-danger mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Decline Appointment Request: <?= htmlspecialchars($targetToReject['booking_reference']) ?>
                </h5>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-close" aria-label="Close"></a>
            </div>
            <p class="text-muted small mb-3">
                Child: <strong><?= htmlspecialchars($targetToReject['child_name']) ?></strong> &bull; Vaccine: <strong><?= htmlspecialchars($targetToReject['vaccine_name']) ?></strong>.<br>
                Please provide an explanatory reason to inform the parent:
            </p>
            <form method="POST" action="<?= BASE_URL ?>/admin/bookings.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="booking_id" value="<?= $rejectBookingId ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label-custom">Reason for Rejection <span class="text-danger">*</span></label>
                        <select name="rejection_reason" class="form-select">
                            <option value="Hospital facility at maximum capacity on requested date.">Hospital facility at maximum capacity on requested date.</option>
                            <option value="Vaccine temporarily out of stock for this age category.">Vaccine temporarily out of stock for this age category.</option>
                            <option value="Child does not meet minimum age threshold for this vaccine dose.">Child does not meet minimum age threshold for this vaccine dose.</option>
                            <option value="Incorrect date slot requested outside of facility operating hours.">Incorrect date slot requested outside of facility operating hours.</option>
                            <option value="Other administrative reason.">Other administrative reason.</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Additional Administrator Notes</label>
                        <textarea name="admin_notes" rows="2" placeholder="e.g. Please choose another date or nearby hospital..." class="form-control"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-xmark me-1"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Bookings Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table id="bookingsTable" class="table-custom">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Child Details</th>
                    <th>Parent / Guardian</th>
                    <th>Vaccine Requested</th>
                    <th>Hospital &amp; Schedule</th>
                    <th>Status</th>
                    <th>Admin Notes</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <h6 class="empty-state-title">No Appointment Requests Found</h6>
                                <p class="empty-state-text">No appointment bookings match the selected status filter.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <span class="font-monospace fw-bold text-dark d-block"><?= htmlspecialchars($b['booking_reference']) ?></span>
                                <small class="text-muted"><?= format_date($b['created_at'], 'M d, Y') ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($b['child_name']) ?></div>
                                <small class="text-muted"><?= calculate_age($b['child_dob']) ?> &bull; <?= htmlspecialchars($b['child_gender']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($b['parent_name']) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($b['parent_phone']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($b['vaccine_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($b['disease_prevented']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($b['hospital_name']) ?></div>
                                <small class="text-muted d-block"><i class="fa-regular fa-calendar me-1"></i><?= format_date($b['requested_date']) ?></small>
                                <small class="badge bg-light text-secondary border"><?= htmlspecialchars($b['requested_time_slot']) ?></small>
                            </td>
                            <td>
                                <?= render_status_badge($b['status']) ?>
                            </td>
                            <td>
                                <?php if ($b['status'] === 'rejected' && $b['rejection_reason']): ?>
                                    <span class="badge bg-danger-subtle text-danger border d-block mb-1 text-wrap" style="max-width: 180px;">
                                        Reason: <?= htmlspecialchars($b['rejection_reason']) ?>
                                    </span>
                                <?php endif; ?>
                                <small class="text-muted d-block" style="max-width: 180px;"><?= htmlspecialchars($b['admin_notes'] ?: '—') ?></small>
                                <?php if ($b['completed_at']): ?>
                                    <small class="text-success d-block">
                                        <i class="fa-solid fa-circle-check me-1"></i>Vaccinated on <?= format_date($b['completed_at']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <div class="d-inline-flex gap-1">
                                        <!-- Approve Button Form -->
                                        <form method="POST" action="<?= BASE_URL ?>/admin/bookings.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn-action-approve" title="Approve Request">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <!-- Reject Trigger -->
                                        <a href="<?= BASE_URL ?>/admin/bookings.php?reject_id=<?= $b['id'] ?>" class="btn-action-reject" title="Reject Request">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Processed</span>
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
