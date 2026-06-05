<?php
/* ============================================================
   GameHub — admin/manage_games.php
   Dedicated Manage Games page with server-side session guard
   ============================================================ */

require_once __DIR__ . '/../php/db.php';
startSession();

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
<title>Manage Games — GameHub Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
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
    <a class="nav-item active" href="manage_games.php"><span class="icon">🎮</span> Manage Games</a>
    <a class="nav-item" href="add_game.php"><span class="icon">＋</span> Add Game</a>
    <a class="nav-item" href="manage_users.php"><span class="icon">👥</span> Manage Users</a>
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
        <div class="admin-name"><?= $adminUsername ?></div>
        <div class="admin-role">● Administrator</div>
      </div>
    </div>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="topbar-title">MANAGE GAMES</div>
    <div class="topbar-right">
      <span class="topbar-time" id="live-time"></span>
      <a href="../php/logout.php" class="btn-logout">⏻ Logout</a>
    </div>
  </div>

  <div class="content">
    <div class="glass-card">
      <div class="glass-card-header">
        <span class="glass-card-title">🎮 Games Library</span>
        <div style="display:flex;gap:.75rem;align-items:center;">
          <span id="games-count" style="font-size:.8rem;color:var(--text-mid)"></span>
          <a class="btn btn-primary" href="add_game.php">＋ Add Game</a>
        </div>
      </div>
      <div class="filter-bar">
        <input class="filter-input" id="game-search" placeholder="🔍 Search games…" oninput="debounce(loadGames,400)()"/>
        <select class="filter-select" id="game-genre" onchange="loadGames()">
          <option value="">All Genres</option>
          <option>Action</option><option>RPG</option><option>FPS</option>
          <option>Strategy</option><option>Sports</option><option>Racing</option>
          <option>Horror</option><option>Adventure</option><option>Simulation</option>
          <option>Fighting</option><option>Puzzle</option><option>Battle Royale</option>
          <option>MOBA</option><option>Stealth</option><option>Survival</option>
        </select>
        <select class="filter-select" id="game-trending" onchange="loadGames()">
          <option value="">All Games</option>
          <option value="1">Trending Only</option>
        </select>
      </div>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead><tr>
            <th>#</th><th>Cover</th><th>Title</th><th>Genre</th><th>Rating</th>
            <th>Released</th><th>Requirements</th><th>Cracked Link</th><th>Trending</th><th>Views</th><th>Actions</th>
          </tr></thead>
          <tbody id="games-body">
            <tr><td colspan="11"><div class="loader"><div class="loader-ring"></div></div></td></tr>
          </tbody>
        </table>
      </div>
      <div class="pagination" id="games-pagination"></div>
    </div>
  </div>
</main>

<!-- ADD/EDIT GAME MODAL -->
<div class="modal-overlay" id="game-modal">
  <div class="modal" style="display:flex;flex-direction:column;max-height:90vh;padding:0;">
    <div class="modal-title" id="game-modal-title" style="padding:1.25rem 1.5rem;flex-shrink:0;border-bottom:1px solid rgba(255,255,255,.07);">ADD GAME</div>
    <input type="hidden" id="game-edit-id"/>
    <div style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;">

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
      <textarea class="form-control" id="gf-desc" placeholder="Game description…" style="min-height:70px;resize:vertical;"></textarea>
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
      <label class="form-label">Cover Image</label>

      <!-- Upload tab / URL tab toggle -->
      <div style="display:flex;gap:.5rem;margin-bottom:.6rem;">
        <button type="button" class="img-tab active" id="tab-upload" onclick="switchImgTab('upload')">📁 Upload File</button>
        <button type="button" class="img-tab" id="tab-url" onclick="switchImgTab('url')">🔗 Paste URL</button>
      </div>

      <!-- File upload panel -->
      <div id="img-panel-upload">
        <div class="upload-drop" id="upload-drop" onclick="document.getElementById('gf-file').click()"
             ondragover="event.preventDefault();this.classList.add('drag-over')"
             ondragleave="this.classList.remove('drag-over')"
             ondrop="handleDrop(event)">
          <div id="upload-drop-inner">
            <div style="font-size:2rem;">🖼️</div>
            <div style="font-size:.85rem;color:var(--text-mid);margin-top:.4rem;">Click or drag &amp; drop image here</div>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:.2rem;">JPG · PNG · WEBP · GIF — max 3 MB</div>
          </div>
        </div>
        <input type="file" id="gf-file" accept="image/*" style="display:none" onchange="handleFileSelect(this)">
        <div id="upload-progress" style="display:none;margin-top:.5rem;">
          <div style="height:4px;background:var(--border-dim);border-radius:4px;overflow:hidden;">
            <div id="upload-bar" style="height:100%;width:0;background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple));transition:width .3s;"></div>
          </div>
          <div id="upload-status" style="font-size:.75rem;color:var(--neon-cyan);margin-top:.3rem;">Uploading…</div>
        </div>
      </div>

      <!-- URL panel (hidden by default) -->
      <div id="img-panel-url" style="display:none;">
        <input class="form-control" id="gf-image-url-input" placeholder="https://example.com/cover.jpg"
               oninput="document.getElementById('gf-image').value=this.value; updatePreview(this.value)"/>
      </div>

      <!-- Hidden field that always holds the final URL -->
      <input type="hidden" id="gf-image"/>

      <!-- Live preview -->
      <div id="img-preview-wrap" style="display:none;margin-top:.75rem;position:relative;">
        <img id="img-preview" src="" alt="Cover preview"
             style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border-glow);">
        <button type="button" onclick="clearImage()"
                style="position:absolute;top:.3rem;right:.3rem;background:rgba(255,0,144,.8);border:none;
                       color:#fff;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:.8rem;">✕</button>
      </div>
    </div>
    <div style="margin:.75rem 0;font-size:.75rem;color:var(--neon-cyan);letter-spacing:2px;text-transform:uppercase;">PC Requirements (Minimum)</div>
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
    <!-- Cracked Version Link -->
    <div style="background:rgba(57,255,20,.06);border:1px solid rgba(57,255,20,.35);border-radius:10px;padding:.85rem 1rem;margin-bottom:1rem;">
      <label style="display:flex;align-items:center;gap:.5rem;font-size:.72rem;font-weight:700;color:#39ff14;letter-spacing:2px;text-transform:uppercase;margin-bottom:.5rem;">
        <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#39ff14;box-shadow:0 0 5px #39ff14;flex-shrink:0;"></span>
        Cracked Version URL
        <span style="font-size:.7rem;color:#3a4f6a;font-weight:400;text-transform:none;letter-spacing:0;margin-left:.25rem;">(optional)</span>
      </label>
      <input class="form-control" id="gf-crack" placeholder="https://… paste the cracked/download page URL here" style="border-color:rgba(57,255,20,.3);background:rgba(57,255,20,.03);"/>
      <div style="display:flex;align-items:flex-start;gap:.4rem;font-size:.72rem;color:#4a7a4a;margin-top:.45rem;line-height:1.5;">
        <span>ℹ️</span>
        <span>When saved, a green <strong style="color:#39ff14;">⚡ Download Cracked Version</strong> button will appear on the game's detail page.</span>
      </div>
    </div>

    <label class="form-check" style="margin-bottom:.75rem;">
      <input type="checkbox" id="gf-trending"/>
      <span style="font-size:.85rem;color:var(--text-mid)">Mark as Trending 🔥</span>
    </label>

    </div><!-- end scrollable body -->
    <div class="modal-footer" style="flex-shrink:0;border-top:1px solid rgba(255,255,255,.07);padding:1rem 1.5rem;">
      <button class="btn btn-ghost" onclick="closeModal('game-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveGame()" id="game-save-btn">Add Game</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:380px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
    <div class="modal-title" style="justify-content:center;">Confirm Delete</div>
    <p id="confirm-msg" style="color:var(--text-mid);margin:.75rem 0 1.5rem;"></p>
    <div style="display:flex;gap:.75rem;justify-content:center;">
      <button class="btn btn-ghost" onclick="closeModal('confirm-modal')">Cancel</button>
      <button class="btn btn-danger" id="confirm-action-btn">Delete</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script>
const API = '../php/admin.php';
let currentPage = 1;

function startClock() {
  const el = document.getElementById('live-time');
  const tick = () => { el.textContent = new Date().toLocaleTimeString(); };
  tick(); setInterval(tick, 1000);
}

async function api(params) {
  const r = await fetch(API + '?' + new URLSearchParams(params));
  return r.json();
}
async function apiPost(action, data) {
  const r = await fetch(API, { method:'POST', body: new URLSearchParams({action,...data}),
    headers:{'Content-Type':'application/x-www-form-urlencoded'} });
  return r.json();
}

function toast(msg, type='info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className=`toast ${type}`; t.textContent=msg; c.appendChild(t);
  setTimeout(()=>{ t.style.animation='toastin .3s ease reverse'; setTimeout(()=>t.remove(),300); },3500);
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function confirmAction(msg, fn) {
  document.getElementById('confirm-msg').textContent = msg;
  document.getElementById('confirm-action-btn').onclick = () => { closeModal('confirm-modal'); fn(); };
  openModal('confirm-modal');
}

async function loadGames() {
  const q = document.getElementById('game-search').value;
  const g = document.getElementById('game-genre').value;
  const t = document.getElementById('game-trending').value;
  const d = await api({ action:'list_games', page:currentPage, search:q, genre:g, trending:t });
  const tb = document.getElementById('games-body');

  if (!d.success) { tb.innerHTML='<tr><td colspan="11" style="color:var(--neon-pink);padding:1rem">Failed to load.</td></tr>'; return; }
  document.getElementById('games-count').textContent = `${d.total||0} games`;

  if (!d.games.length) {
    tb.innerHTML='<tr><td colspan="11" style="text-align:center;padding:2rem;color:var(--text-dim)">No games found.</td></tr>';
    document.getElementById('games-pagination').innerHTML=''; return;
  }

  tb.innerHTML = d.games.map(g => `
    <tr>
      <td style="color:var(--text-dim)">${g.id}</td>
      <td>
        ${g.image_url
          ? `<img src="${g.image_url}" style="width:48px;height:32px;object-fit:cover;border-radius:4px;" onerror="this.replaceWith('🎮')">`
          : '<span style="font-size:1.5rem">🎮</span>'}
      </td>
      <td><strong>${g.title}</strong></td>
      <td><span class="badge badge-user">${g.genre}</span></td>
      <td style="color:var(--neon-gold)">★ ${g.rating}</td>
      <td>${g.release_date||'—'}</td>
      <td style="font-size:.78rem;color:var(--text-dim)">
        ${g.min_ram?g.min_ram+'GB RAM':''} ${g.min_gpu?'· '+g.min_gpu:''}
      </td>
      <td>
        ${g.cracked_link
          ? `<a href="${g.cracked_link}" target="_blank" rel="noopener noreferrer"
               title="Open cracked link"
               style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;
                      border-radius:20px;font-size:.72rem;font-weight:700;letter-spacing:.5px;
                      text-decoration:none;color:#39ff14;
                      background:rgba(57,255,20,.12);border:1px solid rgba(57,255,20,.35);
                      transition:background .2s;"
               onmouseover="this.style.background='rgba(57,255,20,.22)'"
               onmouseout="this.style.background='rgba(57,255,20,.12)'">
               ⚡ Cracked
             </a>`
          : `<span style="font-size:.72rem;color:var(--text-dim);padding:.25rem .5rem;
                          border-radius:20px;background:rgba(255,255,255,.04);
                          border:1px solid rgba(255,255,255,.07);">— None</span>`
        }
      </td>
      <td><span class="badge ${g.is_trending?'badge-trending':'badge-normal'}">${g.is_trending?'🔥 Yes':'No'}</span></td>
      <td>${(g.views||0).toLocaleString()}</td>
      <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="btn btn-warning btn-sm btn-icon" title="Toggle Trending" onclick="toggleTrending(${g.id})">🔥</button>
        <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick="openEditGame(${g.id})">✏️</button>
        <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteGame(${g.id},'${(g.title||'').replace(/'/g,"\\'")}')">✕</button>
      </td>
    </tr>`).join('');

  renderPagination(d.pages, currentPage);
}

function openAddGame() {
  document.getElementById('game-modal-title').textContent = 'ADD GAME';
  document.getElementById('game-save-btn').textContent = 'Add Game';
  document.getElementById('game-edit-id').value = '';
  ['gf-title','gf-desc','gf-image','gf-gpu','gf-cpu','gf-crack'].forEach(id=>{const el=document.getElementById(id);if(el)el.value=''}); clearImage(); document.getElementById('gf-image-url-input')&&(document.getElementById('gf-image-url-input').value='');
  ['gf-rating','gf-ram','gf-storage'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('gf-genre').value='';
  document.getElementById('gf-release').value='';
  document.getElementById('gf-trending').checked=false;
  openModal('game-modal');
}

async function openEditGame(id) {
  const d = await fetch('../php/recommendation.php?action=game_detail&id='+id).then(r=>r.json());
  if (!d.success||!d.game) { toast('Failed to load','error'); return; }
  const g = d.game;
  document.getElementById('game-modal-title').textContent = 'EDIT GAME';
  document.getElementById('game-save-btn').textContent = 'Save Changes';
  document.getElementById('game-edit-id').value = g.id;
  document.getElementById('gf-title').value = g.title||'';
  document.getElementById('gf-genre').value = g.genre||'';
  document.getElementById('gf-desc').value  = g.description||'';
  document.getElementById('gf-rating').value= g.rating||'';
  document.getElementById('gf-release').value=g.release_date||'';
  document.getElementById('gf-image').value = g.image_url||''; if(g.image_url){ updatePreview(g.image_url); } else { clearImage(); }
  document.getElementById('gf-ram').value   = g.min_ram||'';
  document.getElementById('gf-storage').value=g.min_storage||'';
  document.getElementById('gf-gpu').value   = g.min_gpu||'';
  document.getElementById('gf-cpu').value   = g.min_cpu||'';
  document.getElementById('gf-crack').value = g.cracked_link||'';
  document.getElementById('gf-trending').checked=!!g.is_trending;
  openModal('game-modal');
}

async function saveGame() {
  const id = document.getElementById('game-edit-id').value;
  const title = document.getElementById('gf-title').value.trim();
  const genre = document.getElementById('gf-genre').value;
  if (!title||!genre) { toast('Title and Genre required','error'); return; }
  const payload = {
    title,genre,
    description: document.getElementById('gf-desc').value,
    rating:      document.getElementById('gf-rating').value,
    release_date:document.getElementById('gf-release').value,
    image_url:    document.getElementById('gf-image').value,
    cracked_link: document.getElementById('gf-crack') ? document.getElementById('gf-crack').value : '',
    min_ram:     document.getElementById('gf-ram').value,
    min_storage: document.getElementById('gf-storage').value,
    min_gpu:     document.getElementById('gf-gpu').value,
    min_cpu:     document.getElementById('gf-cpu').value,
    is_trending: document.getElementById('gf-trending').checked?1:0,
  };
  if (id) payload.id = id;
  const d = await apiPost(id?'edit_game':'add_game', payload);
  toast(d.message||(d.success?'Done!':'Error'), d.success?'success':'error');
  if (d.success) { closeModal('game-modal'); loadGames(); }
}

async function toggleTrending(id) {
  const d = await apiPost('toggle_trending',{id});
  toast(d.success?(d.trending?'🔥 Marked trending':'Removed'):'Error', d.success?'success':'error');
  if (d.success) loadGames();
}

function deleteGame(id, title) {
  confirmAction(`Delete "${title}"?`, async () => {
    const d = await apiPost('delete_game',{id});
    toast(d.message||'Done', d.success?'success':'error');
    if (d.success) loadGames();
  });
}

function renderPagination(total, current) {
  const c = document.getElementById('games-pagination');
  if (!c||total<=1) { if(c) c.innerHTML=''; return; }
  let html = `<button class="page-btn" ${current<=1?'disabled':''} onclick="gotoPage(${current-1})">‹ Prev</button>`;
  for (let i=Math.max(1,current-2);i<=Math.min(total,current+2);i++)
    html+=`<button class="page-btn ${i===current?'active':''}" onclick="gotoPage(${i})">${i}</button>`;
  html+=`<button class="page-btn" ${current>=total?'disabled':''} onclick="gotoPage(${current+1})">Next ›</button>`;
  c.innerHTML=html;
}

function gotoPage(n) { currentPage=n; loadGames(); }
function debounce(fn,d){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),d);};}

startClock();
loadGames();

  /* ── Image Upload JS ──────────────────────────────── */
  function switchImgTab(tab) {
    document.getElementById('tab-upload').classList.toggle('active', tab==='upload');
    document.getElementById('tab-url').classList.toggle('active',    tab==='url');
    document.getElementById('img-panel-upload').style.display = tab==='upload' ? '' : 'none';
    document.getElementById('img-panel-url').style.display    = tab==='url'    ? '' : 'none';
  }

  function handleDrop(e) {
    e.preventDefault();
    document.getElementById('upload-drop').classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) uploadFile(file);
  }

  function handleFileSelect(input) {
    if (input.files && input.files[0]) uploadFile(input.files[0]);
  }

  async function uploadFile(file) {
    const MAX = 3 * 1024 * 1024;
    const allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    if (file.size > MAX)             { toast('Max image size is 3 MB', 'error'); return; }
    if (!allowed.includes(file.type)){ toast('Only JPG, PNG, WEBP or GIF allowed', 'error'); return; }

    // Show progress bar
    const prog  = document.getElementById('upload-progress');
    const bar   = document.getElementById('upload-bar');
    const label = document.getElementById('upload-status');
    prog.style.display = '';
    bar.style.width = '30%';
    label.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('cover', file);

    try {
      bar.style.width = '70%';
      const res  = await fetch('../php/upload_image.php', { method:'POST', body: fd });
      const data = await res.json();
      bar.style.width = '100%';

      if (data.success) {
        label.textContent = '✓ Uploaded!';
        label.style.color = 'var(--neon-green)';
        document.getElementById('gf-image').value = data.url;
        updatePreview(data.url);
        setTimeout(() => { prog.style.display = 'none'; bar.style.width='0'; label.style.color=''; }, 2000);
      } else {
        label.textContent = '✕ ' + (data.message || 'Upload failed');
        label.style.color = 'var(--neon-pink)';
      }
    } catch {
      label.textContent = '✕ Upload failed. Check XAMPP is running.';
      label.style.color = 'var(--neon-pink)';
    }
  }

  function updatePreview(url) {
    if (!url) { clearImage(); return; }
    const wrap = document.getElementById('img-preview-wrap');
    const img  = document.getElementById('img-preview');
    img.src    = url;
    wrap.style.display = '';
    img.onerror = () => { wrap.style.display='none'; };
  }

  function clearImage() {
    document.getElementById('gf-image').value = '';
    document.getElementById('img-preview-wrap').style.display = 'none';
    document.getElementById('img-preview').src = '';
    const fi = document.getElementById('gf-file');
    if (fi) fi.value = '';
  }
</script>
</body>
</html>