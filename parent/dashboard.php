<?php
/*
 * Parent / Guardian Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];

// Fetch parent's children
$children = db_fetch_all("
    SELECT c.*,
           (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id AND vr.status = 'Vaccinated') as completed_doses
    FROM children c
    WHERE c.parent_id = ?
    ORDER BY c.dob DESC
", [$parentId]);

// Fetch recent unread notifications for this parent
$notifications = db_fetch_all("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$parentId]);

// Mark notifications as read
db_execute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$parentId]);

// Fetch upcoming appointments
$upcomingAppointments = db_fetch_all("
    SELECT a.*, c.name as child_name, v.name as vaccine_name, h.name as hospital_name, h.phone as hospital_phone
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN vaccines v ON a.vaccine_id = v.id
    JOIN hospitals h ON a.hospital_id = h.id
    WHERE a.parent_id = ?
    ORDER BY a.requested_date ASC, a.id DESC
    LIMIT 5
", [$parentId]);

// Compute upcoming vaccine alerts across all children
$dueVaccineAlerts = [];
foreach ($children as $c) {
    $schedule = get_child_immunization_schedule($c['id']);
    foreach ($schedule as $s) {
        if (in_array($s['status'], ['Due Now', 'Overdue'])) {
            $dueVaccineAlerts[] = [
                'child_id' => $c['id'],
                'child_name' => $c['name'],
                'vaccine_id' => $s['vaccine_id'],
                'vaccine_name' => $s['vaccine_name'],
                'due_date' => $s['due_date'],
                'status' => $s['status']
            ];
        }
    }
}

$pageTitle = "Parent Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Parent Welcome Banner -->
<div class="parent-welcome-banner">
    <div class="row g-0 align-items-stretch">

        <!-- LEFT -->
        <div class="col-12 col-lg-6 p-4 p-md-5 d-flex flex-column justify-content-center parent-reveal">
            <span class="parent-badge align-self-start">
                <i class="fa-solid fa-heart-pulse"></i> Guardian Health Portal
            </span>
            <h1 class="parent-welcome-title">Welcome back, <?= htmlspecialchars(current_user()['name']) ?>!</h1>
            <p class="parent-welcome-text">
                Keep your infants fully immunized against preventable diseases. Track upcoming milestones, schedule hospital visits, and generate verified digital immunization cards.
            </p>
            <!-- pill buttons side by side -->
            <div class="d-flex flex-wrap gap-3">
                <a href="<?= BASE_URL ?>/parent/book.php" class="btn btn-light fw-bold text-success rounded-pill px-4 py-2 parent-hero-btn">
                    <i class="fa-solid fa-calendar-plus me-1"></i> Book Vaccination Slot
                </a>
                <a href="<?= BASE_URL ?>/parent/children.php?action=add" class="btn btn-outline-light text-success rounded-pill px-4 py-2 parent-hero-btn">
                    <i class="fa-solid fa-plus me-1 text-success"></i> Add Child Profile
                </a>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="col-12 col-lg-6">
            <div class="parent-hero-visual h-100 d-flex align-items-end justify-content-center px-3 pt-4">
                <img src="<?= BASE_URL ?>/assets/images/parentportal.png" alt="Immunization Protection" class="img-fluid parent-hero-img">
            </div>
        </div>

    </div>
</div>

<!-- Active Vaccine Alerts -->
<?php if (!empty($dueVaccineAlerts)): ?>
    <div class="vaccine-alert-banner alert-overdue mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
            <h6 class="fw-bold text-danger mb-0">Immunization Alert: Upcoming or Overdue Vaccines!</h6>
        </div>
        <div class="row g-2 mt-1">
            <?php foreach ($dueVaccineAlerts as $alert): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-white rounded-3 border border-danger-subtle d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold text-dark small"><?= htmlspecialchars($alert['child_name']) ?></div>
                            <div class="text-primary small fw-semibold"><?= htmlspecialchars($alert['vaccine_name']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Due Date: <?= format_date($alert['due_date']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= $alert['status'] === 'Overdue' ? 'bg-danger text-white' : 'bg-warning text-dark' ?> mb-1">
                                <?= $alert['status'] ?>
                            </span><br>
                            <a href="<?= BASE_URL ?>/parent/book.php?child_id=<?= $alert['child_id'] ?>&vaccine_id=<?= $alert['vaccine_id'] ?>" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;">
                                Book Now &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Dashboard Grid -->
<div class="row g-4">
    
    <!-- Left Column-->
    <div class="col-lg-8">
        
        <!-- Registered Children Section -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Your Registered Children</h5>
                    <small class="text-muted">Select child to view timetable, schedule appointments, or print health card</small>
                </div>
                <a href="<?= BASE_URL ?>/parent/children.php" class="btn btn-outline-success btn-sm">
                    Manage Profiles <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if (empty($children)): ?>
                <div class="card-healthcare p-4 text-center">
                    <div class="empty-state-icon text-success">
                        <i class="fa-solid fa-baby"></i>
                    </div>
                    <h6 class="empty-state-title">No Children Registered Yet</h6>
                    <p class="empty-state-text">Add your infant's date of birth so the portal can calculate their personalized immunization schedule.</p>
                    <a href="<?= BASE_URL ?>/parent/children.php?action=add" class="btn btn-success mt-2">
                        <i class="fa-solid fa-plus me-1"></i> Add First Child
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($children as $c): ?>
                        <div class="col-md-6">
                            <div class="child-dossier-card">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="child-avatar-circle">
                                                <i class="fa-solid fa-baby"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($c['name']) ?></h6>
                                                <small class="text-muted">Born <?= format_date($c['dob']) ?></small>
                                            </div>
                                        </div>
                                        <span class="badge <?= $c['gender'] === 'Male' ? 'bg-info-subtle text-info border' : 'bg-danger-subtle text-danger border' ?>">
                                            <?= htmlspecialchars($c['gender']) ?>
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between small py-1 border-bottom">
                                        <span class="text-muted">Current Age:</span>
                                        <span class="fw-bold text-dark"><?= calculate_age($c['dob']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small py-1 mb-3">
                                        <span class="text-muted">Vaccine Progress:</span>
                                        <span class="fw-bold text-success"><i class="fa-solid fa-shield-check me-1"></i><?= (int)$c['completed_doses'] ?> Doses Completed</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/parent/schedule.php?child_id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fa-solid fa-calendar-days me-1"></i> Timetable
                                    </a>
                                    <a href="<?= BASE_URL ?>/parent/certificate.php?child_id=<?= $c['id'] ?>" class="btn btn-outline-success btn-sm flex-fill">
                                        <i class="fa-solid fa-id-card me-1"></i> Digital Card
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Appointment Requests -->
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Hospital Appointment Requests</h5>
                    <small class="text-muted">Track approvals and hospital vaccination appointments</small>
                </div>
                <a href="<?= BASE_URL ?>/parent/bookings.php" class="btn btn-outline-primary btn-sm">
                    View All <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <?php if (empty($upcomingAppointments)): ?>
                    <div class="p-4 text-center text-muted small">
                        No appointment requests booked yet. Click "Book Vaccination Slot" above to schedule with a partner hospital.
                    </div>
                <?php else: ?>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Child</th>
                                <th>Vaccine</th>
                                <th>Hospital Facility</th>
                                <th>Date &amp; Slot</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $apt): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($apt['booking_reference']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($apt['child_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-semibold"><?= htmlspecialchars($apt['vaccine_name']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($apt['hospital_name']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($apt['hospital_phone']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= format_date($apt['requested_date']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($apt['requested_time_slot']) ?></small>
                                    </td>
                                    <td>
                                        <?= render_status_badge($apt['status']) ?>
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
        
        <!-- Alerts & Notification Feed -->
        <div class="card-healthcare p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-regular fa-bell bell-icon text-success me-1"></i> Alerts &amp; Notifications
                </h6>
                <span class="badge bg-success-subtle text-success">Live Sync</span>
            </div>

            <?php if (empty($notifications)): ?>
                <p class="text-muted small text-center py-3 mb-0">No new alerts right now.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($notifications as $n): ?>
                        <div class="p-2 rounded-3 bg-light border">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($n['title']) ?></span>
                                <span class="text-muted" style="font-size: 0.7rem;"><?= format_date($n['created_at'], 'M d') ?></span>
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;"><?= htmlspecialchars($n['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Health Tip Card -->
        <div class="card-healthcare p-3 bg-dark text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
            <span class="badge bg-success mb-2">Pediatric Health Note</span>
            <h6 class="fw-bold mb-2">Timely Immunization Advice</h6>
            <p class="small text-slate-300 mb-3" style="color: #cbd5e1; font-size: 0.8125rem; line-height: 1.5;">
                Completing vaccines according to the WHO schedule during the first 24 months gives infants lifelong immunity against Polio, Hepatitis B, Measles, and Diphtheria.
            </p>
            <a href="<?= BASE_URL ?>/parent/schedule.php" class="text-success text-decoration-none fw-bold small">
                Review Immunization Milestones &rarr;
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
