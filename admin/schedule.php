<?php
/*
 * Administrator - Upcoming Vaccination Schedule for All Children Module
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Handle Sending Automated Reminder Notification to Parent
if (isset($_GET['remind_child_id']) && isset($_GET['vaccine_name'])) {
    $childId = (int)$_GET['remind_child_id'];
    $vaccineName = sanitize($_GET['vaccine_name']);

    $child = db_fetch_one("SELECT c.*, u.id as parent_user_id, u.name as parent_name FROM children c JOIN users u ON c.parent_id = u.id WHERE c.id = ?", [$childId]);
    if ($child) {
        db_execute("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'reminder')
        ", [
            $child['parent_user_id'],
            "Immunization Reminder: {$child['name']}",
            "Dear {$child['parent_name']}, your child {$child['name']} is due for {$vaccineName}. Please book an appointment with a network hospital."
        ]);
        set_flash('success', "Reminder notification dispatched to parent ({$child['parent_name']}).");
    }
    header("Location: " . BASE_URL . "/admin/schedule.php");
    exit;
}

// Fetch all children
$allChildren = db_fetch_all("
    SELECT c.*, u.name as parent_name, u.phone as parent_phone 
    FROM children c 
    JOIN users u ON c.parent_id = u.id 
    ORDER BY c.dob DESC
");

// Build a  schedule of all pending/upcoming doses
$allUpcomingDoses = [];
foreach ($allChildren as $c) {
    $childSchedule = get_child_immunization_schedule($c['id']);
    foreach ($childSchedule as $s) {
        // We only care about pending, approved, due now, overdue, or upcoming
        if ($s['status'] !== 'Vaccinated') {
            $allUpcomingDoses[] = [
                'child_id' => $c['id'],
                'child_name' => $c['name'],
                'child_dob' => $c['dob'],
                'parent_name' => $c['parent_name'],
                'parent_phone' => $c['parent_phone'],
                'vaccine_name' => $s['vaccine_name'],
                'disease' => $s['disease'],
                'recommended_age' => $s['recommended_age'],
                'due_date' => $s['due_date'],
                'status' => $s['status'],
                'appointment' => $s['appointment']
            ];
        }
    }
}

// Sort by due date ascending
usort($allUpcomingDoses, function ($a, $b) {
    return strtotime($a['due_date']) <=> strtotime($b['due_date']);
});

$pageTitle = "Upcoming Vaccination Schedule - All Children";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Upcoming Vaccination Dates (All Children)</h1>
        <p class="page-subtitle">Real-time schedule projection calculated from child birth dates and WHO immunization timetables.</p>
    </div>
    <div class="search-box-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" data-table-search="scheduleTable" placeholder="Filter by child, parent, vaccine..." class="form-control">
    </div>
</div>

<!-- Summary Metric Widgets -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card metric-indigo">
            <div class="metric-header">
                <span class="metric-label">Scheduled Slots</span>
                <span class="metric-icon"><i class="fa-solid fa-calendar-check"></i></span>
            </div>
            <div class="metric-value">
                <?= count(array_filter($allUpcomingDoses, fn($d) => in_array($d['status'], ['Approved', 'Pending Approval']))) ?>
            </div>
            <span class="small text-muted">Awaiting or booked at facility</span>
        </div>
    </div>

    <div class="col-md-4">
        <div class="metric-card metric-amber">
            <div class="metric-header">
                <span class="metric-label">Due Now / Urgent</span>
                <span class="metric-icon"><i class="fa-solid fa-clock"></i></span>
            </div>
            <div class="metric-value text-warning">
                <?= count(array_filter($allUpcomingDoses, fn($d) => $d['status'] === 'Due Now')) ?>
            </div>
            <span class="small text-muted">Infants currently due this week</span>
        </div>
    </div>

    <div class="col-md-4">
        <div class="metric-card metric-rose">
            <div class="metric-header">
                <span class="metric-label">Overdue Doses</span>
                <span class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            </div>
            <div class="metric-value text-danger">
                <?= count(array_filter($allUpcomingDoses, fn($d) => $d['status'] === 'Overdue')) ?>
            </div>
            <span class="small text-muted">Requires immediate reminder alert</span>
        </div>
    </div>
</div>

<!-- Schedule Table Card -->
<div class="table-card">
    <div class="table-responsive">
        <table id="scheduleTable" class="table-custom">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Child Profile</th>
                    <th>Parent / Guardian</th>
                    <th>Vaccine &amp; Target</th>
                    <th>Milestone Age</th>
                    <th>Schedule Status</th>
                    <th class="text-end">Reminder Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allUpcomingDoses)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-box">
                                <div class="empty-state-icon text-muted">
                                    <i class="fa-regular fa-calendar-check"></i>
                                </div>
                                <h6 class="empty-state-title">No Upcoming Schedules</h6>
                                <p class="empty-state-text">No pending vaccination milestones found. Once parents register their children, immunization timelines will appear here.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allUpcomingDoses as $item): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                    <i class="fa-regular fa-calendar-days text-primary"></i>
                                    <?= format_date($item['due_date']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['child_name']) ?></div>
                                <small class="text-muted">Born: <?= format_date($item['child_dob']) ?> (<?= calculate_age($item['child_dob']) ?>)</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($item['parent_name']) ?></div>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($item['parent_phone']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($item['vaccine_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($item['disease']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['recommended_age']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 align-items-start">
                                    <?= render_status_badge($item['status']) ?>
                                    <?php if ($item['appointment']): ?>
                                        <small class="badge bg-light text-secondary border">
                                            <i class="fa-solid fa-hospital me-1"></i><?= htmlspecialchars($item['appointment']['hospital_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/admin/schedule.php?remind_child_id=<?= $item['child_id'] ?>&vaccine_name=<?= urlencode($item['vaccine_name']) ?>" class="btn btn-outline-warning btn-sm py-1 px-2" title="Dispatch notification reminder to parent">
                                    <i class="fa-regular fa-bell me-1"></i> Send Alert
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
