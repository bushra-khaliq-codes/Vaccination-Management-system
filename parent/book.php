<?php
/**
 * Parent - Book Hospital Appointment Module
 *
 * SRS Requirement: "Book Hospital: Use can search the list of hospital and book schedule for the vaccine dates"
 * Allows parents to search network hospitals, select their child, choose the required vaccine,
 * select an available date and time slot, and submit the booking request for Admin approval.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('parent');

$parentId = (int)current_user()['id'];

// Check pre-selected parameters
$preChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
$preVaccineId = isset($_GET['vaccine_id']) ? (int)$_GET['vaccine_id'] : 0;
$preHospitalId = isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 0;

// Fetch parent's children
$myChildren = db_fetch_all("SELECT id, name, dob FROM children WHERE parent_id = ? ORDER BY name ASC", [$parentId]);

// If parent has no children, redirect to children creation
if (empty($myChildren)) {
    set_flash('info', "Please register your child profile before booking an appointment.");
    header("Location: " . BASE_URL . "/parent/children.php?action=add");
    exit;
}

// Fetch available vaccines
$vaccines = db_fetch_all("SELECT * FROM vaccines WHERE status = 'available' ORDER BY recommended_days_from_birth ASC, name ASC");

// Fetch active hospitals
$hospitals = db_fetch_all("SELECT * FROM hospitals WHERE status = 'active' ORDER BY city ASC, name ASC");

// Process Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed. Please try again.");
    } else {
        $childId = (int)($_POST['child_id'] ?? 0);
        $vaccineId = (int)($_POST['vaccine_id'] ?? 0);
        $hospitalId = (int)($_POST['hospital_id'] ?? 0);
        $date = sanitize($_POST['requested_date'] ?? '');
        $slot = sanitize($_POST['time_slot'] ?? '');

        // Verify child ownership
        $child = db_fetch_one("SELECT id, name FROM children WHERE id = ? AND parent_id = ?", [$childId, $parentId]);
        $vac = db_fetch_one("SELECT id, name FROM vaccines WHERE id = ? AND status = 'available'", [$vaccineId]);
        $hosp = db_fetch_one("SELECT id, name FROM hospitals WHERE id = ? AND status = 'active'", [$hospitalId]);

        if (!$child || !$vac || !$hosp || empty($date) || empty($slot)) {
            set_flash('error', "Please select a valid child, vaccine, hospital, and appointment schedule.");
        } elseif (strtotime($date) < strtotime('today')) {
            set_flash('error', "Appointment date must be today or in the future.");
        } else {
            $bookingRef = generate_booking_ref();

            // Insert Appointment in 'pending' status for Admin approval
            db_execute("
                INSERT INTO appointments (
                    booking_reference, child_id, vaccine_id, hospital_id, parent_id,
                    requested_date, requested_time_slot, status, admin_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'Submitted by parent. Awaiting Admin approval.')
            ", [$bookingRef, $childId, $vaccineId, $hospitalId, $parentId, $date, $slot]);

            // Notify Parent
            db_execute("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, ?, ?, 'info')
            ", [
                $parentId,
                "Booking Request Submitted ({$bookingRef})",
                "Your request for {$child['name']} ({$vac['name']}) at {$hosp['name']} on {$date} is currently pending Admin approval."
            ]);

            // Notify Admin
            $admin = db_fetch_one("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            if ($admin) {
                db_execute("
                    INSERT INTO notifications (user_id, title, message, type)
                    VALUES (?, ?, ?, 'info')
                ", [
                    $admin['id'],
                    "New Appointment Request ({$bookingRef})",
                    "Parent " . current_user()['name'] . " requested {$vac['name']} for {$child['name']} at {$hosp['name']}."
                ]);
            }

            set_flash('success', "Appointment request {$bookingRef} submitted successfully! The Administrator will review and approve your slot.");
            header("Location: " . BASE_URL . "/parent/bookings.php");
            exit;
        }
    }
}

$pageTitle = "Book Hospital Appointment - Parent Portal";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar -->
<div class="page-header-bar">
    <div>
        <h1 class="page-title">Book Child Vaccination Appointment</h1>
        <p class="page-subtitle">Select your child, choose the required vaccine and partner hospital, and choose a convenient schedule.</p>
    </div>
    <a href="<?= BASE_URL ?>/parent/bookings.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-list-check me-1"></i> View My Requests
    </a>
</div>

<!-- Booking Wizard Form Card -->
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="form-card border-success">
            <form method="POST" action="<?= BASE_URL ?>/parent/book.php" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <!-- Step 1: Select Child -->
                <div class="mb-4">
                    <label class="form-label-custom mb-2">
                        <span class="badge bg-success rounded-circle me-1">1</span> Select Child to Vaccinate <span class="text-danger">*</span>
                    </label>
                    <div class="row g-3">
                        <?php foreach ($myChildren as $mc): ?>
                            <div class="col-md-4 col-sm-6">
                                <label class="w-100 cursor-pointer">
                                    <input type="radio" name="child_id" value="<?= $mc['id'] ?>" required <?= ($preChildId === (int)$mc['id'] || count($myChildren) === 1) ? 'checked' : '' ?> class="btn-check" id="childRadio<?= $mc['id'] ?>">
                                    <div class="btn btn-outline-success w-100 p-3 text-start d-flex align-items-center gap-3 rounded-3 h-100">
                                        <div class="fs-3"><i class="fa-solid fa-baby"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($mc['name']) ?></div>
                                            <small class="text-muted d-block"><?= calculate_age($mc['dob']) ?></small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Select Vaccine -->
                <div class="mb-4">
                    <label class="form-label-custom mb-2">
                        <span class="badge bg-success rounded-circle me-1">2</span> Select Required Vaccine <span class="text-danger">*</span>
                    </label>
                    <?php if (empty($vaccines)): ?>
                        <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>No vaccines currently available in catalog.</div>
                        </div>
                    <?php else: ?>
                        <select name="vaccine_id" required class="form-select py-2">
                            <option value="">-- Choose a vaccine from catalog --</option>
                            <?php foreach ($vaccines as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= $preVaccineId === (int)$v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['recommended_age']) ?>) — Prevents <?= htmlspecialchars($v['disease_prevented']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Step 3: Select Hospital -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label-custom mb-0">
                            <span class="badge bg-success rounded-circle me-1">3</span> Select Partner Hospital / Clinic <span class="text-danger">*</span>
                        </label>
                        <span class="small text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>All centers verified</span>
                    </div>
                    <div class="d-flex flex-column gap-2" style="max-height: 280px; overflow-y: auto; padding-right: 4px;">
                        <?php foreach ($hospitals as $h): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="hospital_id" value="<?= $h['id'] ?>" required <?= $preHospitalId === (int)$h['id'] ? 'checked' : '' ?> class="btn-check" id="hospRadio<?= $h['id'] ?>">
                                <div class="btn btn-outline-secondary w-100 p-3 text-start d-flex align-items-center justify-content-between rounded-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($h['name']) ?></h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($h['location']) ?> (<?= htmlspecialchars($h['city']) ?>)
                                        </p>
                                        <small class="text-muted">
                                            <i class="fa-regular fa-clock me-1"></i>Hours: <?= htmlspecialchars($h['operating_hours']) ?> &bull; Phone: <?= htmlspecialchars($h['phone']) ?>
                                        </small>
                                    </div>
                                    <div class="text-success fs-5"><i class="fa-solid fa-hospital"></i></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 4 & 5: Preferred Date and Time Slot -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label-custom mb-2">
                            <span class="badge bg-success rounded-circle me-1">4</span> Preferred Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="requested_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" class="form-control py-2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom mb-2">
                            <span class="badge bg-success rounded-circle me-1">5</span> Preferred Time Slot <span class="text-danger">*</span>
                        </label>
                        <select name="time_slot" required class="form-select py-2">
                            <option value="09:00 AM - 10:00 AM">09:00 AM - 10:00 AM (Morning)</option>
                            <option value="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM (Morning)</option>
                            <option value="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM (Morning)</option>
                            <option value="02:00 PM - 03:00 PM">02:00 PM - 03:00 PM (Afternoon)</option>
                            <option value="03:00 PM - 04:00 PM">03:00 PM - 04:00 PM (Afternoon)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-top text-center">
                    <button type="submit" class="btn btn-success btn-lg w-100 py-3 shadow-sm">
                        <i class="fa-solid fa-calendar-check me-2"></i> Submit Booking Request for Admin Approval
                    </button>
                    <small class="text-muted d-block mt-2">
                        * Per system protocol, appointment requests are approved by the health administrator before routing to the hospital.
                    </small>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
