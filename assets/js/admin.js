/* ============================================
   METREV - Admin JavaScript
   ============================================ */

// Auto-hide alerts after 5 seconds
document.addEventListener("DOMContentLoaded", function () {
  // Auto-hide flash messages
  const alerts = document.querySelectorAll(
    ".alert:not(.alert-danger):not(.alert-warning)",
  );
  alerts.forEach((alert) => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });

  // Counter animation for stat cards
  animateCounters();

  // Initialize tooltips
  initializeTooltips();

  // Setup form confirmations
  setupFormConfirmations();
});

/**
 * Animate counter numbers in stat cards
 */
function animateCounters() {
  const counters = document.querySelectorAll(".stat-number");

  counters.forEach((counter) => {
    // Skip currency, fractions, and explicitly marked elements
    if (counter.dataset.noAnimate) return;
    const text = counter.textContent.trim();
    const target = parseInt(text.replace(/[^0-9]/g, ''));
    if (isNaN(target) || target === 0) return;
    let current = 0;
    const increment = Math.ceil(target / 30);

    const updateCount = () => {
      if (current < target) {
        current += increment;
        counter.textContent = Math.min(current, target).toLocaleString();
        requestAnimationFrame(updateCount);
      } else {
        counter.textContent = target.toLocaleString();
      }
    };

    updateCount();
  });
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  tooltipTriggerList.map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl),
  );
}

/**
 * Setup form confirmation dialogs
 */
function setupFormConfirmations() {
  const deleteButtons = document.querySelectorAll("[data-confirm-delete]");

  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const itemName = this.dataset.itemName || "this item";

      if (!confirm(`Are you sure you want to delete ${itemName}?`)) {
        e.preventDefault();
      }
    });
  });
}

/**
 * Format currency
 */
function formatCurrency(amount) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(amount);
}

/**
 * Show toast notification
 */
function showToast(message, type = "info") {
  const toastHTML = `
        <div class="toast align-items-center text-white bg-${type}" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

  const toastContainer =
    document.getElementById("toast-container") || createToastContainer();
  toastContainer.insertAdjacentHTML("beforeend", toastHTML);

  const toastElement = toastContainer.lastElementChild;
  const toast = new bootstrap.Toast(toastElement);
  toast.show();
}

/**
 * Create toast container if not exists
 */
function createToastContainer() {
  const container = document.createElement("div");
  container.id = "toast-container";
  container.className = "position-fixed bottom-0 end-0 p-3";
  container.style.zIndex = "1050";
  document.body.appendChild(container);
  return container;
}

/**
 * Validate form
 */
function validateForm(formId) {
  const form = document.getElementById(formId);

  if (form && !form.checkValidity()) {
    form.classList.add("was-validated");
    return false;
  }

  return true;
}

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
  const sidebar = document.querySelector(".admin-sidebar");
  if (sidebar) {
    sidebar.classList.toggle("show");
  }
}

/**
 * Search/Filter helper
 */
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);

  if (!input || !table) return;

  input.addEventListener("keyup", function () {
    const searchTerm = this.value.toLowerCase();
    const rows = table.querySelectorAll("tbody tr");

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? "" : "none";
    });
  });
}

/**
 * Export table to CSV
 */
function exportTableToCSV(filename = "export.csv") {
  const table = document.querySelector(".table");
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll("tr");

  rows.forEach((row) => {
    let cells = [];
    row.querySelectorAll("td, th").forEach((cell) => {
      cells.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
    });
    csv.push(cells.join(","));
  });

  const csvContent =
    "data:text/csv;charset=utf-8," + encodeURIComponent(csv.join("\n"));
  const link = document.createElement("a");
  link.setAttribute("href", csvContent);
  link.setAttribute("download", filename);
  link.click();
}

/**
 * Print page
 */
function printPage() {
  window.print();
}

/**
 * Handle image preview
 */
function handleImagePreview(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);

  if (!input || !preview) return;

  input.addEventListener("change", function () {
    const file = this.files[0];

    if (file) {
      const reader = new FileReader();
      reader.addEventListener("load", function () {
        preview.src = this.result;
        preview.style.display = "block";
      });
      reader.readAsDataURL(file);
    }
  });
}

/**
 * Debounce function
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Format date
 */
function formatDate(dateString) {
  const options = { year: "numeric", month: "long", day: "numeric" };
  return new Date(dateString).toLocaleDateString("id-ID", options);
}

// Export functions for use
window.AdminUtils = {
  formatCurrency,
  showToast,
  validateForm,
  toggleSidebar,
  filterTable,
  exportTableToCSV,
  printPage,
  handleImagePreview,
  debounce,
  formatDate,
};

/* ============ Notification Panel ============ */

/**
 * Toggle notification panel and mark as read (clears badge)
 */
function toggleNotifPanel() {
  var panel = document.getElementById('adminNotifPanel');
  var isHidden = panel.classList.contains('d-none');
  panel.classList.toggle('d-none');

  // When opening the panel, mark notifications as read to clear badge
  if (isHidden) {
    var badge = document.getElementById('notifBadge');
    if (badge) {
      badge.style.display = 'none';
    }
    // POST to API to persist the read timestamp
    fetch(window.__notifApiUrl || '/api/notifications.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=mark_read'
    }).catch(function() {});
  }
}

/**
 * Dismiss a single notification item
 */
function dismissNotif(key, elemId) {
  var el = document.getElementById(elemId);
  if (!el) return;

  // Find parent section and list
  var list = el.closest('.admin-notif-list');
  var section = el.closest('.admin-notif-section');

  // Animate removal
  el.style.transition = 'opacity 0.3s, max-height 0.3s';
  el.style.opacity = '0';
  el.style.maxHeight = el.offsetHeight + 'px';
  setTimeout(function() {
    el.style.maxHeight = '0';
    el.style.padding = '0';
    el.style.margin = '0';
    el.style.overflow = 'hidden';
  }, 150);
  setTimeout(function() {
    el.remove();

    // Update section header count or hide section if empty
    if (list && section) {
      var remaining = list.querySelectorAll('.admin-notif-item').length;
      var headerStrong = section.querySelector('.admin-notif-section-header strong');
      if (remaining === 0) {
        section.style.transition = 'opacity 0.3s';
        section.style.opacity = '0';
        setTimeout(function() { section.remove(); checkNotifEmpty(); }, 300);
      } else if (headerStrong) {
        // Update the number in the header text (e.g. "7 Pesanan Dibuat" -> "6 Pesanan Dibuat")
        headerStrong.textContent = headerStrong.textContent.replace(/^\d+/, remaining);
      }
    }
  }, 400);

  // POST to API to persist the dismissal
  fetch(window.__notifApiUrl || '/api/notifications.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=dismiss&key=' + encodeURIComponent(key)
  }).catch(function() {});
}

/**
 * Check if all notification sections are empty and show empty state
 */
function checkNotifEmpty() {
  var sections = document.querySelectorAll('#adminNotifPanel .admin-notif-section');
  if (sections.length === 0) {
    var container = document.querySelector('#adminNotifPanel .admin-notif-sections');
    if (container && !container.querySelector('.admin-notif-empty')) {
      container.innerHTML = '<div class="admin-notif-empty"><i class="fas fa-check-circle"></i><p>' +
        (document.documentElement.lang === 'id' ? 'Tidak ada notifikasi — semua beres!' : 'No notifications — all clear!') +
        '</p></div>';
    }
  }
}

// Close notification panel when clicking outside
document.addEventListener('click', function(e) {
  var panel = document.getElementById('adminNotifPanel');
  var bell = document.getElementById('notifBellToggle');
  if (panel && !panel.classList.contains('d-none') && !panel.contains(e.target) && !bell.contains(e.target)) {
    panel.classList.add('d-none');
  }
});
