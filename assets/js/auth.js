/*
 * Authentication & Role Switcher JavaScript
 */

/**
 * Switch role tab on Login page
 * @param {string} role ('parent', 'hospital', 'admin')
 */
function setLoginRoleTab(role) {
    const tabParent = document.getElementById('tabParent');
    const tabHospital = document.getElementById('tabHospital');
    const tabAdmin = document.getElementById('tabAdmin');
    const roleHint = document.getElementById('roleHint');
    const userInput = document.getElementById('userInput');
    const passInput = document.getElementById('passInput');
    const submitBtn = document.getElementById('submitBtn');
    const inputLabel = document.getElementById('inputLabel');

    if (!tabParent || !tabHospital || !tabAdmin) return;

    // Reset all tabs
    tabParent.className = 'role-tab-btn';
    tabHospital.className = 'role-tab-btn';
    tabAdmin.className = 'role-tab-btn';

    if (roleHint) roleHint.value = role;

    if (role === 'admin') {
        tabAdmin.className = 'role-tab-btn active tab-admin';
        if (userInput) userInput.placeholder = "Enter admin username or email";
        if (passInput) passInput.placeholder = "Enter administrator password";
        if (inputLabel) inputLabel.innerText = "Administrator Username or Email *";
        if (submitBtn) {
            submitBtn.className = "btn btn-purple w-100 py-2";
            submitBtn.innerHTML = 'Sign In as Administrator <i class="fa-solid fa-arrow-right ms-1"></i>';
        }
    } else if (role === 'hospital') {
        tabHospital.className = 'role-tab-btn active tab-hospital';
        if (userInput) userInput.placeholder = "Enter hospital email or username";
        if (passInput) passInput.placeholder = "Enter facility password";
        if (inputLabel) inputLabel.innerText = "Hospital Email or Staff ID *";
        if (submitBtn) {
            submitBtn.className = "btn btn-indigo w-100 py-2";
            submitBtn.innerHTML = 'Sign In to Hospital Portal <i class="fa-solid fa-arrow-right ms-1"></i>';
        }
    } else {
        tabParent.className = 'role-tab-btn active tab-parent';
        if (userInput) userInput.placeholder = "Enter parent username or email";
        if (passInput) passInput.placeholder = "Enter your password";
        if (inputLabel) inputLabel.innerText = "Parent Username or Email *";
        if (submitBtn) {
            submitBtn.className = "btn btn-success w-100 py-2";
            submitBtn.innerHTML = 'Sign In as Parent / Guardian <i class="fa-solid fa-arrow-right ms-1"></i>';
        }
    }
}

/**
 * Switch registration form between Parent and Hospital on Register page
 * @param {string} tab ('parent' or 'hospital')
 */
function switchRegisterTab(tab) {
    const isParent = (tab === 'parent');
    const parentSection = document.getElementById('parentFormSection');
    const hospitalSection = document.getElementById('hospitalFormSection');
    const tabParent = document.getElementById('tabBtnParent');
    const tabHospital = document.getElementById('tabBtnHospital');

    if (parentSection) parentSection.classList.toggle('d-none', !isParent);
    if (hospitalSection) hospitalSection.classList.toggle('d-none', isParent);

    if (tabParent) tabParent.className = isParent ? 'role-tab-btn active tab-parent' : 'role-tab-btn';
    if (tabHospital) tabHospital.className = !isParent ? 'role-tab-btn active tab-hospital' : 'role-tab-btn';
}

// Auto-initialize role on page load based on URL parameter
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const roleParam = urlParams.get('role');
    const typeParam = urlParams.get('type');

    if (roleParam && document.getElementById('tabParent')) {
        setLoginRoleTab(roleParam);
    }
    if (typeParam && document.getElementById('tabBtnParent')) {
        switchRegisterTab(typeParam);
    }
});
