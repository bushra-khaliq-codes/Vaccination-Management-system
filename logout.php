<?php
/*
 * Logout Controller
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();

session_start();
set_flash('info', 'You have been safely signed out.');
header("Location: " . BASE_URL . "/login.php");
exit;
