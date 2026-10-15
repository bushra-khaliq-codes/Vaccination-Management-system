/*
 * Core UI JavaScript & Interactivity
 */

document.addEventListener('DOMContentLoaded', function () {
    const hasFinePointer = window.matchMedia('(pointer: fine)').matches;
    if (hasFinePointer) {
        const dot = document.createElement('div');
        const ring = document.createElement('div');
        dot.id = 'cursor-dot';
        dot.className = 'theme-cursor-dot';
        ring.id = 'cursor-ring';
        ring.className = 'theme-cursor-ring';
        document.body.append(dot, ring);

        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let ringX = mouseX;
        let ringY = mouseY;

        window.addEventListener('mousemove', function (event) {
            mouseX = event.clientX;
            mouseY = event.clientY;
            dot.style.left = `${mouseX}px`;
            dot.style.top = `${mouseY}px`;
            dot.classList.add('is-visible');
            ring.classList.add('is-visible');

            const target = event.target instanceof Element ? event.target : null;
            const textTarget = target?.closest('input:not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]), textarea, [contenteditable="true"]');
            document.body.classList.toggle('theme-cursor-text-active', Boolean(textTarget));
            dot.classList.toggle('is-hidden', Boolean(textTarget));
            ring.classList.toggle('is-hidden', Boolean(textTarget));
        });

        function animateRing() {
            ringX += (mouseX - ringX) * 0.18;
            ringY += (mouseY - ringY) * 0.18;
            ring.style.left = `${ringX}px`;
            ring.style.top = `${ringY}px`;
            requestAnimationFrame(animateRing);
        }
        animateRing();

        document.addEventListener('pointerover', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const currentTarget = target?.closest('a, button, .card, [data-hover]');
            const nextTarget = event.relatedTarget instanceof Element
                ? event.relatedTarget.closest('a, button, .card, [data-hover]')
                : null;
            if (currentTarget && currentTarget !== nextTarget) {
                ring.classList.add('is-active');
                dot.classList.add('is-active');
            }
        });

        document.addEventListener('pointerout', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const currentTarget = target?.closest('a, button, .card, [data-hover]');
            const nextTarget = event.relatedTarget instanceof Element
                ? event.relatedTarget.closest('a, button, .card, [data-hover]')
                : null;
            if (currentTarget && currentTarget !== nextTarget) {
                ring.classList.remove('is-active');
                dot.classList.remove('is-active');
            }
        });

        document.documentElement.addEventListener('mouseleave', function () {
            dot.classList.remove('is-visible');
            ring.classList.remove('is-visible');
        });

        document.documentElement.addEventListener('mouseenter', function () {
            dot.classList.add('is-visible');
            ring.classList.add('is-visible');
        });
    }

    // 1. Live Table Search Filter
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const targetTableId = input.getAttribute('data-table-search');
        const table = document.getElementById(targetTableId);
        if (!table) return;

        input.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                // Ignore empty-state rows
                if (row.querySelector('.empty-state-box')) return;
                const text = row.innerText.toLowerCase();
                row.classList.toggle('table-row-filtered', !text.includes(query));
            });
        });
    });

    // 2. Auto-dismiss Flash Alerts after 5 seconds
    const flashAlerts = document.querySelectorAll('.flash-alert');
    if (flashAlerts.length > 0) {
        setTimeout(() => {
            flashAlerts.forEach(alert => {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 400);
            });
        }, 5000);
    }
});

/**
 * Universal Action Confirmation Helper
 * @param {string} message 
 * @param {Function} callback 
 * @returns {boolean}
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') callback();
        return true;
    }
    return false;
}
