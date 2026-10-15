<?php

/*
 * Global Header Component
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = current_user();
$unreadCount = $user ? get_unread_notifications_count($user['id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?><?= APP_NAME ?></title>

    <!-- logo -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/safe-immune-logo.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core & Modular Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tables.css">

    <?php if ($currentPage === 'login.php' || $currentPage === 'register.php'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <?php elseif ($currentDir === 'admin'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <?php elseif ($currentDir === 'parent'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/parent.css">
    <?php elseif ($currentDir === 'hospital'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/hospital.css">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

    <!-- Primary Responsive Navbar -->
    <div class="navbar-shell">
        <nav class="navbar navbar-expand-xl site-navbar">
            <div class="container">

                <!-- Logo & Header -->
                <a class="navbar-brand-custom" href="<?= BASE_URL ?>/index.php">
                    <img src="<?= BASE_URL ?>/assets/images/Safeimmunelogo.png" alt="Safe Immune Logo" class="navbar-brand-logo">
                    <div>
                        <span class="brand-text-title" style="color: #007bff;">Safe <span style="color: #16a34a;">Immune</span></span>
                    </div>
                </a>

                <!-- Mobile Toggler -->
        <button class="navbar-toggler navbar-toggler-custom" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars text-secondary"></i>
        </button>

        <!-- Navbar Menu & Actions -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                <?php if (!$user): ?>
                    <!-- Guest Navigation Links -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php" class="nav-link nav-link-custom <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                            <i class="fa-solid fa-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/login.php?role=parent" class="nav-link nav-link-custom">
                            <i class="fa-solid fa-baby"></i> Parents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/login.php?role=hospital" class="nav-link nav-link-custom">
                            <i class="fa-solid fa-hospital"></i> Hospitals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/login.php?role=admin" class="nav-link nav-link-custom">
                            <i class="fa-solid fa-user-shield"></i> Admin
                        </a>
                    </li>

                <?php elseif ($user['role'] === 'admin'): ?>
                    <!-- Administrator Portal Navigation -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link nav-link-custom <?= $currentPage === 'dashboard.php' && $currentDir === 'admin' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-gauge"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/children.php" class="nav-link nav-link-custom <?= $currentPage === 'children.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-children"></i> All Children
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/bookings.php" class="nav-link nav-link-custom <?= $currentPage === 'bookings.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-calendar-check"></i> Bookings & Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/vaccines.php" class="nav-link nav-link-custom <?= $currentPage === 'vaccines.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-vial"></i> Vaccines
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/hospitals.php" class="nav-link nav-link-custom <?= $currentPage === 'hospitals.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-hospital"></i> Hospitals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/schedule.php" class="nav-link nav-link-custom <?= $currentPage === 'schedule.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-regular fa-clock"></i> Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link nav-link-custom <?= $currentPage === 'reports.php' ? 'active nav-active-admin' : '' ?>">
                            <i class="fa-solid fa-chart-column"></i> Reports
                        </a>
                    </li>

                <?php elseif ($user['role'] === 'parent'): ?>
                    <!-- Parent / Guardian Navigation -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/dashboard.php" class="nav-link nav-link-custom <?= $currentPage === 'dashboard.php' && $currentDir === 'parent' ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/children.php" class="nav-link nav-link-custom <?= $currentPage === 'children.php' ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-baby"></i> Child Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/book.php" class="nav-link nav-link-custom <?= $currentPage === 'book.php' ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-calendar-plus"></i> Book Hospital
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/bookings.php" class="nav-link nav-link-custom <?= $currentPage === 'bookings.php' ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-list-check"></i> My Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/schedule.php" class="nav-link nav-link-custom <?= $currentPage === 'schedule.php' ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-calendar-days"></i> Vaccination Dates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/parent/reports.php" class="nav-link nav-link-custom <?= ($currentPage === 'reports.php' || $currentPage === 'certificate.php') ? 'active nav-active-parent' : '' ?>">
                            <i class="fa-solid fa-file-medical"></i> History & Card
                        </a>
                    </li>

                <?php elseif ($user['role'] === 'hospital'): ?>
                    <!-- Hospital Facility Navigation -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/hospital/dashboard.php" class="nav-link nav-link-custom <?= $currentPage === 'dashboard.php' && $currentDir === 'hospital' ? 'active nav-active-hospital' : '' ?>">
                            <i class="fa-solid fa-hospital-user"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/hospital/appointments.php" class="nav-link nav-link-custom <?= $currentPage === 'appointments.php' ? 'active nav-active-hospital' : '' ?>">
                            <i class="fa-solid fa-clipboard-list"></i> Appointments Received
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/hospital/update_status.php" class="nav-link nav-link-custom <?= $currentPage === 'update_status.php' ? 'active nav-active-hospital' : '' ?>">
                            <i class="fa-solid fa-square-check"></i> Update Vaccine Status
                        </a>
                    </li>
                   
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/hospital/profile.php" class="nav-link nav-link-custom <?= $currentPage === 'profile.php' ? 'active nav-active-hospital' : '' ?>">
                                    <i class="fa-solid fa-building-circle-check"></i> Facility Profile
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- User Auth -->
                    <div class="user-menu-wrapper d-flex align-items-center gap-2 mt-3 mt-xl-0">
                        <?php if (!$user): ?>
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary btn-sm px-3">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm px-3">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                        <?php else: ?>
                            <div class="dropdown">
                                <button class="user-profile-badge dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account details">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end user-account-dropdown">
                                    <div class="user-account-name"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="user-account-email"><?= htmlspecialchars($user['email']) ?></div>
                                    <?php if ($user['role'] === 'parent'): ?>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/parent/profile.php">Manage Profile</a>
                                    <?php elseif ($user['role'] === 'hospital'): ?>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/hospital/profile.php">Facility Profile</a>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/admin/profile.php">Admin Profile
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Logout Button -->
                            <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" title="Sign Out">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

      

    <!-- Main Application Page Wrapper -->
    <main class="page-wrapper">
        <div class="container">
            <!-- System Flash Notifications -->
            <div class="flash-container">
                <?= render_flash() ?>
            </div>