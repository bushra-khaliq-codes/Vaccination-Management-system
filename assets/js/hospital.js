/*
 * Hospital Portal JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    const radioVaccinated = document.getElementById('statusVaccinated');
    const radioNotVaccinated = document.getElementById('statusNotVaccinated');
    const vaccinatedFields = document.getElementById('vaccinatedFields');
    const notVaccinatedFields = document.getElementById('notVaccinatedFields');
    const batchInput = document.getElementById('batchNumberInput');
    const clinicianInput = document.getElementById('clinicianInput');

    function toggleStatusFields() {
        if (radioVaccinated && radioVaccinated.checked) {
            if (vaccinatedFields) vaccinatedFields.classList.remove('d-none');
            if (notVaccinatedFields) notVaccinatedFields.classList.add('d-none');
            if (batchInput) batchInput.setAttribute('required', 'required');
            if (clinicianInput) clinicianInput.setAttribute('required', 'required');
        } else if (radioNotVaccinated && radioNotVaccinated.checked) {
            if (vaccinatedFields) vaccinatedFields.classList.add('d-none');
            if (notVaccinatedFields) notVaccinatedFields.classList.remove('d-none');
            if (batchInput) batchInput.removeAttribute('required');
            if (clinicianInput) clinicianInput.removeAttribute('required');
        }
    }

    if (radioVaccinated && radioNotVaccinated) {
        radioVaccinated.addEventListener('change', toggleStatusFields);
        radioNotVaccinated.addEventListener('change', toggleStatusFields);
        toggleStatusFields(); // Run on initial load
    }
});
