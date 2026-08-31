/**
 * Prime Dental Clinic Management System
 * Instant Live Patient Search Component
 */

document.addEventListener('DOMContentLoaded', () => {
  initPatientSearch();
});

function initPatientSearch() {
  const searchInputs = document.querySelectorAll('.global-patient-search-input');

  searchInputs.forEach(input => {
    const container = input.closest('.global-search-container');
    if (!container) return;

    // Ensure dropdown container exists
    let dropdown = container.querySelector('.search-results-dropdown');
    if (!dropdown) {
      dropdown = document.createElement('div');
      dropdown.className = 'search-results-dropdown';
      container.appendChild(dropdown);
    }

    let debounceTimer = null;
    let activeIndex = -1;
    let currentAbortController = null;

    // Input event - real-time search
    input.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      clearTimeout(debounceTimer);
      activeIndex = -1;

      // If empty, hide immediately
      if (query.length === 0) {
        if (currentAbortController) {
          currentAbortController.abort();
        }
        dropdown.innerHTML = '';
        dropdown.classList.remove('show');
        return;
      }

      // Show loading indicator
      dropdown.innerHTML = `
        <div class="search-loading">
          <span class="search-spinner"></span>
          <span>Searching patients for "<strong>${escapeHtml(query)}</strong>"...</span>
        </div>
      `;
      dropdown.classList.add('show');

      debounceTimer = setTimeout(() => {
        performPatientSearch(query, dropdown, (controller) => {
          currentAbortController = controller;
        });
      }, 150);
    });

    // Keyboard navigation: ArrowDown, ArrowUp, Enter, Escape
    input.addEventListener('keydown', (e) => {
      const items = dropdown.querySelectorAll('.search-result-item');
      if (!items.length || !dropdown.classList.contains('show')) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = (activeIndex + 1) % items.length;
        updateActiveItem(items, activeIndex);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = (activeIndex - 1 + items.length) % items.length;
        updateActiveItem(items, activeIndex);
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0 && items[activeIndex]) {
          e.preventDefault();
          items[activeIndex].click();
        }
      } else if (e.key === 'Escape') {
        dropdown.classList.remove('show');
      }
    });

    // Re-open on focus if text present
    input.addEventListener('focus', () => {
      if (input.value.trim().length > 0 && dropdown.children.length > 0) {
        dropdown.classList.add('show');
      }
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
  });
}

function updateActiveItem(items, index) {
  items.forEach((item, i) => {
    if (i === index) {
      item.classList.add('highlighted');
      item.scrollIntoView({ block: 'nearest' });
    } else {
      item.classList.remove('highlighted');
    }
  });
}

function performPatientSearch(query, dropdown, onStart) {
  const controller = new AbortController();
  if (typeof onStart === 'function') {
    onStart(controller);
  }

  const apiUrl = getApiEndpoint('api/search_patients.php') + `?q=${encodeURIComponent(query)}`;

  fetch(apiUrl, { signal: controller.signal })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (!data.success || !data.patients || data.patients.length === 0) {
        dropdown.innerHTML = `
          <div class="search-no-results">
            <div style="font-size: 24px; margin-bottom: 6px;">🔍</div>
            <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">No patient found.</div>
            <div style="font-size: 12.5px; color: #64748b;">No records matched "<em>${escapeHtml(query)}</em>"</div>
            <div style="margin-top: 12px;">
              <a href="${getApiEndpoint('pages/patient-add.php')}" class="btn btn-primary" style="padding: 6px 14px; font-size: 12.5px;">
                + Register New Patient
              </a>
            </div>
          </div>
        `;
        dropdown.classList.add('show');
        return;
      }

      let html = '';
      data.patients.forEach(p => {
        const balanceNum = parseFloat(p.balance || 0);
        const hasDue = balanceNum > 0;
        const totalBill = parseFloat(p.total_bill || 0);
        const isPaid = (totalBill > 0 && balanceNum <= 0);

        let balanceBadge = '';
        if (isPaid) {
          balanceBadge = '<span class="badge badge-paid" style="font-size: 11px;">PAID IN FULL</span>';
        } else if (hasDue) {
          balanceBadge = `<span class="badge badge-due" style="font-size: 11px;">DUE: ₹${formatIndianNumber(balanceNum)}</span>`;
        } else {
          balanceBadge = '<span class="badge badge-neutral" style="font-size: 11px;">NO DUES</span>';
        }

        const profileUrl = getApiEndpoint(`pages/patient-view.php?id=${p.id}`);
        const regDateDisplay = p.reg_date || 'Recent';

        html += `
          <a href="${profileUrl}" class="search-result-item">
            <div class="search-result-left">
              <div class="search-reg-badge">${escapeHtml(p.registration_no)}</div>
              <div>
                <div class="search-patient-name">
                  ${escapeHtml(p.full_name)}
                  <span style="font-size: 12px; color: #64748b; font-weight: normal; margin-left: 4px;">
                    (${p.age} yrs &bull; ${escapeHtml(p.gender)})
                  </span>
                </div>
                <div class="search-patient-meta">
                  <span>📞 <strong>${escapeHtml(p.mobile)}</strong></span>
                  <span>📅 Registered: <strong>${escapeHtml(regDateDisplay)}</strong></span>
                  ${p.place_of_work ? `<span>💼 ${escapeHtml(p.place_of_work)}</span>` : ''}
                </div>
              </div>
            </div>
            <div class="search-result-financial">
              <div>${balanceBadge}</div>
              <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                Total: ₹${formatIndianNumber(totalBill)}
              </div>
            </div>
          </a>
        `;
      });

      dropdown.innerHTML = html;
      dropdown.classList.add('show');
    })
    .catch(err => {
      if (err.name === 'AbortError') return;
      console.error('Instant Search Error:', err);
      dropdown.innerHTML = `
        <div class="search-no-results text-danger">
          <div style="font-weight: 600;">Unable to complete search request.</div>
          <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Please check connection or try again.</div>
        </div>
      `;
    });
}

/**
 * Robust Base URL endpoint resolver
 */
function getApiEndpoint(relativePath) {
  const cleanPath = relativePath.replace(/^\/+/, '');
  if (window.PRIME_BASE_URL) {
    return window.PRIME_BASE_URL.replace(/\/+$/, '') + '/' + cleanPath;
  }
  
  // Fallback: infer from current pathname
  const path = window.location.pathname;
  if (path.includes('/pages/') || path.includes('/print/')) {
    return '../' + cleanPath;
  }
  return './' + cleanPath;
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  })[m]);
}

function formatIndianNumber(num) {
  return parseFloat(num).toLocaleString('en-IN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0
  });
}
