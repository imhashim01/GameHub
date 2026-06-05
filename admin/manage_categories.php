<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Manage Categories — GameHub Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin.css"/>
<style>
body{font-family:'Rajdhani',sans-serif;background:#080b12;color:#e8f4ff;margin:0;}
body::before{content:'';position:fixed;inset:0;background:linear-gradient(rgba(0,245,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.025) 1px,transparent 1px);background-size:40px 40px;z-index:0;pointer-events:none;}
.wrap{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;position:relative;z-index:1;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;background:rgba(8,11,18,.95);border-bottom:1px solid rgba(0,245,255,.08);position:sticky;top:0;z-index:100;backdrop-filter:blur(14px);}
.topbar-logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;background:linear-gradient(135deg,#00f5ff,#bf00ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:2px;}
.topbar-nav{display:flex;align-items:center;gap:.5rem;}
.topbar-nav a{padding:.4rem .9rem;border-radius:7px;color:#8ba3c0;text-decoration:none;font-size:.82rem;font-weight:600;transition:all .2s;border:1px solid transparent;}
.topbar-nav a:hover{color:#00f5ff;border-color:rgba(0,245,255,.2);}
.topbar-nav a.active{color:#00f5ff;background:rgba(0,245,255,.08);border-color:rgba(0,245,255,.2);}
.page-grid{display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;}
.glass-card{background:rgba(10,14,24,.88);border:1px solid rgba(0,245,255,.08);border-radius:12px;overflow:hidden;}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid rgba(0,245,255,.06);}
.card-title{font-family:'Orbitron',monospace;font-size:.75rem;color:#00f5ff;letter-spacing:2px;text-transform:uppercase;}
.card-body{padding:1.5rem;}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem;}
.tbl th{padding:.8rem 1rem;text-align:left;font-size:.68rem;color:#3a4f6a;letter-spacing:2px;text-transform:uppercase;border-bottom:1px solid rgba(0,245,255,.06);}
.tbl td{padding:.9rem 1rem;border-bottom:1px solid rgba(0,245,255,.04);color:#8ba3c0;vertical-align:middle;}
.tbl tr:hover td{background:rgba(0,245,255,.025);}
.tbl tr:last-child td{border-bottom:none;}
.form-group{margin-bottom:1.1rem;}
.form-label{display:block;font-size:.72rem;color:#8ba3c0;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:.4rem;}
.form-control{width:100%;padding:.65rem 1rem;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.1);border-radius:8px;color:#e8f4ff;font-family:'Rajdhani',sans-serif;font-size:.9rem;outline:none;transition:all .2s;}
.form-control:focus{border-color:#00f5ff;box-shadow:0 0 0 3px rgba(0,245,255,.1);}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.6rem 1.2rem;border-radius:8px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.85rem;cursor:pointer;transition:all .2s;border:none;}
.btn-primary{background:linear-gradient(135deg,#00f5ff,rgba(0,245,255,.7));color:#080b12;width:100%;}
.btn-primary:hover{box-shadow:0 0 20px rgba(0,245,255,.35);}
.btn-danger{background:rgba(255,0,144,.12);color:#ff0090;border:1px solid rgba(255,0,144,.25);}
.btn-danger:hover{background:rgba(255,0,144,.25);}
.btn-warning{background:rgba(255,215,0,.12);color:#ffd700;border:1px solid rgba(255,215,0,.25);}
.btn-warning:hover{background:rgba(255,215,0,.25);}
.btn-sm{padding:.3rem .65rem;font-size:.75rem;}
.emoji-picker{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.5rem;}
.emoji-opt{width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;cursor:pointer;border-radius:8px;border:1px solid rgba(0,245,255,.1);transition:all .2s;background:rgba(0,245,255,.03);}
.emoji-opt:hover,.emoji-opt.selected{border-color:#00f5ff;background:rgba(0,245,255,.1);}
.cat-count{display:inline-block;padding:.15rem .5rem;border-radius:12px;font-size:.72rem;background:rgba(0,245,255,.1);color:#00f5ff;border:1px solid rgba(0,245,255,.15);}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(6px);}
.modal-overlay.open{display:flex;}
.modal{background:#0d1220;border:1px solid rgba(0,245,255,.25);border-radius:16px;padding:2rem;width:min(380px,94vw);box-shadow:0 0 60px rgba(0,245,255,.1);}
.modal-title{font-family:'Orbitron',monospace;font-size:.85rem;color:#00f5ff;letter-spacing:2px;margin-bottom:1rem;}
#toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;}
.toast{padding:.75rem 1.25rem;border-radius:10px;font-size:.85rem;font-weight:600;animation:toastin .3s ease;}
.toast.success{background:rgba(57,255,20,.15);border:1px solid rgba(57,255,20,.4);color:#39ff14;}
.toast.error{background:rgba(255,0,144,.15);border:1px solid rgba(255,0,144,.4);color:#ff0090;}
.toast.info{background:rgba(0,245,255,.12);border:1px solid rgba(0,245,255,.3);color:#00f5ff;}
@keyframes toastin{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
.loader-ring{width:36px;height:36px;border:3px solid rgba(0,245,255,.1);border-top-color:#00f5ff;border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:768px){.page-grid{grid-template-columns:1fr;}}
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
    <a class="nav-item" href="manage_reviews.php"><span class="icon">⭐</span> Manage Reviews</a>
    <a class="nav-item active" href="manage_categories.php"><span class="icon">🏷️</span> Categories</a>
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
    <a href="manage_reviews.php">⭐ Reviews</a>
    <a href="manage_categories.php" class="active">🏷️ Categories</a>
    <a href="analytics.php">📈 Analytics</a>
    <a href="../php/logout.php" style="color:#ff0090">⏻ Logout</a>
  </nav>
</div>

<div class="wrap">
  <div class="page-grid">

    <!-- Categories Table -->
    <div class="glass-card">
      <div class="card-header">
        <span class="card-title">🏷️ All Categories</span>
        <span id="cat-count-label" style="font-size:.8rem;color:#8ba3c0"></span>
      </div>
      <table class="tbl">
        <thead><tr>
          <th>#</th><th>Icon</th><th>Name</th><th>Games</th><th>Actions</th>
        </tr></thead>
        <tbody id="cats-body">
          <tr><td colspan="5" style="text-align:center;padding:3rem">
            <div style="display:flex;justify-content:center"><div class="loader-ring"></div></div>
          </td></tr>
        </tbody>
      </table>
    </div>

    <!-- Add / Edit Form -->
    <div>
      <div class="glass-card">
        <div class="card-header"><span class="card-title" id="form-title">＋ Add Category</span></div>
        <div class="card-body">
          <input type="hidden" id="edit-id"/>

          <div class="form-group">
            <label class="form-label">Category Name *</label>
            <input class="form-control" id="cat-name" placeholder="e.g. Battle Royale"/>
          </div>

          <div class="form-group">
            <label class="form-label">Icon (Emoji)</label>
            <input class="form-control" id="cat-icon" placeholder="🎯" maxlength="4" style="font-size:1.3rem"/>
            <div class="emoji-picker" id="emoji-picker"></div>
          </div>

          <button class="btn btn-primary" onclick="saveCategory()">
            <span id="save-btn-text">＋ Add Category</span>
          </button>

          <button class="btn btn-sm" id="cancel-edit-btn"
                  style="display:none;width:100%;margin-top:.6rem;background:transparent;color:#8ba3c0;border:1px solid rgba(0,245,255,.1)"
                  onclick="resetForm()">✕ Cancel Edit</button>
        </div>
      </div>

      <!-- Info box -->
      <div style="margin-top:1rem;padding:1rem 1.25rem;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.08);border-radius:10px;font-size:.8rem;color:#8ba3c0;line-height:1.7">
        💡 <strong style="color:#00f5ff">Tips:</strong><br>
        Categories are used to organise and filter games across the platform.<br>
        Each game must belong to a category (genre). Deleting a category may affect existing games.
      </div>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:.75rem">⚠️</div>
    <div class="modal-title" style="justify-content:center">CONFIRM DELETE</div>
    <p id="confirm-msg" style="color:#8ba3c0;margin-bottom:1.5rem"></p>
    <div style="display:flex;gap:.75rem;justify-content:center">
      <button class="btn btn-sm" style="background:transparent;color:#8ba3c0;border:1px solid rgba(0,245,255,.1)"
              onclick="document.getElementById('confirm-modal').classList.remove('open')">Cancel</button>
      <button class="btn btn-danger btn-sm" id="confirm-btn">Delete</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
const API     = '../php/admin.php';
const EMOJIS  = ['🎮','⚔️','🧙','🔫','♟️','⚽','🏎️','👻','🗺️','🏗️','🥊','🧩','🎯','🏆','🕵️','🌲','👾','🤖','🚀','🏹','🛡️','💣'];
let categories = [];

// Render emoji picker
document.getElementById('emoji-picker').innerHTML =
  EMOJIS.map(e=>`<div class="emoji-opt" onclick="pickEmoji('${e}')">${e}</div>`).join('');

function pickEmoji(e) {
  document.getElementById('cat-icon').value = e;
  document.querySelectorAll('.emoji-opt').forEach(el => el.classList.toggle('selected', el.textContent===e));
}

// ── Load Categories ────────────────────────────────
async function loadCategories() {
  const data = await fetch(API+'?action=list_categories').then(r=>r.json());
  if (!data.success) return;
  categories = data.categories;
  document.getElementById('cat-count-label').textContent = `${categories.length} categories`;

  // Also get game count per genre
  const gdata = await fetch('../php/recommendation.php?action=all_games&page=1').then(r=>r.json()).catch(()=>({games:[]}));
  const genreCounts = {};
  (gdata.games||[]).forEach(g => { genreCounts[g.genre] = (genreCounts[g.genre]||0)+1; });

  document.getElementById('cats-body').innerHTML = categories.map(c => `
    <tr>
      <td style="color:#3a4f6a">${c.id}</td>
      <td style="font-size:1.6rem">${c.icon||'🎮'}</td>
      <td><strong style="color:#e8f4ff">${c.name}</strong></td>
      <td><span class="cat-count">${genreCounts[c.name]||0} games</span></td>
      <td style="display:flex;gap:.4rem">
        <button class="btn btn-warning btn-sm" onclick="editCategory(${c.id})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.id},'${esc(c.name)}')">✕</button>
      </td>
    </tr>`).join('');
}

// ── Save (Add or Edit) ─────────────────────────────
async function saveCategory() {
  const id   = document.getElementById('edit-id').value;
  const name = document.getElementById('cat-name').value.trim();
  const icon = document.getElementById('cat-icon').value.trim() || '🎮';
  if (!name) { toast('Category name is required', 'error'); return; }

  const action = id ? 'edit_category' : 'add_category';
  const body   = new URLSearchParams({ action, name, icon });
  if (id) body.set('id', id);

  const data = await fetch(API, { method:'POST', body }).then(r=>r.json());

  // Fallback: add_category exists, edit_category may not — handle gracefully
  if (data.success) {
    toast(data.message || 'Saved!', 'success');
    resetForm();
    loadCategories();
  } else {
    toast(data.message || 'Failed to save.', 'error');
  }
}

// ── Edit ───────────────────────────────────────────
function editCategory(id) {
  const c = categories.find(x => x.id == id || x.id === id);
  if (!c) return;
  document.getElementById('edit-id').value   = c.id;
  document.getElementById('cat-name').value  = c.name;
  document.getElementById('cat-icon').value  = c.icon||'🎮';
  document.getElementById('form-title').textContent    = '✏️ Edit Category';
  document.getElementById('save-btn-text').textContent = 'Save Changes';
  document.getElementById('cancel-edit-btn').style.display = '';
  pickEmoji(c.icon||'🎮');
  window.scrollTo({top:0,behavior:'smooth'});
}

function resetForm() {
  document.getElementById('edit-id').value  = '';
  document.getElementById('cat-name').value = '';
  document.getElementById('cat-icon').value = '';
  document.getElementById('form-title').textContent    = '＋ Add Category';
  document.getElementById('save-btn-text').textContent = '＋ Add Category';
  document.getElementById('cancel-edit-btn').style.display = 'none';
  document.querySelectorAll('.emoji-opt').forEach(e=>e.classList.remove('selected'));
}

// ── Delete ─────────────────────────────────────────
function deleteCategory(id, name) {
  document.getElementById('confirm-msg').innerHTML =
    `Delete category <strong style="color:#e8f4ff">${name}</strong>?<br>
     <span style="font-size:.8rem">Games in this category will lose their category assignment.</span>`;
  document.getElementById('confirm-btn').onclick = async () => {
    document.getElementById('confirm-modal').classList.remove('open');
    const body = new URLSearchParams({ action:'delete_category', id });
    const data = await fetch(API, { method:'POST', body }).then(r=>r.json());
    toast(data.message||(data.success?'Deleted':'Failed'), data.success?'success':'error');
    if (data.success) loadCategories();
  };
  document.getElementById('confirm-modal').classList.add('open');
}

function esc(s) { return String(s).replace(/'/g,"\\'"); }
function toast(msg,type='info') {
  const c=document.getElementById('toast-container');
  const t=document.createElement('div'); t.className=`toast ${type}`; t.textContent=msg;
  c.appendChild(t);
  setTimeout(()=>{t.style.animation='toastin .3s ease reverse';setTimeout(()=>t.remove(),300);},3500);
}

(async()=>{
  try{
    const s=await fetch('../php/session_check.php').then(r=>r.json());
    if(!s.logged_in||s.role!=='admin') window.location.href='../login.html';
  }catch{window.location.href='../login.html';}
  loadCategories();
})();
</script>
</main>
</body>
</html>