/*
 * Administrator Portal JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Admin table row highlight 
    console.log("Admin module initialized.");
});

/**
 * Trigger rejection modal with booking ID
 * @param {number} bookingId 
 * @param {string} bookingRef 
 * @param {string} childName 
 */
function openRejectModal(bookingId, bookingRef, childName) {
    const bookingInput = document.getElementById('rejectBookingId');
    const bookingText = document.getElementById('rejectBookingRef');
    if (bookingInput) bookingInput.value = bookingId;
    if (bookingText) bookingText.innerText = `${bookingRef} (${childName})`;

    const modalElement = document.getElementById('rejectModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}
