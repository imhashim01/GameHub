/* ============================================================
   GameHub — recommendation.js
   Smart game discovery: recommendations, trending, search, detail modal
   ============================================================ */

const RecommendationEngine = (() => {

  let allGames   = [];
  let activeGenre = 'All';
  const container = () => document.getElementById('recommendations-grid');

  // ── Load personalised recommendations ───────────────────────
  async function loadGames() {
    const el = container();
    if (!el) return;
    el.innerHTML = `<div class="loader-wrap" style="grid-column:1/-1;text-align:center;padding:3rem">
      <div class="loader-ring"></div>
      <p style="color:var(--text-muted);margin-top:1rem;font-family:var(--font-display);font-size:.85rem;letter-spacing:2px">LOADING GAMES...</p>
    </div>`;
    try {
      const data = await apiGet('php/recommendation.php?action=get_recommendations');
      if (data.success && data.games) {
        allGames = data.games;
        render(allGames);
      } else {
        renderFallback();
      }
    } catch {
      renderFallback();
    }
  }

  // ── Load trending games ───────────────────────────────────
  async function loadTrending() {
    const el = document.getElementById('trending-grid');
    if (!el) return;
    try {
      const data = await apiGet('php/recommendation.php?action=trending');
      if (data.success && data.games.length) {
        el.innerHTML = data.games.map(g => createCard(g, true)).join('');
        bindWishlistButtons(el);
      }
    } catch {}
  }

  // ── Load new releases ────────────────────────────────────
  async function loadNewReleases() {
    const el = document.getElementById('new-releases-grid');
    if (!el) return;
    try {
      const data = await apiGet('php/recommendation.php?action=new_releases');
      if (data.success && data.games.length) {
        el.innerHTML = data.games.map(g => createCard(g)).join('');
        bindWishlistButtons(el);
      }
    } catch {}
  }

  // ── Genre filter ──────────────────────────────────────────
  function filterByGenre(genre) {
    activeGenre = genre;
    document.querySelectorAll('.genre-filter-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.genre === genre);
    });
    if (genre === 'All') {
      render(allGames);
    } else {
      render(allGames.filter(g => g.genre === genre));
    }
  }

  // ── Render grid ───────────────────────────────────────────
  function render(games) {
    const el = container();
    if (!el) return;
    if (!games.length) {
      el.innerHTML = `
        <div class="no-results" style="grid-column:1/-1">
          <span class="icon">🎮</span>
          <h3>No Games Found</h3>
          <p>Try a different genre or search term.</p>
        </div>`;
      return;
    }
    el.innerHTML = games.map(g => createCard(g)).join('');
    bindWishlistButtons(el);
    initScrollReveal();
  }

  // ── Game card HTML ────────────────────────────────────────
  function createCard(game, isTrending = false) {
    const emoji  = getGenreEmoji(game.genre);
    const rating = parseFloat(game.rating || 0);
    const stars  = renderStars(rating);
    const trendBadge = (isTrending || game.is_trending)
      ? `<div class="game-badge trending">🔥 TRENDING</div>` : '';

    return `
      <div class="game-card reveal" data-id="${game.id}">
        ${trendBadge}
        <div class="game-card-img">
          ${game.image_url
            ? `<img src="${game.image_url}" alt="${game.title}" style="width:100%;height:100%;object-fit:cover;border-radius:10px 10px 0 0;" onerror="this.parentElement.innerHTML='<span style=font-size:3.5rem>'+emoji+'</span>'">`
            : `<span style="font-size:3.5rem">${emoji}</span>`}
        </div>
        <div class="game-card-body">
          <div class="game-card-genre">${game.genre || 'Unknown'}</div>
          <div class="game-card-title">${game.title}</div>
          <div class="game-card-meta">
            <span class="game-card-rating">${stars} ${rating.toFixed(1)}</span>
            <span class="game-card-year">${game.release_year || ''}</span>
          </div>
          ${game.description ? `<p class="game-card-desc">${game.description.substring(0,80)}...</p>` : ''}
        </div>
        <div class="game-card-actions">
          <button class="btn btn-outline btn-sm" onclick="openGameDetail(${game.id})">View Details</button>
          <button class="btn btn-primary btn-sm add-wishlist" data-id="${game.id}" data-title="${escapeHtml(game.title)}" onclick="event.stopPropagation();WishlistModule.toggle(${game.id},'${escapeHtml(game.title)}')">♥</button>
        </div>
      </div>`;
  }

  // ── Star renderer ─────────────────────────────────────────
  function renderStars(rating) {
    const full  = Math.floor(rating);
    const half  = rating - full >= 0.5;
    const empty = 5 - full - (half ? 1 : 0);
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
  }

  // ── Bind wishlist buttons ─────────────────────────────────
  function bindWishlistButtons(scope) {
    scope.querySelectorAll('.add-wishlist').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        WishlistModule.toggle(btn.dataset.id, btn.dataset.title);
      });
    });
  }

  // ── Fallback demo data ────────────────────────────────────
  function renderFallback() {
    const demos = [
      { id:1, title:'Cyber Odyssey 2049', genre:'RPG',      rating:4.8, release_year:2024, is_trending:true,  description:'Epic open-world cyberpunk adventure set in the neon-lit mega-city of Neo Tokyo.' },
      { id:2, title:'Phantom Strike',      genre:'FPS',      rating:4.5, release_year:2024, is_trending:false, description:'High-intensity tactical shooter with team-based objectives and destructible environments.' },
      { id:3, title:'Galaxy Commanders',   genre:'Strategy', rating:4.3, release_year:2023, is_trending:true,  description:'Command your fleet across 50 star systems in this epic real-time strategy game.' },
      { id:4, title:'Neon Racer X',        genre:'Racing',   rating:4.6, release_year:2024, is_trending:false, description:'Blazing fast anti-gravity racing through stunning neon environments.' },
      { id:5, title:'Shadow Realm',        genre:'RPG',      rating:4.4, release_year:2023, is_trending:false, description:'Dark fantasy RPG with branching storylines and 100+ hours of gameplay.' },
      { id:6, title:'Iron Fortress',       genre:'Strategy', rating:4.1, release_year:2023, is_trending:true,  description:'Build, defend and expand your empire in this award-winning strategy game.' },
    ];
    allGames = demos;
    render(demos);
  }

  return { init: loadGames, loadTrending, loadNewReleases, filterByGenre, render, renderFallback, createCard };
})();

// ── Search Module ─────────────────────────────────────────────
const SearchModule = (() => {
  let debounceTimer;

  async function search(query, genre = '', sort = 'rating') {
    const el = container();
    if (el) el.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:2rem"><div class="loader-ring"></div></div>`;

    try {
      const params = new URLSearchParams({ action:'search', q:query, genre, sort });
      const data = await apiGet(`php/recommendation.php?${params}`);
      if (data.success) {
        const grid = container();
        if (grid) {
          if (!data.games.length) {
            grid.innerHTML = `
              <div class="no-results" style="grid-column:1/-1">
                <span class="icon">🔍</span>
                <h3>No results for "${query}"</h3>
                <p>Try a different keyword or genre.</p>
              </div>`;
          } else {
            grid.innerHTML = data.games.map(g => RecommendationEngine.createCard(g)).join('');
            grid.querySelectorAll('.add-wishlist').forEach(btn => {
              btn.addEventListener('click', e => {
                e.stopPropagation();
                WishlistModule.toggle(btn.dataset.id, btn.dataset.title);
              });
            });
          }
        }
      }
    } catch {
      showToast('Search failed. Please try again.', 'error');
    }
  }

  function debounceSearch(query, genre, sort) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => search(query, genre, sort), 400);
  }

  function init() {
    const searchInput = document.getElementById('game-search');
    const genreSelect = document.getElementById('genre-filter');
    const sortSelect  = document.getElementById('sort-filter');
    const searchBtn   = document.getElementById('search-btn');

    const doSearch = () => {
      const q     = searchInput?.value.trim() || '';
      const genre = genreSelect?.value || '';
      const sort  = sortSelect?.value || 'rating';
      if (q || genre) debounceSearch(q, genre, sort);
      else RecommendationEngine.init();
    };

    if (searchInput) searchInput.addEventListener('input', doSearch);
    if (genreSelect) genreSelect.addEventListener('change', doSearch);
    if (sortSelect)  sortSelect.addEventListener('change', doSearch);
    if (searchBtn)   searchBtn.addEventListener('click', doSearch);
  }

  return { init, search };
})();

// Helper
function container() { return document.getElementById('recommendations-grid'); }

// ── Game Detail Modal ─────────────────────────────────────────
async function openGameDetail(gameId) {
  openModal('game-detail-modal');
  const body = document.getElementById('game-detail-body');
  if (!body) return;
  body.innerHTML = `<div style="text-align:center;padding:3rem"><div class="loader-ring"></div></div>`;

  try {
    const data = await apiGet(`php/recommendation.php?action=game_detail&id=${gameId}`);
    if (!data.success || !data.game) throw new Error('Not found');
    const g = data.game;
    const emoji = getGenreEmoji(g.genre);
    const rating = parseFloat(g.rating || 0);
    const stars  = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));

    body.innerHTML = `
      <div class="game-detail-hero" style="font-size:6rem;text-align:center;padding:2rem;background:linear-gradient(135deg,#0a1628 0%,#1a0a2e 100%);border-radius:12px;margin-bottom:1.5rem">${emoji}</div>
      <div class="game-card-genre mb-1">${g.genre || 'Game'}</div>
      <h2 style="font-family:var(--font-display);font-size:1.6rem;color:var(--text-prime);margin:.5rem 0 1rem">${g.title}</h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1.5rem">${g.description || 'No description available.'}</p>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:1.5rem">
        <div class="glass-card" style="padding:1rem;text-align:center">
          <div style="font-size:.65rem;color:var(--text-muted);letter-spacing:1px;margin-bottom:.4rem">RATING</div>
          <div style="font-family:var(--font-display);font-size:1.3rem;color:var(--neon-gold)">${stars} ${rating.toFixed(1)}</div>
        </div>
        <div class="glass-card" style="padding:1rem;text-align:center">
          <div style="font-size:.65rem;color:var(--text-muted);letter-spacing:1px;margin-bottom:.4rem">RELEASED</div>
          <div style="font-family:var(--font-display);font-size:.95rem;color:var(--neon-cyan)">${g.release_date || 'TBA'}</div>
        </div>
        <div class="glass-card" style="padding:1rem;text-align:center">
          <div style="font-size:.65rem;color:var(--text-muted);letter-spacing:1px;margin-bottom:.4rem">VIEWS</div>
          <div style="font-family:var(--font-display);font-size:1.1rem;color:var(--neon-green)">${(g.views||0).toLocaleString()}</div>
        </div>
      </div>

      <div class="glass-card" style="padding:1.25rem;margin-bottom:1.5rem">
        <h4 style="font-family:var(--font-display);font-size:.75rem;color:var(--neon-cyan);letter-spacing:2px;margin-bottom:1rem">⚙️ MINIMUM REQUIREMENTS</h4>
        <table class="specs-table" style="width:100%;border-collapse:collapse">
          <tr><td style="color:var(--text-muted);padding:.35rem 0;font-size:.85rem">RAM</td><td style="color:var(--text-prime);font-size:.85rem">${g.min_ram || '8'} GB</td></tr>
          <tr><td style="color:var(--text-muted);padding:.35rem 0;font-size:.85rem">GPU</td><td style="color:var(--text-prime);font-size:.85rem">${g.min_gpu || 'GTX 1060'}</td></tr>
          <tr><td style="color:var(--text-muted);padding:.35rem 0;font-size:.85rem">CPU</td><td style="color:var(--text-prime);font-size:.85rem">${g.min_cpu || 'Intel i5'}</td></tr>
          <tr><td style="color:var(--text-muted);padding:.35rem 0;font-size:.85rem">Storage</td><td style="color:var(--text-prime);font-size:.85rem">${g.min_storage || '50'} GB</td></tr>
        </table>
      </div>

      ${data.reviews && data.reviews.length ? `
        <div class="glass-card" style="padding:1.25rem;margin-bottom:1.5rem">
          <h4 style="font-family:var(--font-display);font-size:.75rem;color:var(--neon-cyan);letter-spacing:2px;margin-bottom:1rem">💬 RECENT REVIEWS</h4>
          ${data.reviews.slice(0,3).map(r => `
            <div style="border-bottom:1px solid rgba(255,255,255,.05);padding:.75rem 0">
              <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
                <span style="color:var(--neon-gold);font-size:.85rem">👤 ${escapeHtml(r.username)}</span>
                <span style="color:var(--neon-gold);font-size:.8rem">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span>
              </div>
              <p style="color:var(--text-muted);font-size:.82rem;line-height:1.6">${escapeHtml(r.comment)}</p>
            </div>`).join('')}
        </div>` : ''}

      <div style="display:flex;gap:.75rem">
        <button class="btn btn-primary w-full" onclick="WishlistModule.toggle(${g.id}, '${escapeHtml(g.title)}')">♥ Add to Wishlist</button>
        <button class="btn btn-outline" onclick="closeModal('game-detail-modal')">Close</button>
      </div>

      <div style="margin-top:1.5rem">
        <h4 style="font-family:var(--font-display);font-size:.75rem;color:var(--neon-cyan);letter-spacing:2px;margin-bottom:1rem">📝 WRITE A REVIEW</h4>
        <div id="review-form-${g.id}">
          <div class="star-rating" id="review-stars-${g.id}" data-rating="0" style="font-size:1.5rem;cursor:pointer;margin-bottom:.75rem">
            ${[1,2,3,4,5].map(n => `<span class="star" data-val="${n}" style="color:var(--text-muted)">★</span>`).join('')}
          </div>
          <textarea id="review-comment-${g.id}" class="form-input" rows="3" placeholder="Share your experience..." style="width:100%;resize:vertical;margin-bottom:.75rem"></textarea>
          <button class="btn btn-primary btn-sm" onclick="submitReview(${g.id})">Submit Review</button>
        </div>
      </div>`;

    // Init star rating for review
    const starContainer = document.getElementById(`review-stars-${g.id}`);
    if (starContainer) {
      const stars2 = starContainer.querySelectorAll('.star');
      let selected = 0;
      stars2.forEach((star, i) => {
        star.addEventListener('mouseover', () => stars2.forEach((s, j) => s.style.color = j <= i ? 'var(--neon-gold)' : 'var(--text-muted)'));
        star.addEventListener('mouseleave', () => stars2.forEach((s, j) => s.style.color = j < selected ? 'var(--neon-gold)' : 'var(--text-muted)'));
        star.addEventListener('click', () => {
          selected = i + 1;
          starContainer.dataset.rating = selected;
          stars2.forEach((s, j) => s.style.color = j < selected ? 'var(--neon-gold)' : 'var(--text-muted)');
        });
      });
    }
  } catch {
    body.innerHTML = `<p style="color:var(--neon-pink);text-align:center;padding:2rem">❌ Failed to load game details.</p>`;
  }
}

// ── Submit Review ─────────────────────────────────────────────
async function submitReview(gameId) {
  const rating  = parseInt(document.getElementById(`review-stars-${gameId}`)?.dataset.rating || 0);
  const comment = document.getElementById(`review-comment-${gameId}`)?.value.trim();

  if (!rating) { showToast('Please select a star rating', 'warning'); return; }
  if (!comment || comment.length < 10) { showToast('Review must be at least 10 characters', 'warning'); return; }

  const data = await apiPost('php/review_process.php', { action:'submit', game_id:gameId, rating, comment });
  if (data.success) {
    showToast(data.message, 'success');
    document.getElementById(`review-form-${gameId}`).innerHTML = `
      <p style="color:var(--neon-green);text-align:center;padding:1rem">✓ Review submitted successfully!</p>`;
  } else if (data.auth === false) {
    showToast('Please login to submit reviews', 'warning');
    setTimeout(() => window.location.href = 'login.html', 1500);
  } else {
    showToast(data.message || 'Failed to submit review', 'error');
  }
}

// ── XSS escape ───────────────────────────────────────────────
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init on DOM ready ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('recommendations-grid')) {
    RecommendationEngine.init();
    SearchModule.init();
  }
  if (document.getElementById('trending-grid')) {
    RecommendationEngine.loadTrending();
  }
  if (document.getElementById('new-releases-grid')) {
    RecommendationEngine.loadNewReleases();
  }

  // Genre filter buttons
  document.querySelectorAll('.genre-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => RecommendationEngine.filterByGenre(btn.dataset.genre));
  });
});