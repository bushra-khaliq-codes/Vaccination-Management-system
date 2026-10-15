<?php
/** 
//  * Hospital - Update Vaccine Status Controller & Clinical Form
//  */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('hospital');

$userId = (int)current_user()['id'];
$hospital = db_fetch_one("SELECT * FROM hospitals WHERE user_id = ?", [$userId]);
if (!$hospital) {
    $hId = $_SESSION['user']['hospital_id'] ?? 1;
    $hospital = db_fetch_one("SELECT * FROM hospitals WHERE id = ?", [$hId]);
}
$hospitalId = (int)$hospital['id'];

// Selected appointment to update
$selectedBookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$selectedBooking = null;

if ($selectedBookingId > 0) {
    $selectedBooking = db_fetch_one("
        SELECT a.*, c.name as child_name, c.dob as child_dob, c.gender as child_gender, c.blood_group, c.allergies,
               u.id as parent_user_id, u.name as parent_name, u.phone as parent_phone,
               v.name as vaccine_name, v.disease_prevented, v.recommended_age
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN users u ON a.parent_id = u.id
        JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.id = ? AND a.hospital_id = ?
    ", [$selectedBookingId, $hospitalId]);
}

// Fetch all approved appointments
$availableAppointments = db_fetch_all("
    SELECT a.id, a.booking_reference, a.requested_date, c.name as child_name, v.name as vaccine_name
    FROM appointments a
    JOIN children c ON a.child_id = c.id
    JOIN vaccines v ON a.vaccine_id = v.id
    WHERE a.hospital_id = ? AND a.status = 'approved'
    ORDER BY a.requested_date ASC
", [$hospitalId]);

// Process Status Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', "Security verification failed.");
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $statusChoice = sanitize($_POST['vaccine_status'] ?? 'Vaccinated'); // 'Vaccinated' or 'Not Vaccinated'
        $adminDate = sanitize($_POST['vaccination_date'] ?? date('Y-m-d'));
        $batchNo = sanitize($_POST['batch_number'] ?? '');
        $clinician = sanitize($_POST['doctor_nurse_name'] ?? '');
        $doseNo = (int)($_POST['dose_number'] ?? 1);
        $remarks = sanitize($_POST['remarks'] ?? '');

        // Fetch booking record
        $booking = db_fetch_one("
            SELECT a.*, c.name as child_name, u.id as parent_user_id, v.name as vaccine_name
            FROM appointments a
            JOIN children c ON a.child_id = c.id
            JOIN users u ON a.parent_id = u.id
            JOIN vaccines v ON a.vaccine_id = v.id
            WHERE a.id = ? AND a.hospital_id = ?
        ", [$bookingId, $hospitalId]);

        if (!$booking) {
            set_flash('error', "Invalid appointment selected.");
        } else {
            if ($statusChoice === 'Vaccinated') {
                if (empty($batchNo) || empty($clinician)) {
                    set_flash('error', "Batch / Lot number and Administering Clinician name are required when marking as Vaccinated.");
                } else {
                    // 1. Create record in vaccination_records table
                    db_execute("
                        INSERT INTO vaccination_records (
                            child_id, vaccine_id, hospital_id, appointment_id,
                            vaccination_date, batch_number, doctor_nurse_name, dose_number,
                            status, remarks
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Vaccinated', ?)
                    ", [
                        $booking['child_id'], $booking['vaccine_id'], $hospitalId, $bookingId,
                        $adminDate, $batchNo, $clinician, $doseNo, $remarks
                    ]);

                    // 2. Mark appointment as completed
                    db_execute("
                        UPDATE appointments 
                        SET status = 'completed', completed_at = CURRENT_TIMESTAMP,
                            vaccine_batch_no = ?, administered_by = ?, hospital_remarks = ?
                        WHERE id = ?
                    ", [$batchNo, $clinician, $remarks, $bookingId]);

                    // 3. Decrement vaccine stock quantity
                    db_execute("
                        UPDATE vaccines 
                        SET stock_quantity = GREATEST(0, stock_quantity - 1) 
                        WHERE id = ?
                    ", [$booking['vaccine_id']]);

                    // 4. Send parent notification
                    db_execute("
                        INSERT INTO notifications (user_id, title, message, type)
                        VALUES (?, ?, ?, 'success')
                    ", [
                        $booking['parent_user_id'],
                        "Vaccination Completed ({$booking['vaccine_name']})",
                        "{$booking['child_name']} was successfully vaccinated at {$hospital['name']} on {$adminDate}. Digital Card is updated."
                    ]);

                    set_flash('success', "Vaccination status for {$booking['child_name']} ({$booking['vaccine_name']}) updated to VACCINATED.");
                    header("Location: " . BASE_URL . "/hospital/appointments.php?status=completed");
                    exit;
                }
            } else {
                // Not Vaccinated / Missed
                $notReason = sanitize($_POST['not_vaccinated_reason'] ?? 'Child absent or clinical contraindication');
                $combinedRemarks = "NOT VACCINATED: {$notReason}. Notes: {$remarks}";

                // Record non-administration in records
                db_execute("
                    INSERT INTO vaccination_records (
                        child_id, vaccine_id, hospital_id, appointment_id,
                        vaccination_date, batch_number, doctor_nurse_name, dose_number,
                        status, remarks
                    ) VALUES (?, ?, ?, ?, ?, 'N/A', ?, ?, 'Not Vaccinated', ?)
                ", [
                    $booking['child_id'], $booking['vaccine_id'], $hospitalId, $bookingId,
                    $adminDate, $clinician ?: 'Clinical Staff', $doseNo, $combinedRemarks
                ]);

                // Update appointment to missed
                db_execute("
                    UPDATE appointments 
                    SET status = 'missed', hospital_remarks = ?
                    WHERE id = ?
                ", [$combinedRemarks, $bookingId]);

                // Notify parent
                db_execute("
                    INSERT INTO notifications (user_id, title, message, type)
                    VALUES (?, ?, ?, 'warning')
                ", [
                    $booking['parent_user_id'],
                    "Vaccination Missed / Reschedule Required",
                    "Appointment for {$booking['child_name']} at {$hospital['name']} was marked as Not Vaccinated: {$notReason}."
                ]);

                set_flash('warning', "Appointment marked as NOT VACCINATED ({$notReason}).");
                header("Location: " . BASE_URL . "/hospital/appointments.php?status=missed");
                exit;
            }
        }
    }
}

$pageTitle = "Update Vaccine Status - " . htmlspecialchars($hospital['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-2">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Update Child Vaccination Status</h1>
                    <p class="text-muted small mb-0">
                        SRS Requirement: Record completion of administered doses or document clinical postponement reasons.
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/hospital/appointments.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-arrow-left"></i> Back to Roster
                </a>
            </div>

            <!-- Status Update Form Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="<?= BASE_URL ?>/hospital/update_status.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <!-- Select Appointment -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small text-uppercase">Select Approved Appointment <span class="text-danger">*</span></label>
                            <?php if ($selectedBooking): ?>
                                <input type="hidden" name="booking_id" value="<?= $selectedBooking['id'] ?>">
                                <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom border-primary-subtle">
                                        <span class="font-monospace fw-bold text-primary fs-6"><?= htmlspecialchars($selectedBooking['booking_reference']) ?></span>
                                        <span class="badge bg-light text-dark border fw-semibold">
                                            <i class="fa-regular fa-calendar me-1"></i> <?= format_date($selectedBooking['requested_date']) ?> (<?= htmlspecialchars($selectedBooking['requested_time_slot']) ?>)
                                        </span>
                                    </div>
                                    <div class="row g-2 small">
                                        <div class="col-6">
                                            <span class="text-muted">Child:</span>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($selectedBooking['child_name']) ?></span> (<?= calculate_age($selectedBooking['child_dob']) ?>)
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted">Vaccine:</span>
                                            <span class="fw-bold text-primary"><?= htmlspecialchars($selectedBooking['vaccine_name']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <select name="booking_id" required class="form-select">
                                    <option value="">-- Choose an appointment from the queue --</option>
                                    <?php foreach ($availableAppointments as $apt): ?>
                                        <option value="<?= $apt['id'] ?>">
                                            <?= htmlspecialchars($apt['booking_reference']) ?> &bull; <?= htmlspecialchars($apt['child_name']) ?> &bull; <?= htmlspecialchars($apt['vaccine_name']) ?> (<?= format_date($apt['requested_date']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Choose Outcome Vaccinated vs Not Vaccinated -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small text-uppercase">Clinical Outcome <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="card h-100 p-3 text-center border-2 cursor-pointer shadow-sm status-radio-label" for="statusVaccinated" style="cursor: pointer;">
                                        <div class="form-check form-check-inline d-none">
                                            <input class="form-check-input" type="radio" name="vaccine_status" id="statusVaccinated" value="Vaccinated" checked>
                                        </div>
                                        <div class="my-2">
                                            <i class="fa-solid fa-circle-check fs-2 text-success mb-2"></i>
                                            <h6 class="fw-bold text-dark mb-1">Vaccinated</h6>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Dose successfully administered</small>
                                        </div>
                                    </label>
                                </div>

                                <div class="col-6">
                                    <label class="card h-100 p-3 text-center border-2 cursor-pointer shadow-sm status-radio-label" for="statusNotVaccinated" style="cursor: pointer;">
                                        <div class="form-check form-check-inline d-none">
                                            <input class="form-check-input" type="radio" name="vaccine_status" id="statusNotVaccinated" value="Not Vaccinated">
                                        </div>
                                        <div class="my-2">
                                            <i class="fa-solid fa-circle-xmark fs-2 text-danger mb-2"></i>
                                            <h6 class="fw-bold text-dark mb-1">Not Vaccinated</h6>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Missed, fever, or allergy delay</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Vaccinated Detailed Fields -->
                        <div id="vaccinatedFields" class="mb-4 pt-2">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Vaccination Date Given <span class="text-danger">*</span></label>
                                    <input type="date" name="vaccination_date" value="<?= date('Y-m-d') ?>" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Vaccine Lot / Batch Number <span class="text-danger">*</span></label>
                                    <input type="text" name="batch_number" id="batchNumberInput" value="LOT-<?= strtoupper(substr(uniqid(), -6)) ?>" placeholder="e.g. LOT-2026-9812" class="form-control font-monospace">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Administering Clinician Name <span class="text-danger">*</span></label>
                                    <input type="text" name="doctor_nurse_name" id="clinicianInput" required value="<?= htmlspecialchars(current_user()['name']) ?>" placeholder="e.g. Dr. Alan Vance" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Dose Sequence Number</label>
                                    <input type="number" name="dose_number" value="1" min="1" max="5" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Not Vaccinated Fields -->
                        <div id="notVaccinatedFields" class="mb-4 pt-2 d-none">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-danger">Clinical Reason for Non-Administration <span class="text-danger">*</span></label>
                                <select name="not_vaccinated_reason" class="form-select border-danger">
                                    <option value="Child had acute fever/infection; postponed.">Child had acute fever or active infection (postponed 7 days)</option>
                                    <option value="Patient did not attend appointment (No show).">Patient did not attend scheduled appointment (No Show)</option>
                                    <option value="Known allergic contraindication identified by clinician.">Known allergic contraindication identified by clinician</option>
                                    <option value="Guardian requested postponement.">Guardian requested date postponement</option>
                                </select>
                            </div>
                        </div>

                        <!-- Clinical Observations & Remarks -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-dark">Clinical Remarks & Observations</label>
                            <textarea name="remarks" rows="2" placeholder="e.g. Dose administered smoothly. No immediate adverse reactions noted." class="form-control">Administered standard dose smoothly. Child observed for 15 minutes.</textarea>
                        </div>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <a href="<?= BASE_URL ?>/hospital/appointments.php" class="btn btn-outline-secondary btn-sm">
                                &larr; Cancel
                            </a>
                            <button type="submit" class="btn btn-primary py-2 px-4 fw-semibold shadow-sm">
                                <i class="fa-solid fa-square-check me-1"></i> Commit Status Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
