<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Manage Reviews — GameHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
<style>
body{font-family:'Rajdhani',sans-serif;background:#080b12;color:#e8f4ff;margin:0;}
body::before{content:'';position:fixed;inset:0;background:linear-gradient(rgba(0,245,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.025) 1px,transparent 1px);background-size:40px 40px;z-index:0;pointer-events:none;}
.wrap{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;position:relative;z-index:1;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;background:rgba(8,11,18,.95);border-bottom:1px solid rgba(0,245,255,.08);position:sticky;top:0;z-index:100;backdrop-filter:blur(14px);}
.topbar-logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;background:linear-gradient(135deg,#00f5ff,#bf00ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:2px;}
.topbar-nav{display:flex;align-items:center;gap:.5rem;}
.topbar-nav a{padding:.4rem .9rem;border-radius:7px;color:#8ba3c0;text-decoration:none;font-size:.82rem;font-weight:600;transition:all .2s;border:1px solid transparent;}
.topbar-nav a:hover{color:#00f5ff;border-color:rgba(0,245,255,.2);}
.topbar-nav a.active{color:#00f5ff;background:rgba(0,245,255,.08);border-color:rgba(0,245,255,.2);}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:rgba(10,14,24,.88);border:1px solid rgba(0,245,255,.08);border-radius:12px;padding:1.25rem;position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--c,#00f5ff);}
.stat-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;color:var(--c,#00f5ff);}
.stat-lbl{font-size:.72rem;color:#8ba3c0;text-transform:uppercase;letter-spacing:1.5px;margin-top:.3rem;}
.glass-card{background:rgba(10,14,24,.88);border:1px solid rgba(0,245,255,.08);border-radius:12px;overflow:hidden;}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid rgba(0,245,255,.06);}
.card-title{font-family:'Orbitron',monospace;font-size:.75rem;color:#00f5ff;letter-spacing:2px;text-transform:uppercase;}
.filter-bar{display:flex;gap:.75rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(0,245,255,.06);flex-wrap:wrap;align-items:center;}
.finput{padding:.5rem 1rem;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.1);border-radius:8px;color:#e8f4ff;font-family:'Rajdhani',sans-serif;font-size:.85rem;outline:none;transition:all .2s;}
.finput:focus{border-color:#00f5ff;box-shadow:0 0 0 3px rgba(0,245,255,.1);}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem;}
.tbl th{padding:.8rem 1rem;text-align:left;font-size:.68rem;color:#3a4f6a;letter-spacing:2px;text-transform:uppercase;border-bottom:1px solid rgba(0,245,255,.06);}
.tbl td{padding:.85rem 1rem;border-bottom:1px solid rgba(0,245,255,.04);color:#8ba3c0;vertical-align:middle;}
.tbl tr:hover td{background:rgba(0,245,255,.025);}
.tbl tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:20px;font-size:.68rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.badge-5{background:rgba(57,255,20,.15);color:#39ff14;border:1px solid rgba(57,255,20,.3);}
.badge-4{background:rgba(0,245,255,.12);color:#00f5ff;border:1px solid rgba(0,245,255,.25);}
.badge-3{background:rgba(255,215,0,.12);color:#ffd700;border:1px solid rgba(255,215,0,.25);}
.badge-2{background:rgba(255,107,0,.12);color:#ff6b00;border:1px solid rgba(255,107,0,.25);}
.badge-1{background:rgba(255,0,144,.12);color:#ff0090;border:1px solid rgba(255,0,144,.25);}
.review-text-cell{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem 1rem;border-radius:8px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;border:none;}
.btn-danger{background:rgba(255,0,144,.12);color:#ff0090;border:1px solid rgba(255,0,144,.25);}
.btn-danger:hover{background:rgba(255,0,144,.25);}
.btn-ghost{background:transparent;color:#8ba3c0;border:1px solid rgba(0,245,255,.12);}
.btn-ghost:hover{border-color:#00f5ff;color:#00f5ff;}
.btn-sm{padding:.3rem .65rem;font-size:.75rem;}
.bulk-bar{display:none;align-items:center;gap:.75rem;padding:.75rem 1.5rem;background:rgba(255,0,144,.06);border-bottom:1px solid rgba(255,0,144,.2);}
.bulk-bar.show{display:flex;}
.pagination{display:flex;gap:.4rem;justify-content:center;padding:1.25rem;}
.page-btn{padding:.35rem .75rem;border-radius:6px;border:1px solid rgba(0,245,255,.12);background:transparent;color:#8ba3c0;cursor:pointer;font-family:'Rajdhani',sans-serif;font-size:.8rem;transition:all .2s;}
.page-btn:hover,.page-btn.active{border-color:#00f5ff;color:#00f5ff;background:rgba(0,245,255,.08);}
.page-btn:disabled{opacity:.3;cursor:not-allowed;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(6px);}
.modal-overlay.open{display:flex;}
.modal{background:#0d1220;border:1px solid rgba(0,245,255,.25);border-radius:16px;padding:2rem;width:min(440px,94vw);box-shadow:0 0 60px rgba(0,245,255,.1);}
.modal-title{font-family:'Orbitron',monospace;font-size:.85rem;color:#00f5ff;letter-spacing:2px;margin-bottom:1rem;}
.modal-actions{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(0,245,255,.08);}
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
    <a class="nav-item" href="manage_users.php"><span class="icon">👥</span> Manage Users</a>
    <a class="nav-item active" href="manage_reviews.php"><span class="icon">⭐</span> Manage Reviews</a>
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
    <a href="manage_users.php">👥 Users</a>
    <a href="manage_reviews.php" class="active">⭐ Reviews</a>
    <a href="manage_categories.php">🏷️ Categories</a>
    <a href="analytics.php">📈 Analytics</a>
    <a href="../php/logout.php" style="color:#ff0090">⏻ Logout</a>
  </nav>
</div>

<div class="wrap">
  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card" style="--c:#ffd700">
      <div class="stat-val" id="stat-total">–</div>
      <div class="stat-lbl">Total Reviews</div>
    </div>
    <div class="stat-card" style="--c:#39ff14">
      <div class="stat-val" id="stat-avg">–</div>
      <div class="stat-lbl">Avg Rating</div>
    </div>
    <div class="stat-card" style="--c:#00f5ff">
      <div class="stat-val" id="stat-5star">–</div>
      <div class="stat-lbl">5★ Reviews</div>
    </div>
    <div class="stat-card" style="--c:#ff0090">
      <div class="stat-val" id="stat-1star">–</div>
      <div class="stat-lbl">1★ Reviews</div>
    </div>
  </div>

  <!-- Table -->
  <div class="glass-card">
    <div class="card-header">
      <span class="card-title">⭐ Review Moderation</span>
      <span id="total-label" style="font-size:.8rem;color:#8ba3c0"></span>
    </div>

    <!-- Bulk actions bar -->
    <div class="bulk-bar" id="bulk-bar">
      <span id="bulk-count" style="font-size:.85rem;color:#ff0090;font-weight:700"></span>
      <button class="btn btn-danger btn-sm" onclick="bulkDelete()">🗑 Delete Selected</button>
      <button class="btn btn-ghost btn-sm" onclick="clearSelection()">✕ Cancel</button>
    </div>

    <div class="filter-bar">
      <input class="finput" id="search-input" placeholder="🔍 Search by game or username…"
             style="min-width:220px" oninput="debounce(loadReviews,400)()"/>
      <select class="finput" id="rating-filter" onchange="loadReviews()">
        <option value="">All Ratings</option>
        <option value="5">★★★★★ 5 Stars</option>
        <option value="4">★★★★☆ 4 Stars</option>
        <option value="3">★★★☆☆ 3 Stars</option>
        <option value="2">★★☆☆☆ 2 Stars</option>
        <option value="1">★☆☆☆☆ 1 Star</option>
      </select>
      <button class="btn btn-ghost btn-sm" onclick="selectAll()">☑ Select All</button>
    </div>

    <div style="overflow-x:auto">
      <table class="tbl">
        <thead><tr>
          <th><input type="checkbox" id="check-all" onchange="toggleAll(this)"/></th>
          <th>#</th><th>Game</th><th>User</th><th>Rating</th>
          <th>Review</th><th>Helpful</th><th>Date</th><th>Action</th>
        </tr></thead>
        <tbody id="reviews-body">
          <tr><td colspan="9" style="text-align:center;padding:3rem">
            <div style="display:flex;justify-content:center"><div class="loader-ring"></div></div>
          </td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal-overlay" id="preview-modal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-title">📖 REVIEW PREVIEW</div>
    <div id="preview-content" style="color:#8ba3c0;line-height:1.8;font-size:.9rem"></div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal('preview-modal')">Close</button>
      <button class="btn btn-danger" id="preview-delete-btn">Delete This Review</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:380px;text-align:center">
    <div style="font-size:2.5rem;margin-bottom:.75rem">⚠️</div>
    <div class="modal-title" style="justify-content:center">CONFIRM DELETE</div>
    <p id="confirm-msg" style="color:#8ba3c0;margin-bottom:1.5rem"></p>
    <div style="display:flex;gap:.75rem;justify-content:center">
      <button class="btn btn-ghost" onclick="closeModal('confirm-modal')">Cancel</button>
      <button class="btn btn-danger" id="confirm-btn">Delete</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
const API = '../php/admin.php';
let page  = 1, totalPgs = 1;
let allReviews = [];
let selectedIds = new Set();

async function loadStats() {
  const d = await fetch(API+'?action=stats').then(r=>r.json());
  if (!d.success) return;
  document.getElementById('stat-total').textContent = d.stats.total_reviews || 0;
  document.getElementById('stat-avg').textContent   = d.stats.avg_rating    || '–';

  // Count 5★ and 1★
  const rv = await fetch(API+'?action=list_reviews&page=1').then(r=>r.json());
  if (rv.success) {
    document.getElementById('stat-5star').textContent = rv.reviews.filter(r=>r.rating===5||r.rating==='5').length + '+';
    document.getElementById('stat-1star').textContent = rv.reviews.filter(r=>r.rating===1||r.rating==='1').length + '+';
  }
}

async function loadReviews() {
  document.getElementById('reviews-body').innerHTML =
    '<tr><td colspan="9" style="text-align:center;padding:2rem"><div style="display:flex;justify-content:center"><div class="loader-ring"></div></div></td></tr>';

  const data = await fetch(`${API}?action=list_reviews&page=${page}`).then(r=>r.json());
  if (!data.success) {
    document.getElementById('reviews-body').innerHTML = '<tr><td colspan="9" style="color:#ff0090;padding:1.5rem">Failed to load reviews.</td></tr>';
    return;
  }

  totalPgs = data.pages || 1;
  document.getElementById('total-label').textContent = `${data.total} total reviews`;

  const q    = document.getElementById('search-input').value.trim().toLowerCase();
  const rfil = document.getElementById('rating-filter').value;

  let rows = data.reviews;
  if (q)    rows = rows.filter(r => r.game_title?.toLowerCase().includes(q) || r.username?.toLowerCase().includes(q));
  if (rfil) rows = rows.filter(r => String(r.rating) === rfil);

  allReviews = rows;

  if (!rows.length) {
    document.getElementById('reviews-body').innerHTML = '<tr><td colspan="9" style="color:#3a4f6a;text-align:center;padding:2rem">No reviews found.</td></tr>';
    renderPagination(); return;
  }

  const stars = n => '★'.repeat(n) + '☆'.repeat(5-n);

  document.getElementById('reviews-body').innerHTML = rows.map(r => `
    <tr>
      <td><input type="checkbox" class="row-check" value="${r.id}" onchange="onCheck(${r.id},this.checked)"/></td>
      <td style="color:#3a4f6a">${r.id}</td>
      <td style="font-size:.82rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <a href="../game_detail.html?id=${r.game_id}" target="_blank" style="color:#00f5ff;text-decoration:none">${r.game_title}</a>
      </td>
      <td><strong style="color:#e8f4ff">${r.username}</strong></td>
      <td><span class="badge badge-${r.rating}" style="font-size:.72rem">${stars(Number(r.rating))} ${r.rating}</span></td>
      <td class="review-text-cell" style="max-width:220px" title="${esc(r.comment||'')}">${r.comment||''}</td>
      <td style="color:#39ff14">${r.helpful_votes||0}</td>
      <td style="font-size:.78rem">${fmtDate(r.created_at)}</td>
      <td style="display:flex;gap:.4rem">
        <button class="btn btn-ghost btn-sm" title="Preview" onclick="previewReview(${r.id})">👁</button>
        <button class="btn btn-danger btn-sm" title="Delete" onclick="deleteOne(${r.id})">✕</button>
      </td>
    </tr>`).join('');

  renderPagination();
  updateBulkBar();
}

// ── Preview ────────────────────────────────────────
function previewReview(id) {
  const r = allReviews.find(x => x.id === id || x.id === String(id));
  if (!r) return;
  const stars = n => '★'.repeat(n)+'☆'.repeat(5-n);
  document.getElementById('preview-content').innerHTML = `
    <div style="margin-bottom:1rem">
      <strong style="color:#e8f4ff;font-size:1rem">${r.game_title}</strong>
      <div style="color:#8ba3c0;font-size:.82rem;margin-top:.25rem">by <strong>${r.username}</strong> · ${fmtDate(r.created_at)}</div>
    </div>
    <div style="color:#ffd700;font-size:1.2rem;margin-bottom:.75rem">${stars(Number(r.rating))} ${r.rating}/5</div>
    <p style="color:#8ba3c0;line-height:1.8">${r.comment||'No comment'}</p>
    <div style="margin-top:1rem;font-size:.78rem;color:#3a4f6a">Helpful votes: ${r.helpful_votes||0}</div>`;
  document.getElementById('preview-delete-btn').onclick = () => { closeModal('preview-modal'); deleteOne(id); };
  document.getElementById('preview-modal').classList.add('open');
}

// ── Delete one ─────────────────────────────────────
function deleteOne(id) {
  document.getElementById('confirm-msg').textContent = 'Delete this review permanently?';
  document.getElementById('confirm-btn').onclick = async () => {
    closeModal('confirm-modal');
    const body = new URLSearchParams({ action:'delete_review', id });
    const data = await fetch(API, { method:'POST', body }).then(r=>r.json());
    toast(data.message||'Done', data.success?'success':'error');
    if (data.success) loadReviews();
  };
  document.getElementById('confirm-modal').classList.add('open');
}

// ── Bulk delete ────────────────────────────────────
function bulkDelete() {
  if (!selectedIds.size) return;
  document.getElementById('confirm-msg').textContent = `Delete ${selectedIds.size} selected review(s)?`;
  document.getElementById('confirm-btn').onclick = async () => {
    closeModal('confirm-modal');
    for (const id of selectedIds) {
      const body = new URLSearchParams({ action:'delete_review', id });
      await fetch(API, { method:'POST', body });
    }
    toast(`${selectedIds.size} reviews deleted.`, 'success');
    selectedIds.clear();
    updateBulkBar();
    loadReviews();
  };
  document.getElementById('confirm-modal').classList.add('open');
}

function onCheck(id, checked) {
  checked ? selectedIds.add(id) : selectedIds.delete(id);
  updateBulkBar();
}
function toggleAll(cb) {
  document.querySelectorAll('.row-check').forEach(c => {
    c.checked = cb.checked;
    cb.checked ? selectedIds.add(Number(c.value)) : selectedIds.delete(Number(c.value));
  });
  updateBulkBar();
}
function selectAll() {
  document.querySelectorAll('.row-check').forEach(c => { c.checked=true; selectedIds.add(Number(c.value)); });
  updateBulkBar();
}
function clearSelection() {
  selectedIds.clear();
  document.querySelectorAll('.row-check').forEach(c=>c.checked=false);
  document.getElementById('check-all').checked=false;
  updateBulkBar();
}
function updateBulkBar() {
  const bar = document.getElementById('bulk-bar');
  if (selectedIds.size > 0) {
    bar.classList.add('show');
    document.getElementById('bulk-count').textContent = `${selectedIds.size} selected`;
  } else {
    bar.classList.remove('show');
  }
}

// ── Pagination ─────────────────────────────────────
function renderPagination() {
  const c = document.getElementById('pagination');
  if (totalPgs<=1) { c.innerHTML=''; return; }
  let h = `<button class="page-btn" ${page<=1?'disabled':''} onclick="goPage(${page-1})">‹</button>`;
  const s=Math.max(1,page-2), e=Math.min(totalPgs,s+4);
  for(let i=s;i<=e;i++) h+=`<button class="page-btn ${i===page?'active':''}" onclick="goPage(${i})">${i}</button>`;
  h += `<button class="page-btn" ${page>=totalPgs?'disabled':''} onclick="goPage(${page+1})">›</button>`;
  c.innerHTML = h;
}
function goPage(n) { page=n; loadReviews(); }

// ── Helpers ────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s);
  return isNaN(d) ? s : d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}
function esc(s) { return String(s).replace(/'/g,"&#39;").replace(/"/g,'&quot;'); }
function toast(msg, type='info') {
  const c=document.getElementById('toast-container');
  const t=document.createElement('div'); t.className=`toast ${type}`; t.textContent=msg;
  c.appendChild(t);
  setTimeout(()=>{t.style.animation='toastin .3s ease reverse';setTimeout(()=>t.remove(),300);},3500);
}
function debounce(fn,ms){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};}

(async()=>{
  try{
    const s=await fetch('../php/session_check.php').then(r=>r.json());
    if(!s.logged_in||s.role!=='admin') window.location.href='../login.html';
  }catch{window.location.href='../login.html';}
  loadStats();
  loadReviews();
})();
</script>
</main>
</body>
</html>