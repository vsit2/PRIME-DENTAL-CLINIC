/**
 * Prime Dental Clinic Management System
 * Patient Registration Form Interactive Handler
 */

document.addEventListener('DOMContentLoaded', () => {
  const dobInput = document.getElementById('dob');
  const ageInput = document.getElementById('age');

  // Auto calculate Age from DOB
  if (dobInput && ageInput) {
    dobInput.addEventListener('change', () => {
      const val = dobInput.value;
      if (!val) return;

      const dob = new Date(val);
      const today = new Date();

      if (dob > today) {
        alert('Date of Birth cannot be a future date.');
        dobInput.value = '';
        ageInput.value = '';
        return;
      }

      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
      }

      if (age >= 0) {
        ageInput.value = age;
      }
    });
  }

  // Toggle "Any Other" Reason for Visit Field
  const anyOtherReasonCheck = document.getElementById('reason_other_check');
  const otherReasonContainer = document.getElementById('other_reason_container');
  if (anyOtherReasonCheck && otherReasonContainer) {
    anyOtherReasonCheck.addEventListener('change', () => {
      otherReasonContainer.style.display = anyOtherReasonCheck.checked ? 'block' : 'none';
      if (anyOtherReasonCheck.checked) {
        const txt = document.getElementById('other_reason_text');
        if (txt) txt.focus();
      }
    });
  }

  // Toggle Habits Details
  const habitsCheck = document.getElementById('med_habits');
  const habitsContainer = document.getElementById('habits_details_container');
  if (habitsCheck && habitsContainer) {
    habitsCheck.addEventListener('change', () => {
      habitsContainer.style.display = habitsCheck.checked ? 'block' : 'none';
      if (habitsCheck.checked) {
        const inp = document.getElementById('habits_details');
        if (inp) inp.focus();
      }
    });
  }

  // Toggle Drug Allergy Details
  const drugAllergyCheck = document.getElementById('med_drug_allergy');
  const allergyContainer = document.getElementById('drug_allergy_container');
  if (drugAllergyCheck && allergyContainer) {
    drugAllergyCheck.addEventListener('change', () => {
      allergyContainer.style.display = drugAllergyCheck.checked ? 'block' : 'none';
      if (drugAllergyCheck.checked) {
        const inp = document.getElementById('drug_allergy_details');
        if (inp) inp.focus();
      }
    });
  }

  // Toggle Current Medication Details
  const medicationCheck = document.getElementById('med_current_medication');
  const medicationContainer = document.getElementById('medication_details_container');
  if (medicationCheck && medicationContainer) {
    medicationCheck.addEventListener('change', () => {
      medicationContainer.style.display = medicationCheck.checked ? 'block' : 'none';
      if (medicationCheck.checked) {
        const inp = document.getElementById('medication_details');
        if (inp) inp.focus();
      }
    });
  }

  // Toggle Other Medical Conditions Details
  const otherMedCheck = document.getElementById('med_other_conditions');
  const otherMedContainer = document.getElementById('other_med_container');
  if (otherMedCheck && otherMedContainer) {
    otherMedCheck.addEventListener('change', () => {
      otherMedContainer.style.display = otherMedCheck.checked ? 'block' : 'none';
      if (otherMedCheck.checked) {
        const inp = document.getElementById('other_details');
        if (inp) inp.focus();
      }
    });
  }

  // Style active state on checkbox chips
  document.querySelectorAll('.checkbox-chip input[type="checkbox"]').forEach(chk => {
    chk.addEventListener('change', () => {
      const parent = chk.closest('.checkbox-chip');
      if (parent) {
        if (chk.checked) parent.classList.add('active');
        else parent.classList.remove('active');
      }
    });
    // Initial check
    if (chk.checked) {
      const parent = chk.closest('.checkbox-chip');
      if (parent) parent.classList.add('active');
    }
  });
});
