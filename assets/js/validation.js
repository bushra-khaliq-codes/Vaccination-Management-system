/*
 * Frontend Form Validation Layer
 */

document.addEventListener('DOMContentLoaded', function () {
    // Select all forms with class 'needs-validation' or standard forms
    const forms = document.querySelectorAll('.needs-validation, form');

    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            let isValid = true;

            // 1. Required Fields Check
            const requiredInputs = form.querySelectorAll('[required]');
            requiredInputs.forEach(input => {
                // If input is hidden (e.g., inside an inactive tab), skip validation
                if (input.offsetParent === null) return;

                if (!input.value.trim()) {
                    isValid = false;
                    markInvalid(input, 'This field is required.');
                } else {
                    markValid(input);
                }
            });

            // 2. Email Validation Check
            const emailInputs = form.querySelectorAll('input[type="email"]');
            emailInputs.forEach(input => {
                if (input.offsetParent === null) return;
                if (input.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(input.value.trim())) {
                        isValid = false;
                        markInvalid(input, 'Please enter a valid email address.');
                    }
                }
            });

            // 3. Password Minimum Length Check
            const passwordInputs = form.querySelectorAll('input[name*="password"][minlength]');
            passwordInputs.forEach(input => {
                if (input.offsetParent === null) return;
                const min = parseInt(input.getAttribute('minlength') || '6', 10);
                if (input.value.length > 0 && input.value.length < min) {
                    isValid = false;
                    markInvalid(input, `Password must be at least ${min} characters.`);
                }
            });

            // 4. Password Confirmation Match
            const confirmPassInput = form.querySelector('input[name="confirm_password"]');
            const newPassInput = form.querySelector('input[name="new_password"]');
            if (confirmPassInput && newPassInput && confirmPassInput.offsetParent !== null) {
                if (confirmPassInput.value && confirmPassInput.value !== newPassInput.value) {
                    isValid = false;
                    markInvalid(confirmPassInput, 'Passwords do not match.');
                }
            }

            // If validation failed, prevent form submit
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    /*
     * Mark input as invalid and display error
     */
    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        // Check if feedback element already exists
        let feedback = input.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentElement.appendChild(feedback);
        }
        feedback.innerText = message;
    }

    /*
     * Mark input as valid
     */
    function markValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.innerText = '';
        }
    }
});
