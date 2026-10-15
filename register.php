<?php
/*
 * Registration Controller & View
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$activeTab = $_GET['type'] ?? 'parent';
if (!in_array($activeTab, ['parent', 'hospital'])) {
    $activeTab = 'parent';
}
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $regType = $_POST['reg_type'] ?? 'parent';
    $activeTab = $regType;

    if (!validate_csrf($token)) {
        $errorMessage = "Security validation error. Please try again.";
    } elseif ($regType === 'parent') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        $res = register_parent($name, $email, $password, $phone, $address);
        if ($res['success']) {
            set_flash('success', $res['message']);
            header("Location: " . BASE_URL . "/login.php?role=parent");
            exit;
        } else {
            $errorMessage = $res['message'];
        }
    } elseif ($regType === 'hospital') {
        $name = sanitize($_POST['hosp_name'] ?? '');
        $regNumber = sanitize($_POST['hosp_reg'] ?? '');
        $city = sanitize($_POST['hosp_city'] ?? '');
        $location = sanitize($_POST['hosp_location'] ?? '');
        $phone = sanitize($_POST['hosp_phone'] ?? '');
        $email = sanitize($_POST['hosp_email'] ?? '');
        $password = $_POST['hosp_password'] ?? '';
        $operatingHours = sanitize($_POST['hosp_hours'] ?? '08:00 AM - 05:00 PM');

        $res = register_hospital($name, $regNumber, $city, $location, $phone, $email, $password, $operatingHours);
        if ($res['success']) {
            set_flash('success', $res['message']);
            header("Location: " . BASE_URL . "/login.php?role=hospital");
            exit;
        } else {
            $errorMessage = $res['message'];
        }
    }
}

$pageTitle = "Create an Account - Parent & Hospital Network";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Registration Main Section -->
<section class="auth-container">
    <div class="auth-card" style="max-width: 1080px;">
        
        <!-- Left Side -->
        <div class="auth-form-side">
            
            <div class="auth-header">
                <div class="auth-header-icon" style="background-color: var(--brand-light); color: var(--brand-color);">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="auth-title">Create an Account</h1>
                <p class="auth-subtitle">Select your account profile below to get started</p>
            </div>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2 px-3 rounded-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <div><?= htmlspecialchars($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <!-- Role Selector Tabs -->
            <div class="role-tabs-wrapper mb-4">
                <button type="button" onclick="switchRegisterTab('parent')" id="tabBtnParent" class="role-tab-btn <?= $activeTab === 'parent' ? 'active tab-parent' : '' ?>">
                    <i class="fa-solid fa-baby"></i> Parent Sign Up
                </button>
                <button type="button" onclick="switchRegisterTab('hospital')" id="tabBtnHospital" class="role-tab-btn <?= $activeTab === 'hospital' ? 'active tab-hospital' : '' ?>">
                    <i class="fa-solid fa-hospital"></i> Hospital Facility Sign Up
                </button>
            </div>

            <!-- 1. Parent Registration Form -->
            <div id="parentFormSection" class="<?= $activeTab === 'parent' ? '' : 'd-none' ?>">
                <form method="POST" action="<?= BASE_URL ?>/register.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reg_type" value="parent">

                    <div class="mb-3">
                        <label class="form-label-custom">Parent / Guardian Full Name <span class="text-danger">*</span></label>
                        <div class="input-group-auth">
                            <span class="input-icon-box"><i class="fa-regular fa-user"></i></span>
                            <input type="text" name="name" required placeholder="e.g. Sarah Jenkins" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">Email / Username <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" name="email" required placeholder="sarah@example.com" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" name="phone" required placeholder="+1 555 019 2831" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Residential Address <span class="text-danger">*</span></label>
                        <div class="input-group-auth">
                            <span class="input-icon-box"><i class="fa-solid fa-location-dot"></i></span>
                            <input type="text" name="address" required placeholder="Street address, City, Postal Code" class="form-control">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Account Password <span class="text-danger">*</span></label>
                        <div class="input-group-auth">
                            <span class="input-icon-box"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" required minlength="6" placeholder="Choose password (min 6 characters)" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="fa-solid fa-baby"></i> Complete Parent Registration
                    </button>
                </form>
            </div>

            <!-- 2. Hospital Facility Registration Form -->
            <div id="hospitalFormSection" class="<?= $activeTab === 'hospital' ? '' : 'd-none' ?>">
                <form method="POST" action="<?= BASE_URL ?>/register.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reg_type" value="hospital">

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label-custom">Hospital / Clinic Name <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-solid fa-hospital"></i></span>
                                <input type="text" name="hosp_name" required placeholder="e.g. City Pediatric Health Center" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label-custom">License / Reg Code</label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-solid fa-certificate"></i></span>
                                <input type="text" name="hosp_reg" placeholder="e.g. HOSP-NY-8902" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">City <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-solid fa-city"></i></span>
                                <input type="text" name="hosp_city" required placeholder="e.g. New York" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Operating Hours</label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-regular fa-clock"></i></span>
                                <input type="text" name="hosp_hours" value="08:00 AM - 05:00 PM" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Address &amp; Location Details <span class="text-danger">*</span></label>
                        <div class="input-group-auth">
                            <span class="input-icon-box"><i class="fa-solid fa-map-location-dot"></i></span>
                            <input type="text" name="hosp_location" required placeholder="Floor, Wing, Street, District" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">Official Facility Email <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" name="hosp_email" required placeholder="pediatrics@hospital.org" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Facility Phone <span class="text-danger">*</span></label>
                            <div class="input-group-auth">
                                <span class="input-icon-box"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" name="hosp_phone" required placeholder="+1 555 011 2000" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Staff Account Password <span class="text-danger">*</span></label>
                        <div class="input-group-auth">
                            <span class="input-icon-box"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="hosp_password" required minlength="6" placeholder="Choose facility password (min 6 characters)" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-indigo w-100 py-2">
                        <i class="fa-solid fa-hospital"></i> Join Network as Hospital Facility
                    </button>
                </form>
            </div>

            <!-- Footer Links -->
            <div class="mt-4 pt-3 border-top text-center">
                <p class="text-muted small mb-2">Already have an account?</p>
                <div class="d-flex justify-content-center align-items-center gap-2 small">
                    <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary text-decoration-none">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Existing Account
                    </a>
                </div>
            </div>

        </div>

        <!-- Right Side -->
        <div class="auth-visual-side" style="background: linear-gradient(135deg, #166534 0%, #1b2bbde1 50%, #22d15634 100%);">
            <div class="auth-visual-content">
                <div class="auth-visual-image-box">
                    <img src="<?= BASE_URL ?>/assets/images/register (2).png" alt="Safe Immune Registration" class="auth-visual-image img-fluid">
                </div>
                <span class="auth-visual-badge">
                    <i class="fa-solid fa-heart-pulse"></i> Comprehensive Immunization Hub
                </span>
                <h2 class="auth-visual-title">Every Vaccine, Right on Schedule.</h2>
                <p class="auth-visual-text">
                    Join thousands of families and partner healthcare institutions protecting infants and children against preventable diseases.
                </p>
                <ul class="auth-feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Automated dose timeline based on child's birth date</li>
                    <li><i class="fa-solid fa-circle-check"></i> Direct appointment booking with accredited hospitals</li>
                    <li><i class="fa-solid fa-circle-check"></i> Tamper-proof digital health card generation</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
