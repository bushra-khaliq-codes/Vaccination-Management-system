<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// redirect to respective role dashboard
if (is_logged_in()) {
    $role = current_user()['role'];
    header("Location: " . BASE_URL . "/{$role}/dashboard.php");
    exit;
}

$errorMessage = '';
$selectedRole = $_GET['role'] ?? 'parent';

// login form submission strictly with username and password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['username_or_email'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    $selectedRole = $_POST['role_hint'] ?? $selectedRole;

    if (!validate_csrf($token)) {
        $errorMessage = "Security validation failed. Please try again.";
    } elseif (empty($identifier) || empty($password)) {
        $errorMessage = "Please enter both your username/email and password.";
    } else {
        $res = login_user($identifier, $password);
        if ($res['success']) {
            $userRole = $_SESSION['user']['role'];
            header("Location: " . BASE_URL . "/{$userRole}/dashboard.php");
            exit;
        } else {
            $errorMessage = $res['message'];
        }
    }
}

$pageTitle = "Sign In - e-Vaccination Portal";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Main Section -->
<section class="auth-container">
    <div class="auth-card">
        
        <!-- Login Form -->
        <div class="auth-form-side">
            
            <div class="auth-header">
                <div class="auth-header-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Select your account type and sign in to continue</p>
            </div>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2 px-3 rounded-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <div><?= htmlspecialchars($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <!-- Role Selector Tabs -->
            <div class="role-tabs-wrapper">
                <button type="button" onclick="setLoginRoleTab('parent')" id="tabParent" class="role-tab-btn <?= $selectedRole === 'parent' ? 'active tab-parent' : '' ?>">
                    <i class="fa-solid fa-baby"></i> Parent
                </button>
                <button type="button" onclick="setLoginRoleTab('hospital')" id="tabHospital" class="role-tab-btn <?= $selectedRole === 'hospital' ? 'active tab-hospital' : '' ?>">
                    <i class="fa-solid fa-hospital"></i> Hospital
                </button>
                <button type="button" onclick="setLoginRoleTab('admin')" id="tabAdmin" class="role-tab-btn <?= $selectedRole === 'admin' ? 'active tab-admin' : '' ?>">
                    <i class="fa-solid fa-user-shield"></i> Admin
                </button>
            </div>

            <!-- Login Form -->
            <form method="POST" action="<?= BASE_URL ?>/login.php" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="role_hint" id="roleHint" value="<?= htmlspecialchars($selectedRole) ?>">

                <!-- Identifier Field -->
                <div class="mb-3">
                    <label id="inputLabel" class="form-label-custom">Username or Email Address <span class="text-danger">*</span></label>
                    <div class="input-group-auth">
                        <span class="input-icon-box">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" id="userInput" name="username_or_email" required autocomplete="username" placeholder="Enter username or email" class="form-control">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label class="form-label-custom">Password <span class="text-danger">*</span></label>
                    <div class="input-group-auth">
                        <span class="input-icon-box">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="passInput" name="password" required autocomplete="current-password" placeholder="Enter password" class="form-control">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2">
                    Sign In <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </form>

            <!-- Registration Footer Links -->
            <div class="mt-4 pt-3 border-top text-center">
                <p class="text-muted small mb-2">Don't have an account yet?</p>
                <div class="d-flex justify-content-center align-items-center gap-2 small">
                    <a href="<?= BASE_URL ?>/register.php?type=parent" class="fw-bold text-success text-decoration-none">
                        <i class="fa-solid fa-baby"></i> Parent Sign Up
                    </a>
                    <span class="text-muted">&bull;</span>
                    <a href="<?= BASE_URL ?>/register.php?type=hospital" class="fw-bold text-primary text-decoration-none">
                        <i class="fa-solid fa-hospital"></i> Hospital Sign Up
                    </a>
                </div>
                <p class="text-muted small mt-2 mb-0" style="font-size: 0.75rem;">
                    * Administrator accounts are system-managed and sign-in only.
                </p>
            </div>
        </div>

        <!-- Visual Showcase Area -->
        <div class="auth-visual-side">
            <div class="auth-visual-content">
                <div class="auth-visual-image-box">
                    <img src="<?= BASE_URL ?>/assets/images/login-image.png" alt="Safe Immune Portal" class="auth-visual-image img-fluid">
                </div>
                <span class="auth-visual-badge">
                    <i class="fa-solid fa-circle-check"></i> WHO Pediatric Standard
                </span>
                <h2 class="auth-visual-title">Protecting Tomorrow, Today</h2>
                <p class="auth-visual-text">
                    Digital child immunization management platform. Track vaccines, book certified hospital appointments, and download verified digital health cards with ease.
                </p>
                <ul class="auth-feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Real-time vaccination reminders</li>
                    <li><i class="fa-solid fa-circle-check"></i> Seamless hospital appointment scheduling</li>
                    <li><i class="fa-solid fa-circle-check"></i> 100% verified digital health records</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>