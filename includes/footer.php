    </div><!-- /.container -->
</main><!-- /.page-wrapper -->

<!-- Healthcare System Footer -->
<footer class="site-footer">
    <div class="footer-shell">
 
        <div class="footer-wave" aria-hidden="true">
            <svg viewBox="0 0 1200 60" preserveAspectRatio="none" focusable="false">
                <path d="M0,28 C160,56 340,6 600,30 C860,54 1040,8 1200,26 L1200,60 L0,60 Z"></path>
            </svg>
        </div>
 
        <div class="footer-body">
            <div class="footer-top">
                <div class="footer-brand-row">
                    <img src="<?= BASE_URL ?>/assets/images/Safeimmunelogo.png" alt="Safe Immune" class="footer-logo">
                    <div>
                      <span class="footer-brand-name" style="color: #007bff;">Safe <span style="color: #16a34a;">Immune</span></span>
                        <p class="footer-brand-desc">
                            A comprehensive digital immunization and hospital booking management system ensuring zero missed vaccinations for infants and children.
                        </p>
                    </div>
                </div>
 
                <div class="footer-chips">
                    <a class="footer-chip" href="<?= BASE_URL ?>/register.php">Register</a>
                    <a class="footer-chip" href="<?= BASE_URL ?>/login.php">Sign In</a>
                </div>
            </div>
 
            <div class="footer-grid">
                <nav aria-label="Quick Navigation">
                    <h2 class="footer-col-title">Quick Navigation</h2>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/index.php">Portal Homepage</a></li>
                        <li><a href="<?= BASE_URL ?>/index.php#schedule">Immunization Schedule</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php?type=parent">New Parent Registration</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php?type=hospital">Hospital Facility Registration</a></li>
                    </ul>
                </nav>
 
                <nav aria-label="System Roles">
                    <h2 class="footer-col-title">System Roles</h2>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/login.php?role=admin"><i class="fa-solid fa-user-shield ico-admin" aria-hidden="true"></i> Administrator Portal</a></li>
                        <li><a href="<?= BASE_URL ?>/login.php?role=parent"><i class="fa-solid fa-user ico-parent" aria-hidden="true"></i> Parent / Guardian Portal</a></li>
                        <li><a href="<?= BASE_URL ?>/login.php?role=hospital"><i class="fa-solid fa-hospital ico-hospital" aria-hidden="true"></i> Hospital / Clinic Portal</a></li>
                    </ul>
                </nav>
 
                <div class="footer-col-guidelines">
                    <h2 class="footer-col-title">Health Guidelines</h2>
                    <p class="footer-guideline">
                        Compliant with WHO Infant Immunization Standards &amp; National Health Department Guidelines for Polio, BCG, Hepatitis B, and Pentavalent protection.
                    </p>
                    <span class="footer-verified"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> 100% Digital Card Verification</span>
                </div>
            </div>
 
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All Rights Reserved. | Developed for Digital Immunization Management.</p>
                <div class="footer-bottom-meta">
                    <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none" style="color:inherit;">Privicy Policy</a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none" style="color:inherit;">Terms & Condition</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Bootstrap 5.3 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modular Application Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<?php 
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
if ($currentPage === 'login.php' || $currentPage === 'register.php'): ?>
    <script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
<?php elseif ($currentDir === 'admin'): ?>
    <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<?php elseif ($currentDir === 'parent'): ?>
    <script src="<?= BASE_URL ?>/assets/js/parent.js"></script>
<?php elseif ($currentDir === 'hospital'): ?>
    <script src="<?= BASE_URL ?>/assets/js/hospital.js"></script>
<?php endif; ?>

</body>
</html>
