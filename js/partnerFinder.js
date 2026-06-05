/* ============================================================
   GameHub — partnerFinder.js
   Full partner request system: find, send, accept, reject
   ============================================================ */

const PartnerFinder = (() => {

  const DEMO_PARTNERS = [
    { id:1, username:'NeonSword99',   favorite_game:'Cyber Odyssey 2049', playing_time:'evenings',   play_style:'Competitive', online:'🟢', match:96, member_since:'2023' },
    { id:2, username:'PixelHunter',   favorite_game:'Phantom Strike',     playing_time:'weekends',   play_style:'Casual',      online:'🟢', match:88, member_since:'2022' },
    { id:3, username:'GalacticRider', favorite_game:'Neon Racer X',       playing_time:'mornings',   play_style:'Casual',      online:'🟡', match:82, member_since:'2024' },
    { id:4, username:'CryptoMage',    favorite_game:'Galaxy Commanders',  playing_time:'evenings',   play_style:'Competitive', online:'🟢', match:79, member_since:'2023' },
    { id:5, username:'VoidWalker',    favorite_game:'Cyber Odyssey 2049', playing_time:'late night', play_style:'Competitive', online:'🔴', match:75, member_since:'2022' },
    { id:6, username:'BlitzKing',     favorite_game:'Phantom Strike',     playing_time:'evenings',   play_style:'Competitive', online:'🟢', match:70, member_since:'2024' },
  ];

  async function findPartners(prefs) {
    const results = document.getElementById('partner-results');
    if (!results) return;
    results.classList.remove('hidden');
    results.innerHTML = `<div style="text-align:center;padding:3rem"><div class="loader-ring"></div><p style="color:var(--text-muted);margin-top:1rem;font-family:var(--font-display);font-size:.85rem;letter-spacing:1px">SCANNING PLAYER DATABASE...</p></div>`;

    let partners = [];
    try {
      const data = await apiPost('php/partner_match.php', { ...prefs, action:'find' });
      if (data.success && data.partners?.length) { partners = data.partners; }
      else { throw new Error('No results'); }
    } catch {
      partners = DEMO_PARTNERS.map(p => ({ ...p, match: calcDemoMatch(p, prefs) })).sort((a,b) => b.match - a.match);
    }
    renderPartners(partners, results);
  }

  function calcDemoMatch(user, prefs) {
    let score = 50;
    if (user.play_style.toLowerCase() === prefs.play_style) score += 30;
    if (user.playing_time === prefs.playing_time)           score += 20;
    if (prefs.favorite_game && user.favorite_game.toLowerCase().includes(prefs.favorite_game.toLowerCase())) score += 15;
    score += Math.floor(Math.random() * 10);
    return Math.min(99, score);
  }

  function renderPartners(partners, container) {
    if (!partners.length) {
      container.innerHTML = `<div class="no-results"><span class="icon">🕹️</span><h3>No Matches Found</h3><p>Try adjusting your preferences.</p></div>`;
      return;
    }
    container.innerHTML = `
      <div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center">
        <div style="font-family:var(--font-display);font-size:.8rem;color:var(--neon-cyan);letter-spacing:2px">✓ ${partners.length} COMPATIBLE GAMERS FOUND</div>
        <div style="font-size:.78rem;color:var(--text-muted)">Sorted by match score</div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem">
        ${partners.map(p => renderPartnerCard(p)).join('')}
      </div>`;
  }

  function renderPartnerCard(p) {
    const matchColor = p.match >= 90 ? 'var(--neon-green)' : p.match >= 75 ? 'var(--neon-gold)' : 'var(--neon-cyan)';
    const avatarEmojis = ['🎮','⚔️','🔫','🧙','♟️','🏎️','🎯','🧩'];
    const avatar = avatarEmojis[p.id % avatarEmojis.length] || '🎮';

    return `
      <div class="glass-card partner-card" style="padding:1.5rem;text-align:center;position:relative;overflow:hidden;transition:transform .3s,box-shadow .3s"
           onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,229,255,.15)'"
           onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="position:absolute;top:0;right:0;background:${matchColor};color:#000;font-family:var(--font-display);font-size:.7rem;font-weight:700;padding:.3rem .6rem;border-radius:0 0 0 8px;letter-spacing:1px">${p.match}% MATCH</div>
        <div style="font-size:3rem;margin-bottom:.75rem;filter:drop-shadow(0 0 10px ${matchColor})">${avatar}</div>
        <div style="font-family:var(--font-display);font-size:1rem;color:var(--text-prime);margin-bottom:.2rem">${escapeHtml(p.username)}</div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:1rem">${p.online || '🟢'} Online · Since ${p.member_since || '2024'}</div>
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:.75rem;margin-bottom:1rem;text-align:left">
          <div style="font-size:.75rem;line-height:2.1;color:var(--text-muted)">
            🎮 <span style="color:var(--text-prime)">${escapeHtml(p.favorite_game || 'Various')}</span><br>
            ⏰ <span style="color:var(--text-prime)">${escapeHtml(p.playing_time || 'Flexible')}</span><br>
            🎯 <span style="color:var(--text-prime)">${escapeHtml(p.play_style || 'Casual')}</span>
          </div>
        </div>
        <div style="background:rgba(0,0,0,.2);border-radius:4px;height:6px;margin-bottom:1rem;overflow:hidden">
          <div style="height:100%;width:${p.match}%;background:linear-gradient(90deg,${matchColor},${matchColor}88);border-radius:4px;transition:width 1s ease"></div>
        </div>
        <div style="display:flex;gap:.5rem">
          <button class="btn btn-primary btn-sm w-full" id="req-btn-${p.id}"
            onclick="sendPartnerRequest(${p.id},'${escapeHtml(p.username)}',this)">
            🎮 Send Request
          </button>
          <button class="btn btn-outline btn-sm" onclick="viewGamerProfile(${p.id})" title="View Profile">👁</button>
        </div>
      </div>`;
  }

  async function saveProfile(prefs) {
    try {
      const data = await apiPost('php/partner_match.php', { ...prefs, action:'save_profile' });
      if (data.success)          showToast(data.message || 'Profile saved!', 'success');
      else if (data.auth===false) showToast('Login to save your profile', 'warning');
      else                        showToast(data.message || 'Failed', 'error');
    } catch { showToast('Server error', 'error'); }
  }

  async function loadProfile() {
    try {
      const data = await apiGet('php/partner_match.php?action=get_profile');
      if (data.success && data.profile) {
        const p = data.profile;
        if (document.getElementById('pf-game')  && p.favorite_game) document.getElementById('pf-game').value  = p.favorite_game;
        if (document.getElementById('pf-time')  && p.playing_time)  document.getElementById('pf-time').value  = p.playing_time;
        if (document.getElementById('pf-style') && p.play_style)    document.getElementById('pf-style').value = p.play_style;
      }
    } catch {}
  }

  function handleSubmit(e) {
    e.preventDefault();
    const prefs = {
      favorite_game: document.getElementById('pf-game')?.value?.trim()  || '',
      playing_time:  document.getElementById('pf-time')?.value          || 'evenings',
      play_style:    document.getElementById('pf-style')?.value         || 'casual',
    };
    if (!prefs.favorite_game) { showToast('Please enter your favourite game', 'warning'); return; }
    findPartners(prefs);
    saveProfile(prefs);
  }

  function init() {
    const form = document.getElementById('partner-form');
    if (form) { form.addEventListener('submit', handleSubmit); loadProfile(); }
    loadPendingBadge();
  }

  return { init, findPartners, saveProfile };
})();

/* ── Global partner request functions ─────────────────────── */

async function sendPartnerRequest(receiverId, username, btn) {
  btn.disabled = true;
  btn.textContent = '⏳ Sending...';
  try {
    const data = await apiPost('php/partner_requests.php', {
      action: 'send', receiver_id: receiverId,
      message: `Hey ${username}! Want to game together? 🎮`
    });
    if (data.success) {
      btn.textContent = '✓ Request Sent!';
      btn.style.cssText += ';background:rgba(57,255,20,.15);color:#39ff14;border:1px solid rgba(57,255,20,.4)';
      showToast(data.message, 'success');
    } else if (data.auth === false) {
      btn.textContent = '🎮 Send Request'; btn.disabled = false;
      showToast('Please log in first.', 'error');
      setTimeout(() => window.location.href = 'login.html', 1500);
    } else {
      btn.textContent = '🎮 Send Request'; btn.disabled = false;
      showToast(data.message || 'Failed', 'error');
    }
  } catch { btn.textContent = '🎮 Send Request'; btn.disabled = false; showToast('Server error', 'error'); }
}

async function acceptRequest(requestId, senderName, btn) {
  btn.disabled = true; btn.textContent = '⏳...';
  const data = await apiPost('php/partner_requests.php', { action:'accept', request_id:requestId }).catch(()=>({success:false}));
  if (data.success) {
    showToast(data.message, 'success');
    const card = document.getElementById('req-card-' + requestId);
    if (card) { card.style.cssText += ';opacity:0;transform:scale(.95);transition:all .3s'; setTimeout(()=>card.remove(),300); }
    loadPendingBadge();
  } else { showToast(data.message||'Failed','error'); btn.disabled=false; btn.textContent='✅ Accept'; }
}

async function rejectRequest(requestId, senderName, btn) {
  btn.disabled = true; btn.textContent = '⏳...';
  const data = await apiPost('php/partner_requests.php', { action:'reject', request_id:requestId }).catch(()=>({success:false}));
  if (data.success) {
    showToast('Request rejected.', 'info');
    const card = document.getElementById('req-card-' + requestId);
    if (card) { card.style.cssText += ';opacity:0;transform:scale(.95);transition:all .3s'; setTimeout(()=>card.remove(),300); }
    loadPendingBadge();
  } else { showToast(data.message||'Failed','error'); btn.disabled=false; btn.textContent='✕ Reject'; }
}

async function loadPendingBadge() {
  try {
    const data = await apiGet('php/partner_requests.php?action=pending_count');
    document.querySelectorAll('.partner-badge').forEach(b => {
      b.textContent  = data.count > 0 ? data.count : '';
      b.style.display = data.count > 0 ? '' : 'none';
    });
  } catch {}
}

async function loadPartnerRequests(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = `<div style="text-align:center;padding:2rem"><div class="loader-ring"></div></div>`;

  try {
    const data = await apiGet('php/partner_requests.php?action=list_received');

    if (!data.success || !data.requests?.length) {
      container.innerHTML = `
        <div style="text-align:center;padding:2rem;color:#8ba3c0">
          <div style="font-size:2.5rem;margin-bottom:.75rem">🎮</div>
          <p>No partner requests yet.</p>
          <a href="partner_finder.html" style="color:#00f5ff;font-size:.85rem">Find gaming partners →</a>
        </div>`;
      return;
    }

    const pending = data.requests.filter(r => r.status === 'pending');
    const others  = data.requests.filter(r => r.status !== 'pending');

    container.innerHTML = [...pending, ...others].map(r => {
      const isPending  = r.status === 'pending';
      const isAccepted = r.status === 'accepted';
      const statusColor = isAccepted ? '#39ff14' : isPending ? '#ffd700' : '#ff0090';
      const statusLabel = isAccepted ? '✅ Partners!' : isPending ? '⏳ Pending' : '✕ Rejected';
      const avatarList  = ['🎮','⚔️','🔫','🧙','♟️','🏎️','🎯','🧩'];
      const avatar = avatarList[r.sender_id % avatarList.length];

      return `
        <div id="req-card-${r.id}" style="background:rgba(10,14,24,.9);border:1px solid rgba(0,245,255,.1);border-radius:12px;padding:1.1rem 1.25rem;margin-bottom:.85rem;transition:all .3s;">
          <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,rgba(0,245,255,.15),rgba(191,0,255,.15));display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;border:1px solid rgba(0,245,255,.2)">${avatar}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;color:#e8f4ff;font-size:.95rem">${escapeHtml(r.sender_name)}</div>
              <div style="font-size:.75rem;color:#8ba3c0;margin-top:.1rem">🎮 ${escapeHtml(r.sender_game||'Various')} · 🎯 ${escapeHtml(r.sender_style||'Casual')}</div>
              <div style="font-size:.72rem;color:#3a4f6a;margin-top:.15rem">"${escapeHtml(r.message||'')}" · ${getTimeAgo(r.created_at)}</div>
            </div>
            <div style="flex-shrink:0;text-align:right;">
              ${isPending ? `
                <div style="display:flex;gap:.4rem;">
                  <button onclick="acceptRequest(${r.id},'${escapeHtml(r.sender_name)}',this)"
                    style="padding:.35rem .85rem;border-radius:7px;border:1px solid rgba(57,255,20,.4);background:rgba(57,255,20,.1);color:#39ff14;cursor:pointer;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.82rem">✅ Accept</button>
                  <button onclick="rejectRequest(${r.id},'${escapeHtml(r.sender_name)}',this)"
                    style="padding:.35rem .85rem;border-radius:7px;border:1px solid rgba(255,0,144,.3);background:rgba(255,0,144,.08);color:#ff0090;cursor:pointer;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.82rem">✕ Reject</button>
                </div>` : `
                <span style="font-size:.78rem;font-weight:700;color:${statusColor};padding:.25rem .7rem;border-radius:20px;background:${statusColor}22;border:1px solid ${statusColor}44">${statusLabel}</span>`}
            </div>
          </div>
        </div>`;
    }).join('');

  } catch {
    container.innerHTML = `<div style="text-align:center;padding:2rem;color:#8ba3c0">Could not load requests.</div>`;
  }
}

function getTimeAgo(dateStr) {
  if (!dateStr) return '';
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff/60000), hours = Math.floor(diff/3600000), days = Math.floor(diff/86400000);
  if (mins  < 1)  return 'just now';
  if (mins  < 60) return `${mins}m ago`;
  if (hours < 24) return `${hours}h ago`;
  return `${days}d ago`;
}

function viewGamerProfile(id) { showToast('Profile view coming soon! 👁', 'info'); }

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

document.addEventListener('DOMContentLoaded', PartnerFinder.init);