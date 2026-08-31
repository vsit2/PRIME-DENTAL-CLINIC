/**
 * Prime Dental Clinic Management System
 * Interactive Dental Tooth Chart (FDI Two-Digit System 11-48)
 */

document.addEventListener('DOMContentLoaded', () => {
  const toothItems = document.querySelectorAll('.tooth-item');
  const toothInput = document.getElementById('tooth_number');
  const selectionSummary = document.getElementById('selected_teeth_display');

  if (!toothItems.length || !toothInput) return;

  let selectedTeeth = new Set();

  // Initialize from current input value if present
  if (toothInput.value.trim()) {
    const rawValues = toothInput.value.split(',').map(s => s.trim()).filter(Boolean);
    rawValues.forEach(val => {
      if (val === 'Full Mouth') {
        toothItems.forEach(t => {
          selectedTeeth.add(t.getAttribute('data-tooth'));
          t.classList.add('selected');
        });
      } else {
        selectedTeeth.add(val);
        const item = document.querySelector(`.tooth-item[data-tooth="${val}"]`);
        if (item) item.classList.add('selected');
      }
    });
    updateSummary();
  }

  // Handle individual tooth click
  toothItems.forEach(item => {
    item.addEventListener('click', () => {
      const toothNum = item.getAttribute('data-tooth');
      if (selectedTeeth.has(toothNum)) {
        selectedTeeth.delete(toothNum);
        item.classList.remove('selected');
      } else {
        selectedTeeth.add(toothNum);
        item.classList.add('selected');
      }
      syncToInput();
    });
  });

  // Shortcut buttons
  const shortcutButtons = document.querySelectorAll('.dental-shortcut-btn');
  shortcutButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const action = btn.getAttribute('data-action');

      if (action === 'clear') {
        selectedTeeth.clear();
        toothItems.forEach(t => t.classList.remove('selected'));
      } else if (action === 'full') {
        toothItems.forEach(t => {
          selectedTeeth.add(t.getAttribute('data-tooth'));
          t.classList.add('selected');
        });
      } else if (action === 'upper') {
        toothItems.forEach(t => {
          const num = parseInt(t.getAttribute('data-tooth'));
          if (num >= 11 && num <= 28) {
            selectedTeeth.add(t.getAttribute('data-tooth'));
            t.classList.add('selected');
          }
        });
      } else if (action === 'lower') {
        toothItems.forEach(t => {
          const num = parseInt(t.getAttribute('data-tooth'));
          if (num >= 31 && num <= 48) {
            selectedTeeth.add(t.getAttribute('data-tooth'));
            t.classList.add('selected');
          }
        });
      } else if (action === 'upper_ant') {
        const anteriors = ['13', '12', '11', '21', '22', '23'];
        anteriors.forEach(num => {
          selectedTeeth.add(num);
          const el = document.querySelector(`.tooth-item[data-tooth="${num}"]`);
          if (el) el.classList.add('selected');
        });
      } else if (action === 'lower_ant') {
        const anteriors = ['43', '42', '41', '31', '32', '33'];
        anteriors.forEach(num => {
          selectedTeeth.add(num);
          const el = document.querySelector(`.tooth-item[data-tooth="${num}"]`);
          if (el) el.classList.add('selected');
        });
      }

      syncToInput();
    });
  });

  function syncToInput() {
    if (selectedTeeth.size === 32) {
      toothInput.value = 'Full Mouth (All Teeth)';
    } else {
      // Sort numerically by FDI quadrant logic
      const arr = Array.from(selectedTeeth).sort((a, b) => parseInt(a) - parseInt(b));
      toothInput.value = arr.join(', ');
    }
    updateSummary();
  }

  function updateSummary() {
    if (!selectionSummary) return;
    if (selectedTeeth.size === 0) {
      selectionSummary.innerHTML = '<span style="color:#94a3b8;font-style:italic;">None selected</span>';
    } else if (selectedTeeth.size === 32) {
      selectionSummary.innerHTML = '<span class="selected-teeth-pill">Full Mouth (32 Teeth)</span>';
    } else {
      const arr = Array.from(selectedTeeth).sort((a, b) => parseInt(a) - parseInt(b));
      selectionSummary.innerHTML = `<span class="selected-teeth-pill">${arr.join(', ')} (${selectedTeeth.size} teeth)</span>`;
    }
  }
});
