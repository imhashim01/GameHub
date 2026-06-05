/* ============================================================
   GameHub — admin.js  |  Admin Panel Shared Scripts
   ============================================================ */

/* ── Sidebar mobile toggle ── */
(function() {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 900 &&
        !sidebar.contains(e.target) &&
        !e.target.classList.contains('sidebar-toggle')) {
      sidebar.classList.remove('open');
    }
  });
})();

/* ── Auto-dismiss alerts ── */
(function() {
  const alerts = document.querySelectorAll('.admin-alert');
  alerts.forEach(el => {
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      el.style.transition = 'opacity .4s, transform .4s';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  });
})();

/* ── Confirm dialogs helper ── */
function confirmAction(message, formId) {
  if (!confirm(message)) return false;
  if (formId) document.getElementById(formId)?.submit();
  return true;
}

/* ── Toast notification (reuse from main.js pattern) ── */
function showAdminToast(msg, type = 'success') {
  const container = document.getElementById('toast-container') || document.body;
  const toast = document.createElement('div');
  toast.className = `admin-toast admin-toast--${type}`;
  toast.textContent = msg;
  toast.style.cssText = `
    position:fixed;bottom:2rem;right:2rem;z-index:99999;
    padding:.75rem 1.25rem;border-radius:8px;font-size:.85rem;
    background:var(--bg-card);border:1px solid var(--neon-cyan);
    color:var(--text-prime);box-shadow:0 0 20px rgba(0,245,255,.2);
    animation:fadeInUp .3s ease;
  `;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity='0'; setTimeout(()=>toast.remove(),400); }, 3000);
}

/* ── Table row highlight on hover (accessibility) ── */
document.querySelectorAll('.admin-table tbody tr').forEach(row => {
  row.addEventListener('mouseenter', () => row.style.background = 'rgba(0,245,255,0.03)');
  row.addEventListener('mouseleave', () => row.style.background = '');
});

/* ── Numeric counter animation ── */
function animateCount(el, target) {
  let current = 0;
  const step = Math.max(1, Math.floor(target / 40));
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString();
    if (current >= target) clearInterval(timer);
  }, 30);
}

document.querySelectorAll('.stat-num[data-count]').forEach(el => {
  animateCount(el, parseInt(el.dataset.count, 10));
});

/* ── Search debounce ── */
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

const searchInputs = document.querySelectorAll('input[name="search"]');
searchInputs.forEach(input => {
  input.addEventListener('input', debounce(() => {
    // Auto-submit search form on type (optional behaviour)
    // input.closest('form')?.submit();
  }, 400));
});

/* ── Image preview for file inputs ── */
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
  input.addEventListener('change', function() {
    const previewId = this.dataset.preview;
    const preview   = document.getElementById(previewId);
    if (!preview) return;
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
});

/* ── Data table client-side sort ── */
function sortTable(tableId, colIndex) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const tbody = table.querySelector('tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr'));
  const asc   = table.dataset.sortAsc !== 'true';
  table.dataset.sortAsc = asc;

  rows.sort((a, b) => {
    const aText = a.cells[colIndex]?.textContent.trim() || '';
    const bText = b.cells[colIndex]?.textContent.trim() || '';
    const aNum  = parseFloat(aText.replace(/[^0-9.-]/g,''));
    const bNum  = parseFloat(bText.replace(/[^0-9.-]/g,''));
    if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum-bNum : bNum-aNum;
    return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
  });

  rows.forEach(r => tbody.appendChild(r));
}