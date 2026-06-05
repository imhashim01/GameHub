/* ============================================================
   GameHub — main.js  |  Core UI, Utilities & Auth
   Updated: Role-based nav, session management, toast system
   ============================================================ */

// ── Toast Notifications ──────────────────────────────────────
function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
      position:fixed;bottom:2rem;right:2rem;z-index:9999;
      display:flex;flex-direction:column;gap:.75rem;
    `;
    document.body.appendChild(container);
  }
  const icons = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
  const colors = {
    success: '#00ff88',
    error:   '#ff004f',
    info:    '#00e5ff',
    warning: '#ffd700'
  };
  const toast = document.createElement('div');
  toast.style.cssText = `
    background: rgba(10,22,40,0.95);
    border: 1px solid ${colors[type] || colors.info};
    box-shadow: 0 0 20px ${colors[type] || colors.info}44, 0 4px 24px rgba(0,0,0,0.5);
    color: #e8eaf6;
    padding: .85rem 1.25rem;
    border-radius: 10px;
    font-size: .88rem;
    font-family: 'Rajdhani', sans-serif;
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 260px;
    max-width: 360px;
    animation: toastSlideIn .3s ease;
    cursor: pointer;
  `;
  toast.innerHTML = `
    <span style="color:${colors[type] || colors.info};font-size:1rem;font-weight:700">${icons[type] || icons.info}</span>
    <span>${message}</span>
  `;
  toast.addEventListener('click', () => toast.remove());
  container.appendChild(toast);

  if (!document.getElementById('toast-anim')) {
    const style = document.createElement('style');
    style.id = 'toast-anim';
    style.textContent = `
      @keyframes toastSlideIn {
        from { opacity:0; transform:translateX(100%); }
        to   { opacity:1; transform:translateX(0); }
      }
      @keyframes toastSlideOut {
        from { opacity:1; transform:translateX(0); }
        to   { opacity:0; transform:translateX(100%); }
      }
    `;
    document.head.appendChild(style);
  }

  setTimeout(() => {
    toast.style.animation = 'toastSlideOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ── AJAX Helpers ─────────────────────────────────────────────
async function apiPost(url, data) {
  try {
    const body = data instanceof FormData ? data : new URLSearchParams(data);
    const headers = data instanceof FormData ? {} : { 'Content-Type': 'application/x-www-form-urlencoded' };
    const res = await fetch(url, { method: 'POST', body, headers });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (err) {
    console.warn('apiPost error:', err);
    return { success: false, message: 'Request failed.' };
  }
}

async function apiGet(url) {
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (err) {
    console.warn('apiGet error:', err);
    return { success: false };
  }
}

// ── Session Management ───────────────────────────────────────
let _sessionCache = null;

async function checkSession(force = false) {
  if (_sessionCache && !force) return _sessionCache;
  try {
    const data = await apiGet('php/session_check.php');
    _sessionCache = data;
    return data;
  } catch {
    return { logged_in: false };
  }
}

async function requireAuth(redirectTo = 'login.html') {
  const session = await checkSession();
  if (!session.logged_in) {
    showToast('Please login to continue', 'warning');
    setTimeout(() => window.location.href = redirectTo, 1200);
    return null;
  }
  return session;
}

async function requireUserRole() {
  const session = await checkSession();
  if (!session.logged_in) {
    window.location.href = 'login.html';
    return null;
  }
  if (session.role === 'admin') {
    window.location.href = 'admin/admin_dashboard.php';
    return null;
  }
  return session;
}

// ── Hamburger Nav ────────────────────────────────────────────
function initNav() {
  const hamburger = document.querySelector('.hamburger');
  const navLinks  = document.querySelector('.nav-links');
  if (!hamburger || !navLinks) return;

  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    hamburger.classList.toggle('active');
  });

  document.addEventListener('click', e => {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove('open');
      hamburger.classList.remove('active');
    }
  });

  // Mark active link
  const path = location.pathname.split('/').pop();
  document.querySelectorAll('.nav-links a').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href === path || href.endsWith(path)) a.classList.add('active');
  });
}

// ── Update Nav for Auth State ────────────────────────────────
async function updateNavAuth() {
  const session = await checkSession();
  const actions = document.querySelector('.nav-actions');
  if (!actions) return;

  if (session.logged_in) {
    const roleLabel = session.role === 'admin'
      ? `<span class="admin-badge">ADMIN</span>`
      : '';
    actions.innerHTML = `
      <span class="nav-username">👤 ${session.username} ${roleLabel}</span>
      ${session.role === 'admin'
        ? `<a href="admin/admin_dashboard.php" class="btn btn-outline btn-sm">Admin Panel</a>`
        : `<a href="dashboard.html" class="btn btn-outline btn-sm">Dashboard</a>`
      }
      <a href="php/logout.php" class="btn btn-danger btn-sm">Logout</a>
    `;
  } else {
    actions.innerHTML = `
      <a href="login.html"    class="btn btn-outline btn-sm">Login</a>
      <a href="register.html" class="btn btn-primary btn-sm">Join Free</a>
    `;
  }
}

// ── Star Rating Widget ────────────────────────────────────────
function initStarRating(container, onRate) {
  if (!container) return;
  const stars = container.querySelectorAll('.star');
  let current = 0;
  stars.forEach((star, i) => {
    star.addEventListener('mouseover', () => highlightStars(stars, i));
    star.addEventListener('mouseleave', () => highlightStars(stars, current - 1));
    star.addEventListener('click', () => {
      current = i + 1;
      highlightStars(stars, i);
      container.dataset.rating = current;
      if (onRate) onRate(current);
    });
  });
  function highlightStars(stars, upTo) {
    stars.forEach((s, idx) => s.classList.toggle('filled', idx <= upTo));
  }
}

// ── Modal System ──────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modal));
  });
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      const overlay = btn.closest('.modal-overlay');
      if (overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
    });
  });
}

// ── Animated Counter ─────────────────────────────────────────
function animateCounter(el, target, duration = 1500) {
  let start = 0;
  const step = timestamp => {
    if (!start) start = timestamp;
    const progress = Math.min((timestamp - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(eased * target).toLocaleString() + (el.dataset.suffix || '');
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

function initCounters() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        animateCounter(el, parseInt(el.dataset.count), 1800);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
}

// ── Scroll Reveal ─────────────────────────────────────────────
function initScrollReveal() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach((e, i) => {
      if (e.isIntersecting) {
        setTimeout(() => e.target.classList.add('visible'), i * 80);
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.glass-card, .game-card, .feature-card, .reveal').forEach(el => {
    if (!el.classList.contains('reveal')) el.classList.add('reveal');
    observer.observe(el);
  });
}

// ── Password Strength ─────────────────────────────────────────
function checkPasswordStrength(pwd) {
  let score = 0;
  if (pwd.length >= 8) score++;
  if (/[A-Z]/.test(pwd)) score++;
  if (/[0-9]/.test(pwd)) score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;
  return score;
}

function initPasswordStrength() {
  const input = document.getElementById('password');
  const fill  = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  if (!input || !fill || !label) return;
  const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  const colors = ['', '#ff004f', '#ff8800', '#ffd700', '#00ff88'];
  const widths = ['0%', '25%', '50%', '75%', '100%'];
  input.addEventListener('input', () => {
    const s = checkPasswordStrength(input.value);
    fill.style.width = widths[s];
    fill.style.background = colors[s];
    fill.style.boxShadow = `0 0 8px ${colors[s]}`;
    label.textContent = s > 0 ? `Password strength: ${levels[s]}` : '';
    label.style.color = colors[s];
  });
}

// ── Genre Chips ───────────────────────────────────────────────
function initGenreChips() {
  document.querySelectorAll('.genre-chip').forEach(chip => {
    chip.addEventListener('click', () => chip.classList.toggle('selected'));
  });
}

// ── Lazy Loading Images ───────────────────────────────────────
function initLazyImages() {
  const images = document.querySelectorAll('img[data-src]');
  if (!images.length) return;
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const img = e.target;
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        observer.unobserve(img);
      }
    });
  });
  images.forEach(img => observer.observe(img));
}

// ── Smooth Scroll ─────────────────────────────────────────────
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// ── Utility: format date ──────────────────────────────────────
function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function timeAgo(ts) {
  const diff = Date.now() - (typeof ts === 'number' ? ts : new Date(ts).getTime());
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  return `${Math.floor(hrs / 24)}d ago`;
}

// ── Genre → Emoji map ─────────────────────────────────────────
function getGenreEmoji(genre) {
  const map = {
    Action:'⚔️', RPG:'🧙', FPS:'🔫', Strategy:'♟️', Sports:'⚽',
    Racing:'🏎️', Horror:'👻', Adventure:'🗺️', Simulation:'🏗️',
    Fighting:'🥊', Puzzle:'🧩', 'Battle Royale':'🎯', MOBA:'🏆',
    Platformer:'🍄', Sandbox:'🏖️', MMO:'🌍', Stealth:'🕵️'
  };
  return map[genre] || '🎮';
}

// ── Logout helper ─────────────────────────────────────────────
async function logout() {
  try {
    await fetch('php/logout.php');
  } catch {}
  _sessionCache = null;
  window.location.href = 'index.html';
}

// ── Loading Spinner ───────────────────────────────────────────
function showLoader(containerId, message = 'Loading...') {
  const el = document.getElementById(containerId);
  if (el) el.innerHTML = `
    <div class="loader-wrap" style="text-align:center;padding:3rem;">
      <div class="loader-ring"></div>
      <p style="color:var(--text-muted);margin-top:1rem;font-family:var(--font-display);font-size:.85rem;letter-spacing:1px">${message}</p>
    </div>`;
}

// ── Init All ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initModals();
  initCounters();
  initScrollReveal();
  initPasswordStrength();
  initGenreChips();
  initLazyImages();
  initSmoothScroll();
  updateNavAuth();
});