<?php
/* ============================================================
   GameHub — admin/add_game.php
   Dedicated full-page Add / Edit Game form
   Usage:
     add_game.php          → Add new game
     add_game.php?id=123   → Edit existing game
   ============================================================ */

require_once __DIR__ . '/../php/db.php';
startSession();

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.html');
    exit;
}

$adminUsername = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $editId > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $isEdit ? 'Edit Game' : 'Add Game' ?> — GameHub Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
<style>
  /* ── Page-specific overrides ── */
  .form-page { max-width: 780px; margin: 0 auto; }

  .field-section {
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border-dim);
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
  }
  .field-section-title {
    font-family: 'Orbitron', monospace;
    font-size: .7rem; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase;
    color: var(--neon-cyan);
    margin-bottom: 1.1rem;
    display: flex; align-items: center; gap: .5rem;
  }

  /* Cracked section special styling */
  .crack-section {
    background: rgba(57,255,20,.05);
    border: 1px solid rgba(57,255,20,.4);
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
  }
  .crack-section-title {
    font-family: 'Orbitron', monospace;
    font-size: .7rem; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase;
    color: #39ff14;
    margin-bottom: 1rem;
    display: flex; align-items: center; gap: .6rem;
  }
  .crack-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #39ff14; box-shadow: 0 0 8px #39ff14;
    animation: cdot 1.5s ease-in-out infinite;
    flex-shrink: 0;
  }
  @keyframes cdot {
    0%,100% { opacity:1; box-shadow:0 0 5px #39ff14; }
    50%      { opacity:.3; box-shadow:0 0 14px #39ff14; }
  }
  .crack-hint {
    font-size: .78rem; color: rgba(57,255,20,.5);
    margin-top: .5rem; line-height: 1.6;
  }

  /* Action bar */
  .action-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border-dim);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
  }
  .action-bar-left { display: flex; align-items: center; gap: 1rem; }

  /* Image upload */
  .img-tabs { display:flex; gap:.5rem; margin-bottom:.6rem; }
  .img-tab {
    padding:.35rem .85rem; border-radius:8px; font-size:.78rem; font-weight:600;
    font-family:'Rajdhani',sans-serif; cursor:pointer; transition:all .2s;
    border:1px solid var(--border-dim); background:transparent; color:var(--text-mid);
  }
  .img-tab.active { background:rgba(0,245,255,.1); border-color:var(--neon-cyan); color:var(--neon-cyan); }
  .upload-drop {
    border: 2px dashed var(--border-dim); border-radius: 10px;
    padding: 1.5rem; text-align: center; cursor: pointer;
    transition: all .2s;
  }
  .upload-drop:hover, .upload-drop.drag-over { border-color: var(--neon-cyan); background: rgba(0,245,255,.04); }
  .img-preview-wrap { display:none; margin-top:.75rem; position:relative; }
  .img-preview-wrap img { width:100%; max-height:200px; object-fit:cover; border-radius:10px; border:1px solid var(--border-glow); }
  .img-clear-btn {
    position:absolute; top:.4rem; right:.4rem;
    background:rgba(255,0,144,.85); border:none; color:#fff;
    border-radius:50%; width:26px; height:26px; cursor:pointer; font-size:.85rem;
  }
  .upload-progress { display:none; margin-top:.5rem; }
  .upload-bar-wrap { height:4px; background:var(--border-dim); border-radius:4px; overflow:hidden; }
  .upload-bar { height:100%; width:0; background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple)); transition:width .3s; }
  .upload-status { font-size:.75rem; color:var(--neon-cyan); margin-top:.3rem; }

  /* Toast */
  #toast {
    position:fixed; bottom:2rem; right:2rem; z-index:9999;
    padding:.75rem 1.5rem; border-radius:10px;
    font-family:'Rajdhani',sans-serif; font-size:.9rem; font-weight:600;
    opacity:0; transform:translateY(10px); transition:all .3s; pointer-events:none;
  }
  #toast.show { opacity:1; transform:translateY(0); }
  #toast.success { background:rgba(0,255,136,.15); border:1px solid rgba(0,255,136,.4); color:#00ff88; }
  #toast.error   { background:rgba(255,0,80,.15);  border:1px solid rgba(255,0,80,.4);  color:#ff0050; }
</style>
</head>
<body class="admin-layout">

<!-- SIDEBAR -->
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

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div class="topbar-title" id="page-title"><?= $isEdit ? '✏️ EDIT GAME' : '＋ ADD NEW GAME' ?></div>
    <div class="topbar-right">
      <span class="topbar-time" id="live-time"></span>
      <a href="manage_games.php" class="btn-logout" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);color:var(--text-mid);">← Back</a>
      <a href="../php/logout.php" class="btn-logout">⏻ Logout</a>
    </div>
  </div>

  <div class="content">
    <div class="form-page">

      <!-- ── BASIC INFO ── -->
      <div class="field-section">
        <div class="field-section-title">🎮 Basic Information</div>
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
          <textarea class="form-control" id="gf-desc" placeholder="Game description…" style="min-height:90px;resize:vertical;"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Rating (0–5)</label>
            <input class="form-control" id="gf-rating" type="number" min="0" max="5" step="0.1" placeholder="4.5"/>
          </div>
          <div class="form-group">
            <label class="form-label">Release Date</label>
            <input class="form-control" id="gf-release" type="date"/>
          </div>
        </div>
      </div>

      <!-- ── CRACKED VERSION URL ── -->
      <div class="crack-section">
        <div class="crack-section-title">
          <span class="crack-dot"></span>
          ⚡ Cracked Version URL
          <span style="font-size:.7rem;color:rgba(57,255,20,.4);font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span>
        </div>
        <input class="form-control" id="gf-crack"
               placeholder="https://… paste the cracked / download page URL here"
               style="border-color:rgba(57,255,20,.4);background:rgba(57,255,20,.04);font-size:.95rem;padding:.75rem 1rem;"/>
        <div class="crack-hint">
          💡 When saved, a green <strong style="color:#39ff14;">⚡ Download Cracked Version</strong>
          button will appear on the game's detail page and link directly to this URL.
        </div>
      </div>

      <!-- ── COVER IMAGE ── -->
      <div class="field-section">
        <div class="field-section-title">🖼️ Cover Image</div>
        <div class="img-tabs">
          <button type="button" class="img-tab active" id="tab-upload" onclick="switchImgTab('upload')">📁 Upload File</button>
          <button type="button" class="img-tab" id="tab-url" onclick="switchImgTab('url')">🔗 Paste URL</button>
        </div>
        <div id="img-panel-upload">
          <div class="upload-drop" id="upload-drop"
               onclick="document.getElementById('gf-file').click()"
               ondragover="event.preventDefault();this.classList.add('drag-over')"
               ondragleave="this.classList.remove('drag-over')"
               ondrop="handleDrop(event)">
            <div style="font-size:2rem;">🖼️</div>
            <div style="font-size:.85rem;color:var(--text-mid);margin-top:.4rem;">Click or drag &amp; drop image here</div>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:.2rem;">JPG · PNG · WEBP · GIF — max 3 MB</div>
          </div>
          <input type="file" id="gf-file" accept="image/*" style="display:none" onchange="handleFileSelect(this)">
          <div id="upload-progress" class="upload-progress">
            <div class="upload-bar-wrap"><div id="upload-bar" class="upload-bar"></div></div>
            <div id="upload-status" class="upload-status">Uploading…</div>
          </div>
        </div>
        <div id="img-panel-url" style="display:none;">
          <input class="form-control" id="gf-image-url-input" placeholder="https://example.com/cover.jpg"
                 oninput="document.getElementById('gf-image').value=this.value; updatePreview(this.value)"/>
        </div>
        <input type="hidden" id="gf-image"/>
        <div id="img-preview-wrap" class="img-preview-wrap">
          <img id="img-preview" src="" alt="Cover preview">
          <button type="button" class="img-clear-btn" onclick="clearImage()">✕</button>
        </div>
      </div>

      <!-- ── PC REQUIREMENTS ── -->
      <div class="field-section">
        <div class="field-section-title">🖥️ PC Requirements (Minimum)</div>
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
      </div>

      <!-- ── ACTION BAR ── -->
      <div class="action-bar">
        <div class="action-bar-left">
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;">
            <input type="checkbox" id="gf-trending" style="width:16px;height:16px;accent-color:var(--neon-cyan);"/>
            <span style="font-size:.9rem;color:var(--text-mid);font-weight:600;">Mark as Trending 🔥</span>
          </label>
        </div>
        <div style="display:flex;gap:.75rem;">
          <a href="manage_games.php" class="btn btn-ghost">Cancel</a>
          <button class="btn btn-primary" onclick="saveGame()" id="save-btn">
            <?= $isEdit ? '💾 Save Changes' : '＋ Add Game' ?>
          </button>
        </div>
      </div>

    </div><!-- /form-page -->
  </div><!-- /content -->
</main>

<!-- Toast -->
<div id="toast"></div>

<script>
/* ── CONFIG ── */
const EDIT_ID = <?= $editId ?>;
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
const API     = '../php/admin.php';

/* ── LIVE CLOCK ── */
(function tick(){
  const el = document.getElementById('live-time');
  if(el) el.textContent = new Date().toLocaleTimeString();
  setTimeout(tick, 1000);
})();

/* ── TOAST ── */
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'show ' + type;
  setTimeout(() => t.className = '', 3000);
}

/* ── API ── */
async function apiPost(action, body) {
  const r = await fetch(API, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action, ...body})
  });
  return r.json();
}

/* ── IMAGE TABS ── */
function switchImgTab(tab) {
  document.getElementById('img-panel-upload').style.display = tab==='upload'?'':'none';
  document.getElementById('img-panel-url').style.display    = tab==='url'?'':'none';
  document.getElementById('tab-upload').classList.toggle('active', tab==='upload');
  document.getElementById('tab-url').classList.toggle('active',    tab==='url');
}

/* ── IMAGE PREVIEW ── */
function updatePreview(url) {
  const wrap = document.getElementById('img-preview-wrap');
  const img  = document.getElementById('img-preview');
  if (url) { img.src = url; wrap.style.display = 'block'; }
  else      { wrap.style.display = 'none'; img.src = ''; }
}
function clearImage() {
  document.getElementById('gf-image').value = '';
  document.getElementById('gf-image-url-input') && (document.getElementById('gf-image-url-input').value='');
  updatePreview('');
  document.getElementById('upload-drop').innerHTML = `
    <div style="font-size:2rem;">🖼️</div>
    <div style="font-size:.85rem;color:var(--text-mid);margin-top:.4rem;">Click or drag &amp; drop image here</div>
    <div style="font-size:.72rem;color:var(--text-dim);margin-top:.2rem;">JPG · PNG · WEBP · GIF — max 3 MB</div>`;
}

/* ── FILE UPLOAD ── */
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('upload-drop').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) uploadFile(file);
}
function handleFileSelect(input) {
  if (input.files[0]) uploadFile(input.files[0]);
}
async function uploadFile(file) {
  if (file.size > 3*1024*1024) { toast('File too large (max 3 MB)','error'); return; }
  const prog = document.getElementById('upload-progress');
  const bar  = document.getElementById('upload-bar');
  const stat = document.getElementById('upload-status');
  prog.style.display = 'block'; bar.style.width = '30%'; stat.textContent = 'Uploading…';
  const fd = new FormData(); fd.append('image', file);
  try {
    const r = await fetch('../admin/upload_image.php', {method:'POST', body:fd});
    const d = await r.json();
    bar.style.width = '100%';
    if (d.success) {
      stat.textContent = '✓ Uploaded';
      document.getElementById('gf-image').value = d.url;
      updatePreview(d.url);
      document.getElementById('upload-drop').innerHTML = `<div style="font-size:.85rem;color:var(--neon-cyan)">✓ ${file.name}</div>`;
    } else {
      stat.textContent = d.message || 'Upload failed'; stat.style.color = '#ff0050';
    }
  } catch { stat.textContent = 'Upload error'; stat.style.color='#ff0050'; }
  setTimeout(()=>{ prog.style.display='none'; bar.style.width='0'; }, 2500);
}

/* ── LOAD GAME FOR EDIT ── */
async function loadEditGame() {
  if (!IS_EDIT) return;
  try {
    const d = await fetch('../php/recommendation.php?action=game_detail&id='+EDIT_ID).then(r=>r.json());
    if (!d.success || !d.game) { toast('Failed to load game','error'); return; }
    const g = d.game;
    document.getElementById('gf-title').value   = g.title        || '';
    document.getElementById('gf-genre').value   = g.genre        || '';
    document.getElementById('gf-desc').value    = g.description  || '';
    document.getElementById('gf-rating').value  = g.rating       || '';
    document.getElementById('gf-release').value = g.release_date || '';
    document.getElementById('gf-ram').value     = g.min_ram      || '';
    document.getElementById('gf-storage').value = g.min_storage  || '';
    document.getElementById('gf-gpu').value     = g.min_gpu      || '';
    document.getElementById('gf-cpu').value     = g.min_cpu      || '';
    document.getElementById('gf-crack').value   = g.cracked_link || '';
    document.getElementById('gf-trending').checked = !!g.is_trending;
    if (g.image_url) {
      document.getElementById('gf-image').value = g.image_url;
      updatePreview(g.image_url);
      switchImgTab('url');
      document.getElementById('gf-image-url-input').value = g.image_url;
    }
  } catch(e) { toast('Error loading game','error'); }
}
loadEditGame();

/* ── SAVE GAME ── */
async function saveGame() {
  const title = document.getElementById('gf-title').value.trim();
  const genre = document.getElementById('gf-genre').value;
  if (!title || !genre) { toast('Title and Genre are required','error'); return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';

  const payload = {
    title, genre,
    description:  document.getElementById('gf-desc').value,
    rating:       document.getElementById('gf-rating').value,
    release_date: document.getElementById('gf-release').value,
    image_url:    document.getElementById('gf-image').value,
    cracked_link: document.getElementById('gf-crack').value.trim(),
    min_ram:      document.getElementById('gf-ram').value,
    min_storage:  document.getElementById('gf-storage').value,
    min_gpu:      document.getElementById('gf-gpu').value,
    min_cpu:      document.getElementById('gf-cpu').value,
    is_trending:  document.getElementById('gf-trending').checked ? 1 : 0,
  };
  if (IS_EDIT) payload.id = EDIT_ID;

  try {
    const d = await apiPost(IS_EDIT ? 'edit_game' : 'add_game', payload);
    toast(d.message || (d.success ? '✓ Saved!' : 'Error'), d.success ? 'success' : 'error');
    if (d.success) {
      setTimeout(() => window.location.href = 'manage_games.php', 1200);
    }
  } catch { toast('Network error','error'); }
  finally { btn.disabled = false; btn.textContent = IS_EDIT ? '💾 Save Changes' : '＋ Add Game'; }
}
</script>
</body>
</html>