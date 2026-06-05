/* ============================================================
   GameHub — compatibility.js  |  PC Compatibility Checker
   ============================================================ */

const CompatibilityChecker = (() => {

  /* GPU tier scoring (higher = better) */
  const GPU_TIERS = {
    // RTX 4000 series
    'rtx 4090': 100, 'rtx 4080': 95, 'rtx 4070 ti': 90, 'rtx 4070': 85,
    // RTX 3000 series
    'rtx 3090': 88, 'rtx 3080 ti': 86, 'rtx 3080': 83, 'rtx 3070 ti': 79,
    'rtx 3070': 76, 'rtx 3060 ti': 72, 'rtx 3060': 68,
    // RTX 2000 series
    'rtx 2080 ti': 80, 'rtx 2080': 74, 'rtx 2070 super': 70, 'rtx 2070': 67,
    'rtx 2060 super': 63, 'rtx 2060': 60,
    // GTX 16/10 series
    'gtx 1660 ti': 56, 'gtx 1660 super': 54, 'gtx 1660': 52,
    'gtx 1080 ti': 65, 'gtx 1080': 61, 'gtx 1070 ti': 57, 'gtx 1070': 54,
    'gtx 1060 6gb': 49, 'gtx 1060': 47, 'gtx 1050 ti': 38, 'gtx 1050': 33,
    'gtx 960': 30, 'gtx 950': 27,
    // AMD RX 7000
    'rx 7900 xtx': 98, 'rx 7900 xt': 92, 'rx 7800 xt': 82, 'rx 7700 xt': 75,
    // AMD RX 6000
    'rx 6900 xt': 87, 'rx 6800 xt': 84, 'rx 6800': 78, 'rx 6700 xt': 73,
    'rx 6600 xt': 64, 'rx 6600': 59,
    // AMD RX 5000/500
    'rx 5700 xt': 66, 'rx 5700': 62, 'rx 5600 xt': 57,
    'rx 580': 45, 'rx 570': 40, 'rx 480': 42,
    // Intel Arc
    'arc a770': 65, 'arc a750': 60, 'arc a380': 38,
  };

  /* CPU tier scoring */
  const CPU_TIERS = {
    // Intel 13th gen
    'i9-13900k': 100, 'i9-13900': 95, 'i7-13700k': 90, 'i7-13700': 85,
    'i5-13600k': 82, 'i5-13400': 72,
    // Intel 12th gen
    'i9-12900k': 88, 'i7-12700k': 84, 'i7-12700': 80,
    'i5-12600k': 78, 'i5-12400f': 70, 'i5-12400': 68, 'i3-12100f': 58,
    // Intel 11th gen
    'i9-11900k': 82, 'i7-11700k': 76, 'i5-11600k': 72,
    // Intel 10th gen
    'i9-10900k': 78, 'i7-10700k': 74, 'i7-10700': 70,
    'i5-10600k': 68, 'i5-10400': 62,
    // Intel 9th gen
    'i9-9900k': 74, 'i7-9700k': 68, 'i5-9600k': 62,
    // Intel 8th gen
    'i7-8700k': 64, 'i7-8700': 60, 'i5-8600k': 58, 'i5-8400': 54,
    // Intel 6/7th gen
    'i7-7700k': 58, 'i5-7600k': 52, 'i7-6700k': 54, 'i5-6600': 48,
    // AMD Ryzen 7000
    'ryzen 9 7950x': 100, 'ryzen 9 7900x': 94, 'ryzen 7 7700x': 88,
    'ryzen 5 7600x': 82, 'ryzen 5 7600': 78,
    // AMD Ryzen 5000
    'ryzen 9 5950x': 92, 'ryzen 9 5900x': 88, 'ryzen 7 5800x': 82,
    'ryzen 5 5600x': 76, 'ryzen 5 5600': 72,
    // AMD Ryzen 3000
    'ryzen 9 3900x': 78, 'ryzen 7 3700x': 72, 'ryzen 5 3600': 65,
    // AMD Ryzen 1000/2000
    'ryzen 5 2600': 55, 'ryzen 5 1600': 48, 'ryzen 3 3300x': 52,
  };

  function scoreGPU(gpuStr) {
    const lower = gpuStr.toLowerCase().replace(/nvidia|amd|radeon|geforce/gi, '').trim();
    for (const [key, score] of Object.entries(GPU_TIERS)) {
      if (lower.includes(key)) return score;
    }
    return 40; // Unknown = assume mid-range
  }

  function scoreCPU(cpuStr) {
    const lower = cpuStr.toLowerCase().replace(/intel|core|amd|ryzen/gi, '').trim();
    for (const [key, score] of Object.entries(CPU_TIERS)) {
      if (lower.includes(key)) return score;
    }
    return 45;
  }

  function checkGame(game, specs) {
    const ramOk      = specs.ram >= (game.min_ram || 8);
    const storageOk  = specs.storage >= (game.min_storage || 30);
    const gpuScore   = scoreGPU(specs.gpu);
    const cpuScore   = scoreCPU(specs.cpu);
    const reqGpuScore = scoreGPU(game.min_gpu || 'GTX 1060');
    const reqCpuScore = scoreCPU(game.min_cpu || 'Intel i5-8400');

    const gpuRatio  = gpuScore / Math.max(reqGpuScore, 1);
    const cpuRatio  = cpuScore / Math.max(reqCpuScore, 1);

    let tier, label, color, emoji, perf;
    if (!ramOk || !storageOk) {
      tier = 'incompatible'; label = 'Not Compatible'; color = '#ff006a'; emoji = '❌';
      perf = storageOk ? `Need ${game.min_ram}GB RAM (you have ${specs.ram}GB)` : `Need ${game.min_storage}GB storage`;
    } else if (gpuRatio >= 1.2 && cpuRatio >= 1.2) {
      tier = 'ultra'; label = 'Ultra / Max Settings'; color = '#00ff88'; emoji = '🚀';
      perf = 'Smooth 60+ FPS at high/ultra settings';
    } else if (gpuRatio >= 0.9 && cpuRatio >= 0.9) {
      tier = 'high'; label = 'High Settings'; color = '#00f5ff'; emoji = '✅';
      perf = 'Smooth 60 FPS at high settings';
    } else if (gpuRatio >= 0.7 && cpuRatio >= 0.7) {
      tier = 'medium'; label = 'Medium Settings'; color = '#ffd700'; emoji = '⚡';
      perf = 'Playable 30–60 FPS at medium settings';
    } else if (gpuRatio >= 0.5 && cpuRatio >= 0.5) {
      tier = 'low'; label = 'Low Settings'; color = '#ff8800'; emoji = '⚠️';
      perf = 'Playable at low settings with reduced FPS';
    } else {
      tier = 'incompatible'; label = 'Not Recommended'; color = '#ff006a'; emoji = '❌';
      perf = 'GPU/CPU below minimum requirements';
    }

    return { tier, label, color, emoji, perf, gpuRatio, cpuRatio, ramOk, storageOk };
  }

  function renderResult(game, result, iconMap) {
    const icon = iconMap[game.genre] || '🎮';
    const gpuPct = Math.min(100, Math.round(result.gpuRatio * 100));
    const cpuPct = Math.min(100, Math.round(result.cpuRatio * 100));
    return `
      <div class="compat-card" style="border-color:${result.color}22">
        <div class="compat-card-header" style="border-bottom:1px solid ${result.color}22;padding:.85rem 1rem;display:flex;align-items:center;gap:.75rem">
          <span style="font-size:1.5rem">${icon}</span>
          <div style="flex:1">
            <div style="font-family:var(--font-display);font-size:.8rem;color:var(--text-prime);letter-spacing:1px">${game.title}</div>
            <div style="font-size:.72rem;color:var(--text-dim)">${game.genre}</div>
          </div>
          <div style="text-align:right">
            <div style="font-size:1.2rem">${result.emoji}</div>
            <div style="font-size:.65rem;color:${result.color};font-weight:700;text-transform:uppercase;letter-spacing:1px">${result.label}</div>
          </div>
        </div>
        <div style="padding:.85rem 1rem">
          <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.75rem">${result.perf}</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:.25rem">
                <span style="font-size:.65rem;color:var(--text-dim)">GPU</span>
                <span style="font-size:.65rem;color:${result.color}">${gpuPct}%</span>
              </div>
              <div style="height:4px;background:var(--border-dim);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:${gpuPct}%;background:${result.color};border-radius:2px;transition:width 1s ease"></div>
              </div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:.25rem">
                <span style="font-size:.65rem;color:var(--text-dim)">CPU</span>
                <span style="font-size:.65rem;color:${result.color}">${cpuPct}%</span>
              </div>
              <div style="height:4px;background:var(--border-dim);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:${cpuPct}%;background:${result.color};border-radius:2px;transition:width 1s ease"></div>
              </div>
            </div>
          </div>
          <div style="margin-top:.6rem;display:flex;gap:.5rem;flex-wrap:wrap">
            <span class="req-chip">🖥️ ${game.min_ram}GB RAM</span>
            <span class="req-chip">${game.min_gpu||'N/A'}</span>
            <span class="req-chip">💾 ${game.min_storage}GB</span>
          </div>
        </div>
      </div>`;
  }

  async function check(specs, gameId) {
    const resultsEl = document.getElementById('compat-results');
    const defaultEl = document.getElementById('compat-default');
    if (defaultEl) defaultEl.classList.add('hidden');
    if (resultsEl) { resultsEl.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted)">⏳ Analysing your specs…</div>'; resultsEl.classList.remove('hidden'); }

    const GENRES = { Action:'⚔️', RPG:'🧙', FPS:'🔫', Strategy:'♟️', Racing:'🏎️', Horror:'👻',
      Adventure:'🗺️', Simulation:'🏗️', Fighting:'🥊', Puzzle:'🧩', 'Battle Royale':'🎯',
      MOBA:'🏆', Stealth:'🕵️', Survival:'🌲' };

    let games = [];
    try {
      const url = gameId ? `php/recommendation.php?action=all&game_id=${gameId}` : 'php/recommendation.php?action=all&limit=20';
      const res  = await fetch(url);
      const data = await res.json();
      games = data.games || data.data || data || [];
      if (gameId) games = games.filter(g => g.id == gameId);
    } catch(e) {
      // Demo fallback
      games = [
        {id:1,title:'Cyber Odyssey 2049',genre:'RPG',min_ram:16,min_gpu:'RTX 3060',min_cpu:'Intel i7-10700',min_storage:80},
        {id:2,title:'Phantom Strike',genre:'FPS',min_ram:8,min_gpu:'GTX 1660 Super',min_cpu:'Intel i5-9600K',min_storage:60},
        {id:4,title:'Neon Racer X',genre:'Racing',min_ram:8,min_gpu:'GTX 1070',min_cpu:'Intel i5-8600',min_storage:30},
        {id:5,title:'Cursed Hollow',genre:'Horror',min_ram:12,min_gpu:'GTX 1070',min_cpu:'Intel i5-9600',min_storage:25},
        {id:6,title:'Puzzle Dimension',genre:'Puzzle',min_ram:4,min_gpu:'GTX 960',min_cpu:'Intel i3-8100',min_storage:5},
        {id:7,title:'Storm Legends',genre:'Battle Royale',min_ram:8,min_gpu:'GTX 1660',min_cpu:'Intel i5-9400',min_storage:30},
      ];
      if (gameId) games = games.filter(g => g.id == gameId);
    }

    const results  = games.map(g => ({ game:g, result: checkGame(g, specs) }));
    const compat   = results.filter(r => r.result.tier !== 'incompatible').length;
    const total    = results.length;

    if (!resultsEl) return;
    resultsEl.innerHTML = `
      <div class="glass-card" style="padding:1.25rem 1.5rem;margin-bottom:1rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
        <div style="font-family:var(--font-display);font-size:1rem;color:var(--neon-cyan)">${compat}/${total}</div>
        <div style="color:var(--text-muted);font-size:.85rem">games compatible with your setup</div>
        <div style="margin-left:auto;display:flex;gap:.75rem">
          <span style="font-size:.78rem;color:var(--neon-green)">🚀 Ultra: ${results.filter(r=>r.result.tier==='ultra').length}</span>
          <span style="font-size:.78rem;color:var(--neon-cyan)">✅ High: ${results.filter(r=>r.result.tier==='high').length}</span>
          <span style="font-size:.78rem;color:var(--neon-gold)">⚡ Medium: ${results.filter(r=>r.result.tier==='medium').length}</span>
          <span style="font-size:.78rem;color:var(--neon-pink)">❌ N/A: ${results.filter(r=>r.result.tier==='incompatible').length}</span>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
        ${results.map(r => renderResult(r.game, r.result, GENRES)).join('')}
      </div>`;
  }

  function init() {
    const form = document.getElementById('compat-form');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const specs = {
        ram:     parseInt(document.getElementById('ram')?.value) || 8,
        gpu:     document.getElementById('gpu')?.value?.trim()   || '',
        cpu:     document.getElementById('cpu')?.value?.trim()   || '',
        storage: parseInt(document.getElementById('storage')?.value) || 500,
      };
      const gameId = document.getElementById('compat-game')?.value || '';
      if (!specs.gpu || !specs.cpu) { alert('Please enter your GPU and CPU.'); return; }
      await check(specs, gameId);
    });
  }

  return { init, check, scoreGPU, scoreCPU };
})();

document.addEventListener('DOMContentLoaded', () => CompatibilityChecker.init());

/* Styles */
const s = document.createElement('style');
s.textContent = `
  .compat-card { background:var(--bg-card); border:1px solid var(--border-dim); border-radius:10px; overflow:hidden; transition:var(--transition); }
  .compat-card:hover { transform:translateY(-2px); }
  .req-chip { padding:.15rem .5rem; background:var(--bg-glass); border:1px solid var(--border-dim); border-radius:6px; font-size:.65rem; color:var(--text-dim); font-family:var(--font-mono); }
  .hidden { display:none !important; }
`;
document.head.appendChild(s);