<?php
/* ============================================================
   GameHub — admin/analytics.php  |  Platform Analytics
   ============================================================ */

require_once __DIR__ . '/../php/db.php';

startSession();
if (!isAdmin()) { header('Location: ../login.html'); exit; }

$db = getDB();

/* ── Aggregate data ── */
$totals = $db->query("
  SELECT
    (SELECT COUNT(*) FROM users WHERE role='user')  AS total_users,
    (SELECT COUNT(*) FROM games)                     AS total_games,
    (SELECT COUNT(*) FROM reviews)                   AS total_reviews,
    (SELECT COUNT(*) FROM wishlist)                  AS total_wishlist,
    (SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS active_30d
")->fetch();

$genreStats = $db->query("
  SELECT g.genre, COUNT(DISTINCT r.id) AS review_count, COUNT(DISTINCT w.id) AS wishlist_count, ROUND(AVG(g.rating),1) AS avg_rating
  FROM games g
  LEFT JOIN reviews  r ON r.game_id = g.id
  LEFT JOIN wishlist w ON w.game_id = g.id
  GROUP BY g.genre ORDER BY wishlist_count DESC LIMIT 10
")->fetchAll();

$topGames = $db->query("
  SELECT g.title, g.genre, g.rating, g.views,
         COUNT(DISTINCT r.id) AS review_count,
         COUNT(DISTINCT w.id) AS wishlist_count
  FROM games g
  LEFT JOIN reviews  r ON r.game_id = g.id
  LEFT JOIN wishlist w ON w.game_id = g.id
  GROUP BY g.id ORDER BY wishlist_count DESC LIMIT 8
")->fetchAll();

$ratingDist = $db->query("
  SELECT rating, COUNT(*) AS cnt FROM reviews GROUP BY rating ORDER BY rating DESC
")->fetchAll();

$monthlyUsers = $db->query("
  SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt
  FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY month ORDER BY month
")->fetchAll();

$wishlistByGenre = $db->query("
  SELECT g.genre, COUNT(w.id) AS cnt
  FROM wishlist w JOIN games g ON g.id = w.game_id
  GROUP BY g.genre ORDER BY cnt DESC LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics — GameHub Admin</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">

<aside class="admin-sidebar" id="sidebar">
  <div class="sidebar-logo">GAME<span>HUB</span><small>ADMIN</small></div>
  <nav class="sidebar-nav">
    <a href="admin_dashboard.php"   class="nav-item">📊 Dashboard</a>
    <a href="manage_games.php"      class="nav-item">🎮 Games</a>
    <a href="manage_users.php"      class="nav-item">👥 Users</a>
    <a href="manage_reviews.php"    class="nav-item">⭐ Reviews</a>
    <a href="analytics.php"         class="nav-item active">📈 Analytics</a>
    <a href="manage_categories.php" class="nav-item">🏷️ Categories</a>
    <div class="sidebar-divider"></div>
    <a href="../index.html"          class="nav-item">🌐 View Site</a>
    <a href="../php/logout.php"      class="nav-item nav-logout">🚪 Logout</a>
  </nav>
</aside>

<div class="admin-main">
  <header class="admin-topbar">
    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
    <div class="topbar-title">📈 Platform Analytics</div>
    <div class="topbar-user">🛡️ <?= htmlspecialchars($_SESSION['username']) ?></div>
  </header>

  <div class="admin-content">

    <!-- KPI Cards -->
    <div class="admin-stats-row">
      <div class="admin-stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
          <div class="stat-num"><?= number_format($totals['total_users']) ?></div>
          <div class="stat-label">Total Users</div>
          <div class="stat-sub">Active 30d: <?= $totals['active_30d'] ?></div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-icon">🎮</div>
        <div class="stat-body">
          <div class="stat-num"><?= number_format($totals['total_games']) ?></div>
          <div class="stat-label">Total Games</div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-body">
          <div class="stat-num"><?= number_format($totals['total_reviews']) ?></div>
          <div class="stat-label">Total Reviews</div>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-icon">♥</div>
        <div class="stat-body">
          <div class="stat-num"><?= number_format($totals['total_wishlist']) ?></div>
          <div class="stat-label">Wishlist Saves</div>
        </div>
      </div>
    </div>

    <!-- Charts Row 1 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

      <!-- Genre Wishlist Bar Chart -->
      <div class="admin-card" style="padding:1.5rem">
        <div class="chart-title">♥ Most Wishlisted Genres</div>
        <canvas id="genreChart" height="220"></canvas>
      </div>

      <!-- Rating Distribution -->
      <div class="admin-card" style="padding:1.5rem">
        <div class="chart-title">⭐ Review Rating Distribution</div>
        <canvas id="ratingChart" height="220"></canvas>
      </div>
    </div>

    <!-- Charts Row 2 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

      <!-- User Growth -->
      <div class="admin-card" style="padding:1.5rem">
        <div class="chart-title">📈 User Registrations (6 Months)</div>
        <canvas id="growthChart" height="220"></canvas>
      </div>

      <!-- Wishlist by Genre Doughnut -->
      <div class="admin-card" style="padding:1.5rem">
        <div class="chart-title">🎯 Wishlist Breakdown</div>
        <canvas id="wishDoughnut" height="220"></canvas>
      </div>
    </div>

    <!-- Top Games Table -->
    <div class="admin-card" style="margin-bottom:1.5rem">
      <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-dim)">
        <div class="chart-title" style="margin:0">🏆 Top Games by Wishlist</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Game</th>
              <th>Genre</th>
              <th>Rating</th>
              <th>Wishlist Saves</th>
              <th>Reviews</th>
              <th>Views</th>
              <th>Popularity</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topGames as $i => $g): ?>
            <?php $pct = $totals['total_wishlist'] > 0 ? round($g['wishlist_count']/$totals['total_wishlist']*100,1) : 0; ?>
            <tr>
              <td><span class="rank-badge"><?= $i+1 ?></span></td>
              <td style="color:var(--text-prime);font-weight:600"><?= htmlspecialchars($g['title']) ?></td>
              <td><span style="font-size:.75rem;color:var(--neon-purple)"><?= $g['genre'] ?></span></td>
              <td><span style="color:var(--neon-gold)">★ <?= $g['rating'] ?></span></td>
              <td><span style="color:var(--neon-cyan);font-weight:700"><?= $g['wishlist_count'] ?></span></td>
              <td><?= $g['review_count'] ?></td>
              <td><span style="font-family:var(--font-mono);font-size:.78rem;color:var(--text-muted)"><?= number_format($g['views']) ?></span></td>
              <td>
                <div class="progress-bar-wrap">
                  <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
                  <span><?= $pct ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Genre Stats Table -->
    <div class="admin-card">
      <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-dim)">
        <div class="chart-title" style="margin:0">📊 Genre Performance</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Genre</th><th>Avg Rating</th><th>Reviews</th><th>Wishlist Saves</th></tr>
          </thead>
          <tbody>
            <?php foreach ($genreStats as $gs): ?>
            <tr>
              <td style="color:var(--neon-cyan);font-weight:600"><?= htmlspecialchars($gs['genre']) ?></td>
              <td><span style="color:var(--neon-gold)">★ <?= $gs['avg_rating'] ?></span></td>
              <td><?= $gs['review_count'] ?></td>
              <td><span style="color:var(--neon-purple);font-weight:700"><?= $gs['wishlist_count'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
  Chart.defaults.color = '#5a7a9a';
  Chart.defaults.borderColor = 'rgba(0,245,255,0.06)';
  Chart.defaults.font.family = "'Rajdhani', sans-serif";

  const neonCyan   = '#00f5ff';
  const neonPurple = '#b400ff';
  const neonPink   = '#ff006a';
  const neonGreen  = '#00ff88';
  const neonGold   = '#ffd700';

  /* Genre Wishlist */
  const genreData = <?= json_encode(array_column($wishlistByGenre,'cnt')) ?>;
  const genreLabels = <?= json_encode(array_column($wishlistByGenre,'genre')) ?>;
  new Chart(document.getElementById('genreChart'), {
    type:'bar',
    data:{ labels:genreLabels, datasets:[{
      label:'Wishlist Saves',
      data:genreData,
      backgroundColor:'rgba(0,245,255,.2)',
      borderColor:neonCyan,
      borderWidth:2,
      borderRadius:6
    }]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{grid:{color:'rgba(0,245,255,.05)'}}, x:{grid:{display:false}} } }
  });

  /* Rating Distribution */
  const ratingLabels = <?= json_encode(array_column($ratingDist,'rating')) ?>;
  const ratingCounts = <?= json_encode(array_column($ratingDist,'cnt')) ?>;
  const ratingColors = { 5:neonGreen, 4:neonCyan, 3:neonGold, 2:'#ff8800', 1:neonPink };
  new Chart(document.getElementById('ratingChart'), {
    type:'bar',
    data:{ labels:ratingLabels.map(r=>'★'.repeat(r)+' ('+r+')'), datasets:[{
      data:ratingCounts,
      backgroundColor:ratingLabels.map(r=>`${ratingColors[r]}33`),
      borderColor:ratingLabels.map(r=>ratingColors[r]),
      borderWidth:2, borderRadius:6
    }]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{grid:{color:'rgba(0,245,255,.05)'}}, x:{grid:{display:false}} } }
  });

  /* Growth Line */
  const growthMonths = <?= json_encode(array_column($monthlyUsers,'month')) ?>;
  const growthCounts = <?= json_encode(array_column($monthlyUsers,'cnt')) ?>;
  new Chart(document.getElementById('growthChart'), {
    type:'line',
    data:{ labels: growthMonths.length ? growthMonths : ['—'], datasets:[{
      label:'New Users',
      data: growthCounts.length ? growthCounts : [0],
      borderColor:neonPurple,
      backgroundColor:'rgba(180,0,255,.08)',
      fill:true, tension:.4, pointBackgroundColor:neonPurple, pointRadius:5
    }]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{grid:{color:'rgba(0,245,255,.05)'}}, x:{grid:{display:false}} } }
  });

  /* Doughnut */
  const doughnutLabels = <?= json_encode(array_column($wishlistByGenre,'genre')) ?>;
  const doughnutData   = <?= json_encode(array_column($wishlistByGenre,'cnt')) ?>;
  const dColors = [neonCyan, neonPurple, neonPink, neonGreen, neonGold, '#00aaff'];
  new Chart(document.getElementById('wishDoughnut'), {
    type:'doughnut',
    data:{ labels:doughnutLabels, datasets:[{
      data:doughnutData,
      backgroundColor:dColors.map(c=>c+'44'),
      borderColor:dColors,
      borderWidth:2
    }]},
    options:{ responsive:true, plugins:{ legend:{ position:'right', labels:{color:'#5a7a9a'} } } }
  });
</script>
<script src="../js/admin.js"></script>
<style>
  .chart-title { font-family:var(--font-display); font-size:.8rem; color:var(--text-prime); letter-spacing:1px; margin-bottom:1.25rem; }
  .rank-badge { display:inline-flex; width:24px; height:24px; align-items:center; justify-content:center; border-radius:50%; background:rgba(0,245,255,.1); border:1px solid rgba(0,245,255,.3); font-size:.72rem; color:var(--neon-cyan); font-weight:700; }
  .progress-bar-wrap { display:flex; align-items:center; gap:.5rem; }
  .progress-bar-wrap > div { flex:1; height:6px; background:var(--bg-glass); border-radius:3px; overflow:hidden; }
  .progress-bar-fill { height:100%; background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple)); border-radius:3px; }
  .progress-bar-wrap span { font-size:.72rem; color:var(--text-muted); min-width:35px; }
  .stat-sub { font-size:.72rem; color:var(--neon-green); margin-top:.25rem; }
</style>
</body>
</html>