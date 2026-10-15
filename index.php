<?php
/*
 * Vaccination Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If user is not logged in, strictly redirect to Login / Sign Up portal
if (!is_logged_in()) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// If user is logged in, redirect directly to their authorized portal
$userRole = current_user()['role'] ?? 'parent';
header("Location: " . BASE_URL . "/{$userRole}/dashboard.php");
exit;
