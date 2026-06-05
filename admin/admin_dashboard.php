<?php
/* ============================================================
   GameHub — admin/admin_dashboard.php
   PHP-based Admin Dashboard with server-side session guard
   ============================================================ */

require_once __DIR__ . '/../php/db.php';

startSession();

// ── Server-side admin guard ───────────────────────────────────
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.html');
    exit;
}

$adminUsername = htmlspecialchars($_SESSION['username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard — GameHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
</head>
<body class="admin-layout">

<!-- ═══════════ SIDEBAR ═══════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">⬡ GAMEHUB</div>
    <div class="logo-sub">Admin Control Panel</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a class="nav-item active" onclick="showPage('dashboard')">
      <span class="icon">📊</span> Dashboard
    </a>

    <div class="nav-section">Management</div>
    <a class="nav-item" onclick="showPage('games')">
      <span class="icon">🎮</span> Manage Games
    </a>
    <a class="nav-item" href="add_game.php">
      <span class="icon">＋</span> Add Game
    </a>
    <a class="nav-item" onclick="showPage('users')">
      <span class="icon">👥</span> Manage Users
    </a>
    <a class="nav-item" onclick="showPage('reviews')">
      <span class="icon">⭐</span> Manage Reviews
    </a>
    <a class="nav-item" onclick="showPage('categories')">
      <span class="icon">🏷️</span> Categories
    </a>
    <a class="nav-item" onclick="showPage('analytics')">
      <span class="icon">📈</span> Analytics
    </a>

    <div class="nav-section">Site</div>
    <a class="nav-item" href="../index.html" target="_blank">
      <span class="icon">🌐</span> View Site
    </a>
    <a class="nav-item" href="../dashboard.html" target="_blank">
      <span class="icon">👤</span> User View
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-card-mini">
      <div class="admin-avatar">👑</div>
      <div>
        <div class="admin-name"><?= $adminUsername ?></div>
        <div class="admin-role">● Administrator</div>
      </div>
    </div>
  </div>
</aside>

<!-- ═══════════ MAIN ═══════════ -->
<main class="main">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-title" id="page-title">DASHBOARD</div>
    <div class="topbar-right">
      <span class="topbar-time" id="live-time"></span>
      <a href="../php/logout.php" class="btn-logout">⏻ Logout</a>
    </div>
  </div>

  <div class="content">

    <!-- ═══ DASHBOARD PAGE ═══ -->
    <div class="page-section active" id="page-dashboard">
      <div class="stats-grid" id="stats-grid">
        <div class="loader"><div class="loader-ring"></div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">🔥 Trending Games</span>
            <button class="btn btn-ghost btn-sm" onclick="showPage('games')">View All</button>
          </div>
          <div id="trending-list"><div class="loader"><div class="loader-ring"></div></div></div>
        </div>

        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">⭐ Recent Reviews</span>
            <button class="btn btn-ghost btn-sm" onclick="showPage('reviews')">View All</button>
          </div>
          <div id="recent-reviews-list"><div class="loader"><div class="loader-ring"></div></div></div>
        </div>
      </div>

      <div class="glass-card">
        <div class="glass-card-header">
          <span class="glass-card-title">👥 Recent Users</span>
          <button class="btn btn-ghost btn-sm" onclick="showPage('users')">View All</button>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead><tr>
              <th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th>
            </tr></thead>
            <tbody id="recent-users-body">
              <tr><td colspan="5"><div class="loader"><div class="loader-ring"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ GAMES PAGE ═══ -->
    <div class="page-section" id="page-games">
      <div class="glass-card">
        <div class="glass-card-header">
          <span class="glass-card-title">🎮 Games Library</span>
          <button class="btn btn-primary" onclick="openAddGame()">＋ Add Game</button>
        </div>
        <div class="filter-bar">
          <input class="filter-input" id="game-search" placeholder="🔍 Search games…" oninput="debounce(loadGames, 400)()"/>
          <select class="filter-select" id="game-genre-filter" onchange="loadGames()">
            <option value="">All Genres</option>
            <option>Action</option><option>RPG</option><option>FPS</option>
            <option>Strategy</option><option>Sports</option><option>Racing</option>
            <option>Horror</option><option>Adventure</option><option>Simulation</option>
            <option>Fighting</option><option>Puzzle</option><option>Battle Royale</option>
            <option>MOBA</option><option>Stealth</option><option>Survival</option>
          </select>
          <select class="filter-select" id="game-trending-filter" onchange="loadGames()">
            <option value="">All Games</option>
            <option value="1">Trending Only</option>
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead><tr>
              <th>#</th><th>Title</th><th>Genre</th><th>Rating</th>
              <th>Released</th><th>Trending</th><th>Views</th><th>Actions</th>
            </tr></thead>
            <tbody id="games-body">
              <tr><td colspan="8"><div class="loader"><div class="loader-ring"></div></div></td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination" id="games-pagination"></div>
      </div>
    </div>

    <!-- ═══ USERS PAGE ═══ -->
    <div class="page-section" id="page-users">
      <div class="glass-card">
        <div class="glass-card-header">
          <span class="glass-card-title">👥 User Management</span>
          <span id="users-count" style="font-size:.8rem;color:var(--text-mid)"></span>
        </div>
        <div class="filter-bar">
          <input class="filter-input" id="user-search" placeholder="🔍 Search users…" oninput="debounce(loadUsers, 400)()"/>
          <select class="filter-select" id="user-role-filter" onchange="loadUsers()">
            <option value="">All Roles</option>
            <option value="user">Users</option>
            <option value="admin">Admins</option>
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead><tr>
              <th>#</th><th>Username</th><th>Email</th><th>Role</th>
              <th>Reviews</th><th>Wishlist</th><th>Joined</th><th>Actions</th>
            </tr></thead>
            <tbody id="users-body">
              <tr><td colspan="8"><div class="loader"><div class="loader-ring"></div></div></td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination" id="users-pagination"></div>
      </div>
    </div>

    <!-- ═══ REVIEWS PAGE ═══ -->
    <div class="page-section" id="page-reviews">
      <div class="glass-card">
        <div class="glass-card-header">
          <span class="glass-card-title">⭐ Review Management</span>
          <span id="reviews-count" style="font-size:.8rem;color:var(--text-mid)"></span>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead><tr>
              <th>#</th><th>Game</th><th>User</th><th>Rating</th>
              <th>Review</th><th>Helpful</th><th>Date</th><th>Action</th>
            </tr></thead>
            <tbody id="reviews-body">
              <tr><td colspan="8"><div class="loader"><div class="loader-ring"></div></div></td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination" id="reviews-pagination"></div>
      </div>
    </div>

    <!-- ═══ CATEGORIES PAGE ═══ -->
    <div class="page-section" id="page-categories">
      <div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;">
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">🏷️ Categories</span>
            <span id="cats-count" style="font-size:.8rem;color:var(--text-mid)"></span>
          </div>
          <table class="admin-table">
            <thead><tr><th>#</th><th>Icon</th><th>Name</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="categories-body">
              <tr><td colspan="5"><div class="loader"><div class="loader-ring"></div></div></td></tr>
            </tbody>
          </table>
        </div>

        <div class="glass-card" style="align-self:start;">
          <div class="glass-card-header"><span class="glass-card-title">＋ Add Category</span></div>
          <div style="padding:1.25rem;">
            <div class="form-group">
              <label class="form-label">Category Name</label>
              <input class="form-control" id="cat-name" placeholder="e.g. Battle Royale"/>
            </div>
            <div class="form-group">
              <label class="form-label">Icon (emoji)</label>
              <input class="form-control" id="cat-icon" placeholder="🎯" maxlength="4"/>
            </div>
            <button class="btn btn-primary" style="width:100%" onclick="addCategory()">Add Category</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ ANALYTICS PAGE ═══ -->
    <div class="page-section" id="page-analytics">
      <div class="stats-grid" id="analytics-stats">
        <div class="loader"><div class="loader-ring"></div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
        <!-- Genre Distribution Chart -->
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">📊 Genre Distribution</span>
          </div>
          <div style="padding:1.5rem 1rem 2rem;">
            <div class="chart-bar-wrap" id="genre-chart"></div>
            <div class="chart-labels" id="genre-labels"></div>
          </div>
        </div>

        <!-- Top Reviewed Games -->
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">⭐ Most Reviewed Games</span>
          </div>
          <div id="top-reviewed-list"></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <!-- Wishlist Stats -->
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">♥ Most Wishlisted</span>
          </div>
          <div id="top-wishlisted-list"></div>
        </div>

        <!-- Platform Summary -->
        <div class="glass-card">
          <div class="glass-card-header">
            <span class="glass-card-title">🚀 Platform Growth</span>
          </div>
          <div style="padding:1.5rem;" id="platform-summary"></div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</main>

<!-- ═══ ADD/EDIT GAME MODAL ═══ -->
<div class="modal-overlay" id="game-modal">
  <div class="modal">
    <div class="modal-title" id="game-modal-title">ADD GAME</div>
    <input type="hidden" id="game-edit-id"/>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Title *</label>
        <input class="form-control" id="gf-title" placeholder="Game title"/>
      </div>
      <div class="form-group">
        <label class="form-label">Genre *</label>
        <select class="form-control" id="gf-genre">
          <option value="">Select genre</option>
          <option>Action</option><option>RPG</option><option>FPS</option>
          <option>Strategy</option><option>Sports</option><option>Racing</option>
          <option>Horror</option><option>Adventure</option><option>Simulation</option>
          <option>Fighting</option><option>Puzzle</option><option>Battle Royale</option>
          <option>MOBA</option><option>Stealth</option><option>Survival</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea class="form-control" id="gf-desc" placeholder="Game description…"></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Rating (0-5)</label>
        <input class="form-control" id="gf-rating" type="number" min="0" max="5" step="0.1" placeholder="4.5"/>
      </div>
      <div class="form-group">
        <label class="form-label">Release Date</label>
        <input class="form-control" id="gf-release" type="date"/>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Cover Image URL</label>
      <input class="form-control" id="gf-image" placeholder="https://…"/>
    </div>

    <div style="margin-bottom:1rem;font-size:.75rem;color:var(--neon-cyan);letter-spacing:2px;text-transform:uppercase;">
      PC Requirements (Minimum)
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">RAM (GB)</label>
        <input class="form-control" id="gf-ram" type="number" placeholder="8"/>
      </div>
      <div class="form-group">
        <label class="form-label">Storage (GB)</label>
        <input class="form-control" id="gf-storage" type="number" placeholder="50"/>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">GPU</label>
        <input class="form-control" id="gf-gpu" placeholder="e.g. GTX 1660"/>
      </div>
      <div class="form-group">
        <label class="form-label">CPU</label>
        <input class="form-control" id="gf-cpu" placeholder="e.g. Intel i5-9600K"/>
      </div>
    </div>

    <label class="form-check" style="margin-bottom:.75rem;">
      <input type="checkbox" id="gf-trending"/>
      <span style="font-size:.85rem;color:var(--text-mid)">Mark as Trending 🔥</span>
    </label>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('game-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveGame()" id="game-save-btn">Add Game</button>
    </div>
  </div>
</div>

<!-- ═══ CONFIRM MODAL ═══ -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:380px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
    <div class="modal-title" id="confirm-title" style="justify-content:center;">Confirm Action</div>
    <p id="confirm-msg" style="color:var(--text-mid);margin:.75rem 0 1.5rem;"></p>
    <div style="display:flex;gap:.75rem;justify-content:center;">
      <button class="btn btn-ghost" onclick="closeModal('confirm-modal')">Cancel</button>
      <button class="btn btn-danger" id="confirm-action-btn">Confirm</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
/* ══════════════════════════════════════════════════
   GameHub Admin Dashboard JS (PHP-powered version)
   ══════════════════════════════════════════════════ */

const API = '../php/admin.php';
let currentPage = { games:1, users:1, reviews:1 };

// ── Navigation ────────────────────────────────────
function showPage(name) {
  document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('page-' + name).classList.add('active');
  event.currentTarget.classList.add('active');

  const titles = {
    dashboard: 'DASHBOARD', games: 'MANAGE GAMES', users: 'MANAGE USERS',
    reviews: 'REVIEW MANAGEMENT', categories: 'CATEGORIES', analytics: 'ANALYTICS'
  };
  document.getElementById('page-title').textContent = titles[name] || name.toUpperCase();

  const loaders = {
    games:      loadGames,
    users:      loadUsers,
    reviews:    loadReviews,
    categories: loadCategories,
    analytics:  loadAnalytics
  };
  if (loaders[name]) loaders[name]();
}

// ── Live Clock ────────────────────────────────────
function startClock() {
  const el = document.getElementById('live-time');
  const tick = () => { el.textContent = new Date().toLocaleTimeString(); };
  tick(); setInterval(tick, 1000);
}

// ── API helpers ───────────────────────────────────
async function api(params) {
  const qs = new URLSearchParams(params);
  const r  = await fetch(API + '?' + qs);
  if (!r.ok) throw new Error('HTTP ' + r.status);
  return r.json();
}
async function apiPost(action, data) {
  const body = new URLSearchParams({ action, ...data });
  const r    = await fetch(API, { method:'POST', body,
    headers:{'Content-Type':'application/x-www-form-urlencoded'} });
  return r.json();
}

// ── Toast ─────────────────────────────────────────
function toast(msg, type='info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.animation='toastin .3s ease reverse'; setTimeout(()=>t.remove(),300); }, 3500);
}

// ── Modals ────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function confirmAction(msg, onConfirm) {
  document.getElementById('confirm-msg').textContent = msg;
  document.getElementById('confirm-action-btn').onclick = () => { closeModal('confirm-modal'); onConfirm(); };
  openModal('confirm-modal');
}

// ── DASHBOARD ─────────────────────────────────────
async function loadDashboard() {
  try {
    const d = await api({ action:'stats' });
    if (!d.success) return;
    const s = d.stats;

    const statCards = [
      { icon:'🎮', label:'Total Games',    value:s.total_games,    sub:`${s.trending_games} trending`,   accent:'var(--neon-cyan)' },
      { icon:'👥', label:'Total Users',    value:s.total_users,    sub:`+${s.new_users_week} this week`, accent:'var(--neon-purple)' },
      { icon:'⭐', label:'Total Reviews',  value:s.total_reviews,  sub:`Avg ${s.avg_rating||0}★`,        accent:'var(--neon-gold)' },
      { icon:'♥', label:'Wishlisted',     value:s.total_wishlist, sub:'Across all users',               accent:'var(--neon-pink)' },
      { icon:'👀', label:'Total Views',    value:s.total_views,    sub:'Game page views',                accent:'var(--neon-orange)' },
    ];

    document.getElementById('stats-grid').innerHTML = statCards.map(c => `
      <div class="stat-card" style="--accent-color:${c.accent}">
        <div class="stat-icon">${c.icon}</div>
        <div class="stat-value">${Number(c.value).toLocaleString()}</div>
        <div class="stat-label">${c.label}</div>
        <div class="stat-sub">${c.sub}</div>
      </div>`).join('');

    loadTrending();
    loadRecentReviews();
    loadRecentUsers();
  } catch(e) {
    toast('Failed to load dashboard stats', 'error');
  }
}

async function loadTrending() {
  const d  = await fetch('../php/recommendation.php?action=trending').then(r=>r.json());
  const el = document.getElementById('trending-list');
  if (!d.success || !d.games.length) {
    el.innerHTML='<p style="padding:1rem;color:var(--text-dim)">No trending games.</p>'; return;
  }
  el.innerHTML = d.games.slice(0,6).map(g => `
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(0,245,255,.1)">🎮</div>
      <div class="activity-text">
        <strong>${g.title}</strong>
        <div style="font-size:.75rem;margin-top:.15rem">${g.genre} · ★ ${g.rating}</div>
      </div>
      <div class="activity-time">${(g.views||0).toLocaleString()} views</div>
    </div>`).join('');
}

async function loadRecentReviews() {
  const d  = await api({ action:'list_reviews', page:1 });
  const el = document.getElementById('recent-reviews-list');
  if (!d.success || !d.reviews.length) {
    el.innerHTML='<p style="padding:1rem;color:var(--text-dim)">No reviews yet.</p>'; return;
  }
  el.innerHTML = d.reviews.slice(0,5).map(r => `
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(255,215,0,.1)">${'★'.repeat(r.rating)}</div>
      <div class="activity-text">
        <strong>${r.username}</strong> on <em>${r.game_title}</em>
        <div style="font-size:.75rem;margin-top:.15rem;color:var(--text-dim)">${(r.comment||'').slice(0,60)}…</div>
      </div>
      <div class="activity-time">${fmtDate(r.created_at)}</div>
    </div>`).join('');
}

async function loadRecentUsers() {
  const d  = await api({ action:'list_users', page:1 });
  const tb = document.getElementById('recent-users-body');
  if (!d.success) { tb.innerHTML='<tr><td colspan="5" style="color:var(--neon-pink);padding:1rem">Failed to load</td></tr>'; return; }
  tb.innerHTML = d.users.slice(0,5).map(u => `
    <tr>
      <td><strong>${u.username}</strong></td>
      <td style="color:var(--text-dim)">${u.email}</td>
      <td><span class="badge badge-${u.role}">${u.role}</span></td>
      <td>${fmtDate(u.created_at)}</td>
      <td>
        <button class="btn btn-warning btn-sm btn-icon" title="Toggle Admin" onclick="toggleAdmin(${u.id})">👑</button>
        <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteUser(${u.id},'${esc(u.username)}')">✕</button>
      </td>
    </tr>`).join('');
}

// ── GAMES ─────────────────────────────────────────
async function loadGames() {
  const p = currentPage.games;
  const q = document.getElementById('game-search')?.value || '';
  const g = document.getElementById('game-genre-filter')?.value || '';
  const t = document.getElementById('game-trending-filter')?.value || '';
  const d = await api({ action:'list_games', page:p, search:q, genre:g, trending:t });
  const tb = document.getElementById('games-body');

  if (!d.success) { tb.innerHTML='<tr><td colspan="8" style="color:var(--neon-pink);padding:1rem">Failed to load games.</td></tr>'; return; }
  if (!d.games.length) {
    tb.innerHTML='<tr><td colspan="8" style="color:var(--text-dim);padding:1.5rem;text-align:center">No games found.</td></tr>';
    document.getElementById('games-pagination').innerHTML=''; return;
  }

  tb.innerHTML = d.games.map(g => `
    <tr>
      <td style="color:var(--text-dim)">${g.id}</td>
      <td><strong>${g.title}</strong></td>
      <td><span class="badge badge-user">${g.genre}</span></td>
      <td style="color:var(--neon-gold)">★ ${g.rating}</td>
      <td>${g.release_date||'—'}</td>
      <td><span class="badge ${g.is_trending?'badge-trending':'badge-normal'}">${g.is_trending?'🔥 Yes':'No'}</span></td>
      <td>${(g.views||0).toLocaleString()}</td>
      <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="btn btn-warning btn-sm btn-icon" title="Toggle Trending" onclick="toggleTrending(${g.id})">🔥</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick="openEditGame(${g.id})">✏️</button>
        <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteGame(${g.id},'${esc(g.title)}')">✕</button>
      </td>
    </tr>`).join('');

  renderPagination('games-pagination', d.pages, p, n => { currentPage.games=n; loadGames(); });
}

function openAddGame() {
  document.getElementById('game-modal-title').textContent = 'ADD GAME';
  document.getElementById('game-save-btn').textContent    = 'Add Game';
  document.getElementById('game-edit-id').value = '';
  ['gf-title','gf-desc','gf-image','gf-gpu','gf-cpu'].forEach(id => document.getElementById(id).value='');
  ['gf-rating','gf-ram','gf-storage'].forEach(id => document.getElementById(id).value='');
  document.getElementById('gf-genre').value='';
  document.getElementById('gf-release').value='';
  document.getElementById('gf-trending').checked=false;
  openModal('game-modal');
}

async function openEditGame(id) {
  const d = await fetch('../php/recommendation.php?action=game_detail&id='+id).then(r=>r.json());
  if (!d.success || !d.game) { toast('Failed to load game','error'); return; }
  const g = d.game;
  document.getElementById('game-modal-title').textContent = 'EDIT GAME';
  document.getElementById('game-save-btn').textContent    = 'Save Changes';
  document.getElementById('game-edit-id').value = g.id;
  document.getElementById('gf-title').value   = g.title  || '';
  document.getElementById('gf-genre').value   = g.genre  || '';
  document.getElementById('gf-desc').value    = g.description || '';
  document.getElementById('gf-rating').value  = g.rating || '';
  document.getElementById('gf-release').value = g.release_date || '';
  document.getElementById('gf-image').value   = g.image_url   || '';
  document.getElementById('gf-ram').value     = g.min_ram     || '';
  document.getElementById('gf-storage').value = g.min_storage || '';
  document.getElementById('gf-gpu').value     = g.min_gpu     || '';
  document.getElementById('gf-cpu').value     = g.min_cpu     || '';
  document.getElementById('gf-trending').checked = !!g.is_trending;
  openModal('game-modal');
}

async function saveGame() {
  const id    = document.getElementById('game-edit-id').value;
  const title = document.getElementById('gf-title').value.trim();
  const genre = document.getElementById('gf-genre').value;
  if (!title || !genre) { toast('Title and Genre are required','error'); return; }

  const payload = {
    title, genre,
    description:  document.getElementById('gf-desc').value,
    rating:       document.getElementById('gf-rating').value,
    release_date: document.getElementById('gf-release').value,
    image_url:    document.getElementById('gf-image').value,
    min_ram:      document.getElementById('gf-ram').value,
    min_storage:  document.getElementById('gf-storage').value,
    min_gpu:      document.getElementById('gf-gpu').value,
    min_cpu:      document.getElementById('gf-cpu').value,
    is_trending:  document.getElementById('gf-trending').checked ? 1 : 0,
  };
  if (id) payload.id = id;

  const d = await apiPost(id ? 'edit_game' : 'add_game', payload);
  toast(d.message || (d.success?'Done!':'Error'), d.success?'success':'error');
  if (d.success) { closeModal('game-modal'); loadGames(); }
}

async function toggleTrending(id) {
  const d = await apiPost('toggle_trending', { id });
  toast(d.success ? (d.trending ? '🔥 Marked trending' : 'Removed from trending') : 'Error', d.success?'success':'error');
  if (d.success) loadGames();
}

function deleteGame(id, title) {
  confirmAction(`Delete "${title}"? This will also remove its reviews and wishlist entries.`, async () => {
    const d = await apiPost('delete_game', { id });
    toast(d.message||'Done', d.success?'success':'error');
    if (d.success) loadGames();
  });
}

// ── USERS ─────────────────────────────────────────
async function loadUsers() {
  const p  = currentPage.users;
  const q  = document.getElementById('user-search')?.value || '';
  const r  = document.getElementById('user-role-filter')?.value || '';
  const d  = await api({ action:'list_users', page:p, search:q, role:r });
  const tb = document.getElementById('users-body');

  if (!d.success) { tb.innerHTML='<tr><td colspan="8" style="color:var(--neon-pink);padding:1rem">Failed to load.</td></tr>'; return; }
  document.getElementById('users-count').textContent = `Total: ${d.total} users`;

  tb.innerHTML = d.users.map(u => `
    <tr>
      <td style="color:var(--text-dim)">${u.id}</td>
      <td><strong>${u.username}</strong></td>
      <td style="color:var(--text-dim);font-size:.8rem">${u.email}</td>
      <td><span class="badge badge-${u.role}">${u.role}</span></td>
      <td>${u.review_count||0}</td>
      <td>${u.wishlist_count||0}</td>
      <td>${fmtDate(u.created_at)}</td>
      <td style="display:flex;gap:.4rem;">
        <button class="btn btn-warning btn-sm btn-icon" title="Toggle Admin" onclick="toggleAdmin(${u.id})">👑</button>
        <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteUser(${u.id},'${esc(u.username)}')">✕</button>
      </td>
    </tr>`).join('');

  renderPagination('users-pagination', d.pages, p, n => { currentPage.users=n; loadUsers(); });
}

async function toggleAdmin(id) {
  const d = await apiPost('toggle_admin', { id });
  toast(d.message||'Role updated', d.success?'success':'error');
  if (d.success) loadUsers();
}

function deleteUser(id, username) {
  confirmAction(`Delete user "${username}"? All their reviews and wishlist data will be removed.`, async () => {
    const d = await apiPost('delete_user', { id });
    toast(d.message||'Done', d.success?'success':'error');
    if (d.success) loadUsers();
  });
}

// ── REVIEWS ───────────────────────────────────────
async function loadReviews() {
  const p  = currentPage.reviews;
  const d  = await api({ action:'list_reviews', page:p });
  const tb = document.getElementById('reviews-body');

  if (!d.success) { tb.innerHTML='<tr><td colspan="8" style="color:var(--neon-pink);padding:1rem">Failed to load.</td></tr>'; return; }
  document.getElementById('reviews-count').textContent = `Total: ${d.total} reviews`;

  const stars = n => '★'.repeat(n)+'☆'.repeat(5-n);

  tb.innerHTML = d.reviews.map(r => `
    <tr>
      <td style="color:var(--text-dim)">${r.id}</td>
      <td style="font-size:.8rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.game_title}</td>
      <td><strong>${r.username}</strong></td>
      <td style="color:var(--neon-gold)">${stars(r.rating)}</td>
      <td style="max-width:200px;font-size:.8rem;color:var(--text-mid);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.comment||''}</td>
      <td style="color:var(--neon-green)">${r.helpful_votes||0}</td>
      <td>${fmtDate(r.created_at)}</td>
      <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteReview(${r.id})">✕</button></td>
    </tr>`).join('');

  renderPagination('reviews-pagination', d.pages, p, n => { currentPage.reviews=n; loadReviews(); });
}

function deleteReview(id) {
  confirmAction('Delete this review permanently?', async () => {
    const d = await apiPost('delete_review', { id });
    toast(d.message||'Done', d.success?'success':'error');
    if (d.success) loadReviews();
  });
}

// ── CATEGORIES ────────────────────────────────────
async function loadCategories() {
  const d  = await api({ action:'list_categories' });
  const tb = document.getElementById('categories-body');
  if (!d.success) { tb.innerHTML='<tr><td colspan="5" style="color:var(--neon-pink);padding:1rem">Error</td></tr>'; return; }
  document.getElementById('cats-count').textContent = `${d.categories.length} categories`;
  tb.innerHTML = d.categories.map(c => `
    <tr>
      <td style="color:var(--text-dim)">${c.id}</td>
      <td style="font-size:1.4rem">${c.icon||'🎮'}</td>
      <td><strong>${c.name}</strong></td>
      <td><span class="badge badge-green">Active</span></td>
      <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteCategory(${c.id},'${esc(c.name)}')">✕</button></td>
    </tr>`).join('');
}

async function addCategory() {
  const name = document.getElementById('cat-name').value.trim();
  const icon = document.getElementById('cat-icon').value.trim() || '🎮';
  if (!name) { toast('Name required','error'); return; }
  const d = await apiPost('add_category', { name, icon });
  toast(d.message||'Done', d.success?'success':'error');
  if (d.success) { document.getElementById('cat-name').value=''; document.getElementById('cat-icon').value=''; loadCategories(); }
}

function deleteCategory(id, name) {
  confirmAction(`Delete category "${name}"?`, async () => {
    const d = await apiPost('delete_category', { id });
    toast(d.message||'Done', d.success?'success':'error');
    if (d.success) loadCategories();
  });
}

// ── ANALYTICS ─────────────────────────────────────
async function loadAnalytics() {
  try {
    const d = await api({ action:'stats' });
    if (!d.success) return;
    const s = d.stats;

    document.getElementById('analytics-stats').innerHTML = [
      { icon:'🏆', label:'Top Rated Game', value: s.top_rated_game||'—', accent:'var(--neon-gold)', text:true },
      { icon:'📈', label:'Avg Review Score', value: s.avg_rating||'0', accent:'var(--neon-green)', suffix:'★' },
      { icon:'🔥', label:'Trending Games', value: s.trending_games, accent:'var(--neon-orange)' },
      { icon:'👀', label:'Total Page Views', value: s.total_views, accent:'var(--neon-cyan)' },
    ].map(c => `
      <div class="stat-card" style="--accent-color:${c.accent}">
        <div class="stat-icon">${c.icon}</div>
        ${c.text
          ? `<div class="stat-value" style="font-size:1rem">${c.value}</div>`
          : `<div class="stat-value">${Number(c.value).toLocaleString()}${c.suffix||''}</div>`}
        <div class="stat-label">${c.label}</div>
      </div>`).join('');

    loadGenreChart();
    loadTopReviewed();
    loadTopWishlisted();
    loadPlatformSummary(s);
  } catch(e) { toast('Failed to load analytics','error'); }
}

async function loadGenreChart() {
  try {
    // Fetch genre distribution from games list
    const d = await api({ action:'list_games', page:1 });
    if (!d.success) return;

    const counts = {};
    d.games.forEach(g => { counts[g.genre] = (counts[g.genre]||0) + 1; });
    const top = Object.entries(counts).sort((a,b)=>b[1]-a[1]).slice(0,8);
    const max = Math.max(...top.map(e=>e[1]));

    const colors = ['var(--neon-cyan)','var(--neon-purple)','var(--neon-pink)',
                    'var(--neon-gold)','var(--neon-green)','var(--neon-orange)',
                    'var(--neon-cyan)','var(--neon-purple)'];

    document.getElementById('genre-chart').innerHTML = top.map(([genre, count], i) => `
      <div class="chart-bar" style="height:${(count/max)*100}%;background:linear-gradient(to top,${colors[i]},${colors[i]}55);"
           title="${genre}: ${count}">
      </div>`).join('');

    document.getElementById('genre-labels').innerHTML = top.map(([genre]) => `
      <div class="chart-label-item">${genre.slice(0,7)}</div>`).join('');
  } catch {}
}

async function loadTopReviewed() {
  const d  = await api({ action:'list_reviews', page:1 });
  const el = document.getElementById('top-reviewed-list');
  if (!d.success || !d.reviews.length) { el.innerHTML='<p style="padding:1rem;color:var(--text-dim)">No reviews</p>'; return; }

  // Count reviews per game
  const gameCounts = {};
  d.reviews.forEach(r => {
    if (!gameCounts[r.game_title]) gameCounts[r.game_title] = { count:0, avgRating:0, ratings:[] };
    gameCounts[r.game_title].count++;
    gameCounts[r.game_title].ratings.push(r.rating);
  });
  const top = Object.entries(gameCounts)
    .map(([title, data]) => ({ title, count: data.count, avg: (data.ratings.reduce((a,b)=>a+b,0)/data.ratings.length).toFixed(1) }))
    .sort((a,b)=>b.count-a.count).slice(0,5);

  el.innerHTML = top.map(g => `
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(255,215,0,.1)">⭐</div>
      <div class="activity-text"><strong>${g.title}</strong></div>
      <div class="activity-time">${g.count} reviews · ★${g.avg}</div>
    </div>`).join('');
}

async function loadTopWishlisted() {
  // Use stats endpoint — not directly available so approximate via games endpoint
  const d  = await api({ action:'list_games', page:1 });
  const el = document.getElementById('top-wishlisted-list');
  if (!d.success) { el.innerHTML='<p style="padding:1rem;color:var(--text-dim)">N/A</p>'; return; }
  const top = d.games.sort((a,b)=>(b.views||0)-(a.views||0)).slice(0,5);
  el.innerHTML = top.map(g => `
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(255,0,144,.1)">♥</div>
      <div class="activity-text"><strong>${g.title}</strong><div style="font-size:.75rem;color:var(--text-dim)">${g.genre}</div></div>
      <div class="activity-time">${(g.views||0).toLocaleString()} views</div>
    </div>`).join('');
}

function loadPlatformSummary(s) {
  const el = document.getElementById('platform-summary');
  el.innerHTML = `
    <div style="display:flex;flex-direction:column;gap:1rem;">
      ${[
        { label:'Users this week',   val: '+' + (s.new_users_week||0), color:'var(--neon-green)' },
        { label:'Total content',     val: ((s.total_games||0)+(s.total_reviews||0)).toLocaleString(), color:'var(--neon-cyan)' },
        { label:'Engagement rate',   val: s.total_reviews>0 ? ((s.total_reviews/s.total_users)*100).toFixed(0)+'%' : '0%', color:'var(--neon-gold)' },
        { label:'Wishlist actions',  val: (s.total_wishlist||0).toLocaleString(), color:'var(--neon-pink)' },
      ].map(item => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border-dim);">
          <span style="font-size:.85rem;color:var(--text-mid)">${item.label}</span>
          <span style="font-family:'Orbitron',monospace;font-size:.9rem;color:${item.color};font-weight:700">${item.val}</span>
        </div>`).join('')}
    </div>`;
}

// ── Helpers ───────────────────────────────────────
function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}

function esc(str) {
  return (str||'').replace(/'/g,"\\'").replace(/"/g,'&quot;');
}

function renderPagination(containerId, total, current, onPage) {
  const c = document.getElementById(containerId);
  if (!c || total <= 1) { if(c) c.innerHTML=''; return; }
  let html = `<button class="page-btn" ${current<=1?'disabled':''} onclick="(${onPage.toString()})(${current-1})">‹ Prev</button>`;
  const start = Math.max(1, current - 2);
  const end   = Math.min(total, start + 4);
  for (let i=start; i<=end; i++) {
    html += `<button class="page-btn ${i===current?'active':''}" onclick="(${onPage.toString()})(${i})">${i}</button>`;
  }
  html += `<button class="page-btn" ${current>=total?'disabled':''} onclick="(${onPage.toString()})(${current+1})">Next ›</button>`;
  c.innerHTML = html;
}

function debounce(fn, delay) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

// ── Init ──────────────────────────────────────────
(async () => {
  startClock();
  await loadDashboard();
})();
</script>
</body>
</html>