<?php
/* ============================================================
   GameHub — compatibility.php  |  PC Compatibility Checker
   ============================================================
   FIX LOG:
   • games table uses column "genre" directly — no JOIN needed
   • scoreGame() uses correct column names from schema
   • Results now include rec_ram / rec_gpu from schema
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db     = getDB();
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? 'check';

switch ($action) {
    case 'check':        checkCompatibility($db, $body); break;
    case 'get_games':    getGamesForCheck($db);           break;
    case 'requirements': getGameRequirements($db);        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

// ── Main Compatibility Check ──────────────────────────────────
function checkCompatibility(PDO $db, array $body): void {
    $ram     = (int)($body['ram']     ?? 0);
    $gpu     = trim($body['gpu']      ?? '');
    $cpu     = trim($body['cpu']      ?? '');
    $storage = (int)($body['storage'] ?? 0);
    $gameId  = (int)($body['game_id'] ?? 0);

    if (!$ram || !$gpu || !$cpu) {
        jsonResponse(['success' => false, 'message' => 'Please provide RAM (GB), GPU model, and CPU model.'], 400);
    }

    $systemScore = scoreSystem($ram, $gpu, $cpu, $storage);

    if ($gameId) {
        $stmt = $db->prepare('SELECT * FROM games WHERE id = ? LIMIT 1');
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if ($game) {
            $gameScore = scoreGame($game);
            $result    = determineCompatibility($systemScore, $gameScore);
            jsonResponse([
                'success'       => true,
                'system_score'  => $systemScore,
                'game_score'    => $gameScore,
                'compatibility' => $result,
                'game_title'    => $game['title'],
                'requirements'  => [
                    'min_ram'     => $game['min_ram'],
                    'min_gpu'     => $game['min_gpu'],
                    'min_cpu'     => $game['min_cpu'],
                    'min_storage' => $game['min_storage'],
                ],
                'results' => [[
                    'id'            => (int)$game['id'],
                    'title'         => $game['title'],
                    'genre'         => $game['genre'],
                    'score'         => (int)($result['percent'] ?? $systemScore),
                    'compatibility' => $result,
                    'min_ram'       => $game['min_ram'],
                    'min_gpu'       => $game['min_gpu'],
                    'min_cpu'       => $game['min_cpu'],
                    'min_storage'   => $game['min_storage'],
                ]],
            ]);
            return;
        }
    }

    // Check against all games
    $stmt = $db->query('SELECT * FROM games ORDER BY rating DESC LIMIT 50');
    $games = $stmt->fetchAll();

    $results = [];
    foreach ($games as $game) {
        $gameScore = scoreGame($game);
        $compat    = determineCompatibility($systemScore, $gameScore);
        $results[] = [
            'id'            => (int)$game['id'],
            'title'         => $game['title'],
            'genre'         => $game['genre'],
            'score'         => $compat['percent'],
            'compatibility' => $compat,
            'min_ram'       => $game['min_ram'],
            'min_gpu'       => $game['min_gpu'],
            'min_cpu'       => $game['min_cpu'],
            'min_storage'   => $game['min_storage'],
        ];
    }

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

    jsonResponse([
        'success'      => true,
        'system_score' => $systemScore,
        'results'      => $results,
        'summary'      => [
            'compatible' => count(array_filter($results, fn($r) => $r['compatibility']['level'] === 'good')),
            'medium'     => count(array_filter($results, fn($r) => $r['compatibility']['level'] === 'medium')),
            'poor'       => count(array_filter($results, fn($r) => $r['compatibility']['level'] === 'poor')),
        ],
    ]);
}

// ── Score user system (0–100) ─────────────────────────────────
function scoreSystem(int $ram, string $gpu, string $cpu, int $storage): int {
    $score = 0;

    // RAM — max 25 pts
    if      ($ram >= 32) $score += 25;
    elseif  ($ram >= 16) $score += 20;
    elseif  ($ram >= 8)  $score += 12;
    elseif  ($ram >= 4)  $score += 6;
    else                  $score += 2;

    // GPU — max 40 pts
    $score += scoreGPU($gpu);

    // CPU — max 25 pts
    $c = strtoupper($cpu);
    if      (preg_match('/I9|RYZEN 9|EPYC|THREADRIPPER/i', $c))       $score += 25;
    elseif  (preg_match('/I7-1[2-4]\d{3}|RYZEN 7 7\d{3}/i', $c))      $score += 22;
    elseif  (preg_match('/I7|RYZEN 7/i', $c))                          $score += 18;
    elseif  (preg_match('/I5-1[2-4]\d{3}|RYZEN 5 7\d{3}/i', $c))      $score += 15;
    elseif  (preg_match('/I5|RYZEN 5/i', $c))                          $score += 12;
    elseif  (preg_match('/I3|RYZEN 3/i', $c))                          $score += 7;
    else                                                                 $score += 4;

    // Storage — max 10 pts
    if      ($storage >= 1000) $score += 10;
    elseif  ($storage >= 500)  $score += 7;
    elseif  ($storage >= 256)  $score += 4;
    else                        $score += 1;

    return min($score, 100);
}

function scoreGPU(string $gpu): int {
    $tiers = [
        40 => ['RTX 4090','RTX 4080','RTX 4070 Ti'],
        38 => ['RTX 4070','RX 7900 XTX','RX 7900 XT'],
        35 => ['RTX 3090','RTX 3080 Ti','RTX 3080'],
        32 => ['RTX 3070 Ti','RTX 3070','RX 6900','RX 6800'],
        28 => ['RTX 3060 Ti','RTX 3060','RX 6700','RX 6600 XT'],
        24 => ['RTX 2080 Ti','RTX 2080 Super','RTX 2080'],
        20 => ['RTX 2070','RTX 2060 Super','RX 5700 XT','GTX 1080 Ti'],
        16 => ['RTX 2060','GTX 1080','RX 5600','RX 580'],
        12 => ['GTX 1070 Ti','GTX 1070','GTX 1660 Super','GTX 1660'],
        8  => ['GTX 1060','GTX 1650','RX 570','RX 560'],
        5  => ['GTX 1050 Ti','GTX 1050','RX 550','GTX 960'],
    ];
    $g = strtoupper($gpu);
    foreach ($tiers as $pts => $cards) {
        foreach ($cards as $card) {
            if (str_contains($g, strtoupper($card))) return $pts;
        }
    }
    if (preg_match('/GTX|RTX|RX |RADEON|GEFORCE|QUADRO/i', $g)) return 8;
    return 4; // integrated / unknown
}

// ── Score a game's minimum requirements ──────────────────────
function scoreGame(array $game): int {
    $score = 0;
    $ram   = (int)($game['min_ram']     ?? 8);
    $gpu   =       $game['min_gpu']     ?? '';
    $cpu   =       $game['min_cpu']     ?? '';
    $store = (int)($game['min_storage'] ?? 50);

    if      ($ram >= 32) $score += 22;
    elseif  ($ram >= 16) $score += 17;
    elseif  ($ram >= 8)  $score += 10;
    else                  $score += 5;

    $score += scoreGPU($gpu);

    $c = strtoupper($cpu);
    if      (preg_match('/I9|RYZEN 9/i', $c))   $score += 23;
    elseif  (preg_match('/I7|RYZEN 7/i', $c))   $score += 17;
    elseif  (preg_match('/I5|RYZEN 5/i', $c))   $score += 11;
    elseif  (preg_match('/I3|RYZEN 3/i', $c))   $score += 6;
    else                                          $score += 4;

    if      ($store >= 100) $score += 8;
    elseif  ($store >= 50)  $score += 5;
    else                     $score += 2;

    return min($score, 100);
}

// ── Compatibility level ───────────────────────────────────────
function determineCompatibility(int $sys, int $game): array {
    $ratio = $game > 0 ? ($sys / $game) : 1;

    if ($ratio >= 1.1) {
        return [
            'level'   => 'good',
            'label'   => 'Compatible ✓',
            'detail'  => 'Your PC exceeds minimum requirements. Enjoy smooth gameplay!',
            'score'   => 3,
            'color'   => '#00ff88',
            'percent' => min(100, (int)($ratio * 80)),
        ];
    }
    if ($ratio >= 0.75) {
        return [
            'level'   => 'medium',
            'label'   => 'Medium Performance ⚡',
            'detail'  => 'Your PC meets minimum specs. Expect playable performance on medium settings.',
            'score'   => 2,
            'color'   => '#ffd700',
            'percent' => (int)($ratio * 75),
        ];
    }
    return [
        'level'   => 'poor',
        'label'   => 'Not Recommended ✕',
        'detail'  => 'Your PC falls below minimum requirements. The game may not run properly.',
        'score'   => 1,
        'color'   => '#ff2d78',
        'percent' => max(10, (int)($ratio * 60)),
    ];
}

// ── Games dropdown ────────────────────────────────────────────
function getGamesForCheck(PDO $db): void {
    $stmt = $db->query('SELECT id, title, genre FROM games ORDER BY title ASC LIMIT 100');
    jsonResponse(['success' => true, 'games' => $stmt->fetchAll()]);
}

// ── Single game requirements ──────────────────────────────────
function getGameRequirements(PDO $db): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Game ID required'], 400);

    $stmt = $db->prepare('SELECT id, title, min_ram, min_gpu, min_cpu, min_storage FROM games WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $game = $stmt->fetch();

    if (!$game) jsonResponse(['success' => false, 'message' => 'Game not found'], 404);
    jsonResponse(['success' => true, 'game' => $game]);
}