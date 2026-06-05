/* ============================================================
   GameHub — wishlist.js
   Wishlist: server-synced with localStorage fallback, rich UI
   ============================================================ */

const WishlistModule = (() => {

  const LOCAL_KEY = 'gamehub_wishlist';

  // ── Local Storage helpers ─────────────────────────────────
  function getLocal() {
    try { return JSON.parse(localStorage.getItem(LOCAL_KEY)) || []; }
    catch { return []; }
  }
  function saveLocal(list) {
    localStorage.setItem(LOCAL_KEY, JSON.stringify(list));
  }

  // ── Toggle wishlist item ──────────────────────────────────
  async function toggle(gameId, title) {
    gameId = parseInt(gameId);
    const list   = getLocal();
    const exists = list.find(g => g.id === gameId);

    if (exists) {
      saveLocal(list.filter(g => g.id !== gameId));
      showToast(`Removed "${title}" from wishlist`, 'info');
      updateButtons(gameId, false);
    } else {
      list.push({ id: gameId, title, added: Date.now() });
      saveLocal(list);
      showToast(`Added "${title}" to wishlist ♥`, 'success');
      updateButtons(gameId, true);
    }

    // Sync with server
    try {
      const data = await apiPost('php/wishlist_process.php', {
        action: exists ? 'remove' : 'add',
        game_id: gameId
      });
      if (data.auth === false) {
        showToast('Login to save across devices', 'info');
      }
    } catch { /* Server unavailable, local only */ }

    updateWishlistBadge();
    if (document.getElementById('wishlist-container')) renderPage();
  }

  // ── Update wishlist heart buttons on page ─────────────────
  function updateButtons(gameId, isWishlisted) {
    document.querySelectorAll(`.add-wishlist[data-id="${gameId}"]`).forEach(btn => {
      btn.style.color = isWishlisted ? 'var(--neon-pink)' : '';
      btn.title = isWishlisted ? 'Remove from wishlist' : 'Add to wishlist';
    });
  }

  // ── Highlight all wishlisted items on page ────────────────
  function highlightWishlisted() {
    const list = getLocal();
    const ids  = new Set(list.map(g => g.id));
    document.querySelectorAll('.add-wishlist').forEach(btn => {
      const id = parseInt(btn.dataset.id);
      btn.style.color = ids.has(id) ? 'var(--neon-pink)' : '';
    });
  }

  // ── Render wishlist page ──────────────────────────────────
  async function renderPage() {
    const container = document.getElementById('wishlist-container');
    if (!container) return;

    // Try server first if logged in
    try {
      const data = await apiGet('php/wishlist_process.php?action=get');
      if (data.success && data.items) {
        // Merge server items into local storage
        const local = getLocal();
        const merged = [...local];
        data.items.forEach(item => {
          if (!merged.find(g => g.id === item.game_id)) {
            merged.push({ id: item.game_id, title: item.title, added: Date.now(), genre: item.genre, rating: item.rating });
          }
        });
        saveLocal(merged);
        renderList(container, merged.map(g => ({
          ...g,
          genre: g.genre || (data.items.find(i => i.game_id === g.id)?.genre || 'Game'),
          rating: g.rating || (data.items.find(i => i.game_id === g.id)?.rating || 0),
        })));
        return;
      }
    } catch {}

    // Fallback to local
    renderList(container, getLocal());
  }

  function renderList(container, list) {
    updateWishlistBadge(list.length);

    if (!list.length) {
      container.innerHTML = `
        <div class="no-results">
          <span class="icon" style="font-size:4rem">💔</span>
          <h3>Your Wishlist is Empty</h3>
          <p>Browse games and click <span style="color:var(--neon-pink)">♥</span> to save your favorites here.</p>
          <a href="games.html" class="btn btn-primary mt-2">Browse Games</a>
        </div>`;
      return;
    }

    container.innerHTML = `
      <div class="wishlist-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <h3 style="font-family:var(--font-display);color:var(--text-prime)">${list.length} Game${list.length !== 1 ? 's' : ''} in Wishlist</h3>
        <button class="btn btn-outline btn-sm" onclick="WishlistModule.clearAll()">Clear All</button>
      </div>
      <div class="wishlist-grid">
        ${list.map(g => createWishlistCard(g)).join('')}
      </div>`;
  }

  function createWishlistCard(g) {
    const emoji  = getGenreEmoji(g.genre || 'Game');
    const rating = parseFloat(g.rating || 0);
    const stars  = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
    const added  = g.added ? timeAgo(g.added) : 'Recently';

    return `
      <div class="glass-card wishlist-item" data-id="${g.id}" style="display:flex;align-items:center;gap:1.25rem;padding:1.25rem">
        <div class="wishlist-item-img" style="font-size:2.5rem;min-width:3.5rem;text-align:center">${emoji}</div>
        <div class="wishlist-item-info" style="flex:1;min-width:0">
          <h4 style="font-family:var(--font-display);color:var(--text-prime);margin:0 0 .3rem;font-size:1rem">${g.title}</h4>
          <div style="display:flex;gap:1rem;font-size:.78rem;color:var(--text-muted)">
            <span>${g.genre || 'Game'}</span>
            ${rating > 0 ? `<span style="color:var(--neon-gold)">${stars} ${rating.toFixed(1)}</span>` : ''}
            <span>Added ${added}</span>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-shrink:0">
          <button class="btn btn-outline btn-sm" onclick="openGameDetail(${g.id})">View</button>
          <button class="btn btn-sm" style="background:rgba(255,0,79,.15);color:#ff004f;border:1px solid rgba(255,0,79,.3)"
                  onclick="WishlistModule.remove(${g.id}, '${escapeHtml(g.title)}')">✕</button>
        </div>
      </div>`;
  }

  // ── Remove single item ────────────────────────────────────
  function remove(gameId, title) {
    gameId = parseInt(gameId);
    saveLocal(getLocal().filter(g => g.id !== gameId));
    showToast(`Removed "${title}"`, 'info');
    updateButtons(gameId, false);
    updateWishlistBadge();
    if (document.getElementById('wishlist-container')) renderPage();
    try { apiPost('php/wishlist_process.php', { action:'remove', game_id:gameId }); } catch {}
  }

  // ── Clear all ─────────────────────────────────────────────
  async function clearAll() {
    if (!confirm('Clear your entire wishlist?')) return;
    saveLocal([]);
    updateWishlistBadge(0);
    showToast('Wishlist cleared', 'info');
    try { await apiPost('php/wishlist_process.php', { action:'clear' }); } catch {}
    if (document.getElementById('wishlist-container')) renderPage();
  }

  // ── Badge count in nav ────────────────────────────────────
  function updateWishlistBadge(countOverride) {
    const n = countOverride !== undefined ? countOverride : getLocal().length;
    document.querySelectorAll('#wishlist-count, .wishlist-badge').forEach(el => {
      el.textContent = n;
      el.style.display = n > 0 ? '' : 'none';
    });
  }

  // ── Sync server → local on login ──────────────────────────
  async function syncFromServer() {
    try {
      const data = await apiGet('php/wishlist_process.php?action=get');
      if (data.success && data.items) {
        const merged = [...getLocal()];
        data.items.forEach(item => {
          if (!merged.find(g => g.id === item.game_id)) {
            merged.push({ id: item.game_id, title: item.title, added: Date.now(), genre: item.genre, rating: item.rating });
          }
        });
        saveLocal(merged);
        updateWishlistBadge(merged.length);
        highlightWishlisted();
      }
    } catch {}
  }

  function count() { return getLocal().length; }

  return { toggle, remove, clearAll, renderPage, syncFromServer, count, highlightWishlisted, updateWishlistBadge };
})();

// ── Time ago helper (also used in recommendation.js) ─────────
function timeAgo(ts) {
  if (!ts) return 'Recently';
  const diff = Date.now() - (typeof ts === 'number' ? ts : new Date(ts).getTime());
  const mins = Math.floor(diff / 60000);
  if (mins < 1)  return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24)  return `${hrs}h ago`;
  return `${Math.floor(hrs / 24)}d ago`;
}

// ── Escape HTML helper ────────────────────────────────────────
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Genre emoji helper ────────────────────────────────────────
function getGenreEmoji(genre) {
  const map = {
    Action:'⚔️', RPG:'🧙', FPS:'🔫', Strategy:'♟️', Sports:'⚽',
    Racing:'🏎️', Horror:'👻', Adventure:'🗺️', Simulation:'🏗️',
    Fighting:'🥊', Puzzle:'🧩', 'Battle Royale':'🎯', MOBA:'🏆',
    Platformer:'🍄', Sandbox:'🏖️', MMO:'🌍', Stealth:'🕵️'
  };
  return map[genre] || '🎮';
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  WishlistModule.updateWishlistBadge();
  WishlistModule.syncFromServer().then(() => {
    WishlistModule.highlightWishlisted();
  });

  if (document.getElementById('wishlist-container')) {
    WishlistModule.renderPage();
  }
});