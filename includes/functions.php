<?php
/*
 * Application Help Functions
 */

require_once __DIR__ . '/db.php';

function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function format_date(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return 'N/A';
    $time = strtotime($date);
    return $time ? date($format, $time) : 'N/A';
}

/*
 * Calculate child age accurately in years, months, or weeks
 */
function calculate_age(string $dob): string {
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $diff = $birthDate->diff($today);

        if ($diff->y > 0) {
            return $diff->y . ' yr' . ($diff->y > 1 ? 's ' : ' ') . ($diff->m > 0 ? $diff->m . ' mo' : '');
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ($diff->d > 0 ? ' ' . $diff->d . ' d' : '');
        } else {
            $days = $diff->days;
            $weeks = floor($days / 7);
            if ($weeks > 0) {
                return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' (' . $days . ' days)';
            }
            return $days . ' day' . ($days > 1 ? 's' : '');
        }
    } catch (Exception $e) {
        return 'Unknown';
    }
}

/*
 * Generate status badge HTML
 */
function render_status_badge(string $status): string {
    $statusLower = strtolower(trim($status));
    $classes = [
        'pending' => 'badge-pending',
        'approved' => 'badge-approved',
        'completed' => 'badge-completed',
        'vaccinated' => 'badge-completed',
        'not vaccinated' => 'badge-rejected',
        'rejected' => 'badge-rejected',
        'missed' => 'badge-overdue',
        'available' => 'badge-available',
        'unavailable' => 'badge-unavailable',
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'due' => 'badge-due',
        'overdue' => 'badge-overdue',
        'upcoming' => 'badge-upcoming'
    ];

    $badgeClass = $classes[$statusLower] ?? 'badge-upcoming';
    $label = ucwords($status);

    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($label) . '</span>';
}

/*
 * Generate unique booking reference code
 */
function generate_booking_ref(): string {
    return 'VAC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
}

/*
 * Set flash alert message 
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/*
 * Display flash alerts 
 */
function render_flash(): string {
    $html = '';
    $types = [
        'success' => ['flash-success', 'CheckCircle'],
        'error' => ['flash-error', 'AlertCircle'],
        'info' => ['flash-info', 'Info'],
        'warning' => ['flash-warning', 'AlertTriangle']
    ];

    foreach ($types as $key => [$css, $icon]) {
        if (!empty($_SESSION['flash_' . $key])) {
            $msg = htmlspecialchars($_SESSION['flash_' . $key]);
            $html .= "
            <div class=\"flash-alert {$css}\" role=\"alert\">
                <div class=\"flash-text\">{$msg}</div>
                <button type=\"button\" onclick=\"this.parentElement.remove()\" class=\"flash-close\">&times;</button>
            </div>";
            unset($_SESSION['flash_' . $key]);
        }
    }

    return $html;
}

function get_child_immunization_schedule(int $childId): array {
    $child = db_fetch_one("SELECT * FROM children WHERE id = ?", [$childId]);
    if (!$child) return [];

    $dobTimestamp = strtotime($child['dob']);
    $todayTimestamp = strtotime('today');

    // Fetch all vaccines
    $vaccines = db_fetch_all("SELECT * FROM vaccines ORDER BY recommended_days_from_birth ASC, id ASC");

    // Fetch all completed records for child
    $records = db_fetch_all("
        SELECT vr.*, h.name as hospital_name 
        FROM vaccination_records vr
        LEFT JOIN hospitals h ON vr.hospital_id = h.id
        WHERE vr.child_id = ?
    ", [$childId]);

    //  records by vaccine id
    $completedMap = [];
    foreach ($records as $r) {
        $completedMap[$r['vaccine_id']] = $r;
    }

    // Fetch active bookings for child
    $activeBookings = db_fetch_all("
        SELECT a.*, h.name as hospital_name, v.name as vaccine_name
        FROM appointments a
        LEFT JOIN hospitals h ON a.hospital_id = h.id
        LEFT JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.child_id = ? AND a.status IN ('pending', 'approved')
    ", [$childId]);

    $bookingMap = [];
    foreach ($activeBookings as $b) {
        $bookingMap[$b['vaccine_id']] = $b;
    }

    $schedule = [];
    foreach ($vaccines as $v) {
        $days = (int)$v['recommended_days_from_birth'];
        $dueDateTimestamp = strtotime("+{$days} days", $dobTimestamp);
        $dueDate = date('Y-m-d', $dueDateTimestamp);

        $item = [
            'vaccine_id' => $v['id'],
            'vaccine_name' => $v['name'],
            'disease' => $v['disease_prevented'],
            'recommended_age' => $v['recommended_age'],
            'due_date' => $dueDate,
            'status' => 'Upcoming',
            'record' => null,
            'appointment' => null
        ];

        if (isset($completedMap[$v['id']])) {
            $item['status'] = $completedMap[$v['id']]['status']; // 'Vaccinated' or 'Not Vaccinated'
            $item['record'] = $completedMap[$v['id']];
        } elseif (isset($bookingMap[$v['id']])) {
            $item['status'] = ($bookingMap[$v['id']]['status'] === 'approved') ? 'Approved' : 'Pending Approval';
            $item['appointment'] = $bookingMap[$v['id']];
        } else {
            // Determine if overdue or due now
            $diffDays = ($todayTimestamp - $dueDateTimestamp) / (60 * 60 * 24);
            if ($diffDays > 14) {
                $item['status'] = 'Overdue';
            } elseif ($diffDays >= -7 && $diffDays <= 14) {
                $item['status'] = 'Due Now';
            } else {
                $item['status'] = 'Upcoming';
            }
        }

        $schedule[] = $item;
    }

    return $schedule;
}

/*
 * Get count of unread notifications for a user
 */
function get_unread_notifications_count(int $userId): int {
    $row = db_fetch_one("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
    return (int)($row['cnt'] ?? 0);
}
