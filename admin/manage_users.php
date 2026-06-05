<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Manage Users — GameHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
<style>
body{font-family:'Rajdhani',sans-serif;background:#080b12;color:#e8f4ff;margin:0;}
body::before{content:'';position:fixed;inset:0;background:linear-gradient(rgba(0,245,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.025) 1px,transparent 1px);background-size:40px 40px;z-index:0;pointer-events:none;}
.wrap{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;position:relative;z-index:1;}

/* Topbar */
.topbar{display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;
  background:rgba(8,11,18,.95);border-bottom:1px solid rgba(0,245,255,.08);
  position:sticky;top:0;z-index:100;backdrop-filter:blur(14px);}
.topbar-logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;
  background:linear-gradient(135deg,#00f5ff,#bf00ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:2px;}
.topbar-nav{display:flex;align-items:center;gap:.5rem;}
.topbar-nav a{padding:.4rem .9rem;border-radius:7px;color:#8ba3c0;text-decoration:none;
  font-size:.82rem;font-weight:600;transition:all .2s;border:1px solid transparent;}
.topbar-nav a:hover{color:#00f5ff;border-color:rgba(0,245,255,.2);}
.topbar-nav a.active{color:#00f5ff;background:rgba(0,245,255,.08);border-color:rgba(0,245,255,.2);}

/* Stats row */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:rgba(10,14,24,.88);border:1px solid rgba(0,245,255,.08);border-radius:12px;
  padding:1.25rem;position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--c,#00f5ff);}
.stat-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;color:var(--c,#00f5ff);}
.stat-lbl{font-size:.72rem;color:#8ba3c0;text-transform:uppercase;letter-spacing:1.5px;margin-top:.3rem;}

/* Glass Card */
.glass-card{background:rgba(10,14,24,.88);border:1px solid rgba(0,245,255,.08);border-radius:12px;overflow:hidden;}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;
  border-bottom:1px solid rgba(0,245,255,.06);}
.card-title{font-family:'Orbitron',monospace;font-size:.75rem;color:#00f5ff;letter-spacing:2px;text-transform:uppercase;}

/* Filter bar */
.filter-bar{display:flex;gap:.75rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(0,245,255,.06);flex-wrap:wrap;}
.finput{padding:.5rem 1rem;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.1);
  border-radius:8px;color:#e8f4ff;font-family:'Rajdhani',sans-serif;font-size:.85rem;outline:none;transition:all .2s;}
.finput:focus{border-color:#00f5ff;box-shadow:0 0 0 3px rgba(0,245,255,.1);}

/* Table */
.tbl{width:100%;border-collapse:collapse;font-size:.85rem;}
.tbl th{padding:.8rem 1rem;text-align:left;font-size:.68rem;color:#3a4f6a;letter-spacing:2px;
  text-transform:uppercase;border-bottom:1px solid rgba(0,245,255,.06);}
.tbl td{padding:.8rem 1rem;border-bottom:1px solid rgba(0,245,255,.04);color:#8ba3c0;vertical-align:middle;}
.tbl tr:hover td{background:rgba(0,245,255,.025);}
.tbl tr:last-child td{border-bottom:none;}

/* Avatar */
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#00f5ff,#bf00ff);
  display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;
  color:#080b12;flex-shrink:0;font-family:'Orbitron',monospace;}

/* Badges */
.badge{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .6rem;border-radius:20px;
  font-size:.68rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.badge-admin{background:rgba(191,0,255,.2);color:#bf00ff;border:1px solid rgba(191,0,255,.3);}
.badge-user{background:rgba(0,245,255,.1);color:#00f5ff;border:1px solid rgba(0,245,255,.2);}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem 1rem;
  border-radius:8px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.82rem;
  cursor:pointer;transition:all .2s;border:none;}
.btn-primary{background:linear-gradient(135deg,#00f5ff,rgba(0,245,255,.7));color:#080b12;}
.btn-primary:hover{box-shadow:0 0 20px rgba(0,245,255,.35);}
.btn-danger{background:rgba(255,0,144,.12);color:#ff0090;border:1px solid rgba(255,0,144,.25);}
.btn-danger:hover{background:rgba(255,0,144,.25);}
.btn-warning{background:rgba(255,215,0,.12);color:#ffd700;border:1px solid rgba(255,215,0,.25);}
.btn-warning:hover{background:rgba(255,215,0,.25);}
.btn-ghost{background:transparent;color:#8ba3c0;border:1px solid rgba(0,245,255,.12);}
.btn-ghost:hover{border-color:#00f5ff;color:#00f5ff;}
.btn-sm{padding:.3rem .65rem;font-size:.75rem;}

/* Pagination */
.pagination{display:flex;gap:.4rem;justify-content:center;padding:1.25rem;}
.page-btn{padding:.35rem .75rem;border-radius:6px;border:1px solid rgba(0,245,255,.12);
  background:transparent;color:#8ba3c0;cursor:pointer;font-family:'Rajdhani',sans-serif;font-size:.8rem;transition:all .2s;}
.page-btn:hover,.page-btn.active{border-color:#00f5ff;color:#00f5ff;background:rgba(0,245,255,.08);}
.page-btn:disabled{opacity:.3;cursor:not-allowed;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:999;
  align-items:center;justify-content:center;backdrop-filter:blur(6px);}
.modal-overlay.open{display:flex;}
.modal{background:#0d1220;border:1px solid rgba(0,245,255,.25);border-radius:16px;
  padding:2rem;width:min(420px,94vw);box-shadow:0 0 60px rgba(0,245,255,.1);}
.modal-title{font-family:'Orbitron',monospace;font-size:.85rem;color:#00f5ff;letter-spacing:2px;margin-bottom:1.5rem;}
.modal-actions{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem;
  padding-top:1.5rem;border-top:1px solid rgba(0,245,255,.08);}

/* Toast */
#toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;}
.toast{padding:.75rem 1.25rem;border-radius:10px;font-size:.85rem;font-weight:600;animation:toastin .3s ease;}
.toast.success{background:rgba(57,255,20,.15);border:1px solid rgba(57,255,20,.4);color:#39ff14;}
.toast.error{background:rgba(255,0,144,.15);border:1px solid rgba(255,0,144,.4);color:#ff0090;}
.toast.info{background:rgba(0,245,255,.12);border:1px solid rgba(0,245,255,.3);color:#00f5ff;}
@keyframes toastin{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

.loader-ring{width:36px;height:36px;border:3px solid rgba(0,245,255,.1);border-top-color:#00f5ff;border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body class="admin-layout">

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">⬡ GAMEHUB</div>
    <div class="logo-sub">Admin Control Panel</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a class="nav-item" href="admin_dashboard.php"><span class="icon">📊</span> Dashboard</a>
    <div class="nav-section">Management</div>
    <a class="nav-item" href="manage_games.php"><span class="icon">🎮</span> Manage Games</a>
    <a class="nav-item active" href="manage_users.php"><span class="icon">👥</span> Manage Users</a>
    <a class="nav-item" href="manage_reviews.php"><span class="icon">⭐</span> Manage Reviews</a>
    <a class="nav-item" href="manage_categories.php"><span class="icon">🏷️</span> Categories</a>
    <a class="nav-item" href="analytics.php"><span class="icon">📈</span> Analytics</a>
    <div class="nav-section">Site</div>
    <a class="nav-item" href="../index.html" target="_blank"><span class="icon">🌐</span> View Site</a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-card-mini">
      <div class="admin-avatar">👑</div>
      <div>
        <div class="admin-name">Admin</div>
        <div class="admin-role">● Administrator</div>
      </div>
    </div>
  </div>
</aside>

<main class="main">
<div class="topbar">
  <div class="topbar-logo">⬡ GAMEHUB ADMIN</div>
  <nav class="topbar-nav">
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="manage_games.php">🎮 Games</a>
    <a href="manage_users.php" class="active">👥 Users</a>
    <a href="manage_reviews.php">⭐ Reviews</a>
    <a href="manage_categories.php">🏷️ Categories</a>
    <a href="analytics.php">📈 Analytics</a>
    <a href="../php/logout.php" style="color:#ff0090">⏻ Logout</a>
  </nav>
</div>

<div class="wrap">
  <!-- Stats Row -->
  <div class="stats-row" id="stats-row">
    <div class="stat-card" style="--c:#00f5ff">
      <div class="stat-val" id="stat-total">–</div>
      <div class="stat-lbl">Total Users</div>
    </div>
    <div class="stat-card" style="--c:#bf00ff">
      <div class="stat-val" id="stat-admins">–</div>
      <div class="stat-lbl">Admins</div>
    </div>
    <div class="stat-card" style="--c:#39ff14">
      <div class="stat-val" id="stat-new">–</div>
      <div class="stat-lbl">New This Week</div>
    </div>
    <div class="stat-card" style="--c:#ffd700">
      <div class="stat-val" id="stat-reviews">–</div>
      <div class="stat-lbl">Reviews Written</div>
    </div>
  </div>

  <!-- Users Table -->
  <div class="glass-card">
    <div class="card-header">
      <span class="card-title">👥 All Users</span>
      <span id="total-label" style="font-size:.8rem;color:#8ba3c0"></span>
    </div>
    <div class="filter-bar">
      <input class="finput" id="search-input" placeholder="🔍 Search by username or email…"
             style="min-width:240px" oninput="debounce(loadUsers,400)()"/>
      <select class="finput" id="role-filter" onchange="loadUsers()">
        <option value="">All Roles</option>
        <option value="user">Users Only</option>
        <option value="admin">Admins Only</option>
      </select>
    </div>
    <div style="overflow-x:auto">
      <table class="tbl">
        <thead><tr>
          <th>#</th><th>User</th><th>Email</th><th>Role</th>
          <th>Reviews</th><th>Wishlist</th><th>Joined</th><th>Last Login</th><th>Actions</th>
        </tr></thead>
        <tbody id="users-body">
          <tr><td colspan="9" style="text-align:center;padding:3rem">
            <div style="display:flex;justify-content:center"><div class="loader-ring"></div></div>
          </td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="delete-modal">
  <div class="modal">
    <div class="modal-title">⚠️ CONFIRM DELETE</div>
    <p style="color:#8ba3c0;line-height:1.7" id="delete-msg">Are you sure?</p>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-danger" id="delete-confirm-btn">Delete User</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
const API    = '../php/admin.php';
let page     = 1;
let totalPgs = 1;

// ── Load Stats ─────────────────────────────────────
async function loadStats() {
  const r = await fetch(API + '?action=stats').then(r=>r.json());
  if (!r.success) return;
  const s = r.stats;
  document.getElementById('stat-total').textContent   = s.total_users   || 0;
  document.getElementById('stat-new').textContent     = s.new_users_week|| 0;
  document.getElementById('stat-reviews').textContent = s.total_reviews || 0;

  // Count admins separately
  const ar = await fetch(API + '?action=list_users&page=1').then(r=>r.json());
  if (ar.success) {
    const admins = ar.users.filter(u => u.role === 'admin').length;
    document.getElementById('stat-admins').textContent = admins;
  }
}

// ── Load Users ─────────────────────────────────────
async function loadUsers() {
  const q    = document.getElementById('search-input').value.trim();
  const role = document.getElementById('role-filter').value;

  document.getElementById('users-body').innerHTML =
    '<tr><td colspan="9" style="text-align:center;padding:2rem"><div style="display:flex;justify-content:center"><div class="loader-ring"></div></div></td></tr>';

  const params = new URLSearchParams({ action:'list_users', page });
  if (q)    params.set('search', q);
  if (role) params.set('role', role);

  const data = await fetch(API + '?' + params).then(r=>r.json());
  if (!data.success) {
    document.getElementById('users-body').innerHTML =
      '<tr><td colspan="9" style="color:#ff0090;padding:1.5rem">Failed to load users. Check admin.php.</td></tr>';
    return;
  }

  totalPgs = data.pages || 1;
  document.getElementById('total-label').textContent = `${data.total} total users`;

  let filtered = data.users;
  // Client-side role filter (server might not support it yet)
  if (role) filtered = filtered.filter(u => u.role === role);
  // Client-side search
  if (q) filtered = filtered.filter(u =>
    u.username.toLowerCase().includes(q.toLowerCase()) ||
    u.email.toLowerCase().includes(q.toLowerCase())
  );

  if (!filtered.length) {
    document.getElementById('users-body').innerHTML =
      '<tr><td colspan="9" style="color:#3a4f6a;text-align:center;padding:2rem">No users found.</td></tr>';
    renderPagination();
    return;
  }

  document.getElementById('users-body').innerHTML = filtered.map(u => `
    <tr>
      <td style="color:#3a4f6a">${u.id}</td>
      <td>
        <div style="display:flex;align-items:center;gap:.75rem">
          <div class="avatar">${u.username.charAt(0).toUpperCase()}</div>
          <strong style="color:#e8f4ff">${u.username}</strong>
        </div>
      </td>
      <td style="font-size:.8rem">${u.email}</td>
      <td><span class="badge badge-${u.role}">${u.role === 'admin' ? '👑 ' : ''}${u.role}</span></td>
      <td style="color:#ffd700">${u.review_count  || 0}</td>
      <td style="color:#ff0090">${u.wishlist_count || 0}</td>
      <td>${fmtDate(u.created_at)}</td>
      <td style="color:#3a4f6a;font-size:.78rem">${u.last_login ? fmtDate(u.last_login) : 'Never'}</td>
      <td>
        <div style="display:flex;gap:.4rem">
          <button class="btn btn-warning btn-sm" title="Toggle Admin Role"
                  onclick="toggleAdmin(${u.id},'${esc(u.username)}')">👑</button>
          <button class="btn btn-danger btn-sm" title="Delete User"
                  onclick="confirmDelete(${u.id},'${esc(u.username)}')">✕</button>
        </div>
      </td>
    </tr>`).join('');

  renderPagination();
}

// ── Toggle Admin ───────────────────────────────────
async function toggleAdmin(uid, username) {
  const body = new URLSearchParams({ action:'toggle_admin', id: uid });
  const data = await fetch(API, { method:'POST', body }).then(r=>r.json());
  toast(data.message || 'Role updated', data.success ? 'success' : 'error');
  if (data.success) loadUsers();
}

// ── Delete ─────────────────────────────────────────
let pendingDeleteId = null;
function confirmDelete(uid, username) {
  pendingDeleteId = uid;
  document.getElementById('delete-msg').innerHTML =
    `Delete user <strong style="color:#e8f4ff">${username}</strong>?<br>
     <span style="font-size:.82rem">All their reviews and wishlist data will also be deleted.</span>`;
  document.getElementById('delete-confirm-btn').onclick = doDelete;
  document.getElementById('delete-modal').classList.add('open');
}
async function doDelete() {
  closeModal();
  const body = new URLSearchParams({ action:'delete_user', id: pendingDeleteId });
  const data = await fetch(API, { method:'POST', body }).then(r=>r.json());
  toast(data.message || 'Done', data.success ? 'success' : 'error');
  if (data.success) loadUsers();
}
function closeModal() {
  document.getElementById('delete-modal').classList.remove('open');
}

// ── Pagination ─────────────────────────────────────
function renderPagination() {
  const c = document.getElementById('pagination');
  if (totalPgs <= 1) { c.innerHTML = ''; return; }
  let html = `<button class="page-btn" ${page<=1?'disabled':''} onclick="goPage(${page-1})">‹</button>`;
  const s = Math.max(1, page-2), e = Math.min(totalPgs, s+4);
  for (let i=s;i<=e;i++) html += `<button class="page-btn ${i===page?'active':''}" onclick="goPage(${i})">${i}</button>`;
  html += `<button class="page-btn" ${page>=totalPgs?'disabled':''} onclick="goPage(${page+1})">›</button>`;
  c.innerHTML = html;
}
function goPage(n) { page = n; loadUsers(); }

// ── Helpers ────────────────────────────────────────
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s);
  return isNaN(d) ? s : d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}
function esc(s) { return String(s).replace(/'/g,"\\'"); }
function toast(msg, type='info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`; t.textContent = msg;
  c.appendChild(t);
  setTimeout(()=>{ t.style.animation='toastin .3s ease reverse'; setTimeout(()=>t.remove(),300); },3500);
}
function debounce(fn, ms) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(()=>fn(...a), ms); };
}

// ── Auth check ─────────────────────────────────────
(async()=>{
  try {
    const s = await fetch('../php/session_check.php').then(r=>r.json());
    if (!s.logged_in || s.role !== 'admin') window.location.href='../login.html';
  } catch { window.location.href='../login.html'; }
  loadStats();
  loadUsers();
})();
</script>
</main>
</body>
</html>