<?php
/* ============================================================
   GameHub — partner_match.php  |  Gamer Partner Finder
   ============================================================
   FIXES APPLIED:
   1. findPartners() now filters by looking_for_partner = 1
      so only users who opted in appear as potential partners
   2. findPartners() fallback also filters by looking_for_partner
   3. saveProfile() now saves looking_for_partner = 1 when a
      user submits the form (makes them discoverable)
   4. saveProfile() also saves preferred_genre, skill_level,
      discord_tag — the new columns added to gamer_profiles
   5. member_since properly formatted as 4-digit year string
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

startSession();

$db     = getDB();
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? 'find';

switch ($action) {
    case 'find':          findPartners($db, $body);  break;
    case 'save_profile':  saveProfile($db, $body);   break;
    case 'get_profile':   getProfile($db);           break;
    case 'list_profiles': listProfiles($db);         break;
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)], 400);
}

/* ── Find Compatible Partners ───────────────────────────────── */
function findPartners(PDO $db, array $body): void {
    $favoriteGame = trim($body['favorite_game'] ?? $body['game']  ?? '');
    $playingTime  = trim($body['playing_time']  ?? $body['time']  ?? '');
    $playStyle    = trim($body['play_style']    ?? $body['style'] ?? '');
    $currentUser  = currentUserId() ?? 0;   // 0 = not logged in (guests can still search)

    /* Match scoring:
       • Same play_style    → 40 pts
       • Same playing_time  → 30 pts  (or their time is 'flexible')
       • Same favorite_game → 20 pts
       • Random noise       → 0–9 pts
    */
    $sql = "SELECT
                u.id,
                u.username,
                u.created_at          AS member_since,
                gp.favorite_game,
                gp.playing_time,
                gp.play_style,
                gp.preferred_genre,
                gp.skill_level,
                gp.discord_tag,
                (
                    (CASE WHEN gp.play_style    = :style                               THEN 40 ELSE 0 END)
                  + (CASE WHEN gp.playing_time  = :time
                            OR gp.playing_time  = 'flexible'                           THEN 30 ELSE 0 END)
                  + (CASE WHEN :game != '' AND gp.favorite_game LIKE :gameLike         THEN 20 ELSE 0 END)
                  + FLOOR(RAND() * 10)
                ) AS match_score
            FROM gamer_profiles gp
            JOIN users u ON gp.user_id = u.id
            WHERE gp.looking_for_partner = 1
              AND u.is_banned = 0
              AND gp.user_id != :me
            HAVING match_score > 0
            ORDER BY match_score DESC
            LIMIT 12";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':style'    => $playStyle,
        ':time'     => $playingTime,
        ':game'     => $favoriteGame,
        ':gameLike' => '%' . $favoriteGame . '%',
        ':me'       => $currentUser,
    ]);
    $partners = $stmt->fetchAll();

    // ── Relaxed fallback: show any available partner ──────────
    if (empty($partners)) {
        $relaxed = $db->prepare(
            'SELECT u.id, u.username, u.created_at AS member_since,
                    gp.favorite_game, gp.playing_time, gp.play_style,
                    gp.preferred_genre, gp.skill_level, gp.discord_tag,
                    FLOOR(RAND() * 40 + 50) AS match_score
             FROM gamer_profiles gp
             JOIN users u ON gp.user_id = u.id
             WHERE gp.looking_for_partner = 1
               AND u.is_banned = 0
               AND gp.user_id != ?
             ORDER BY RAND()
             LIMIT 6'
        );
        $relaxed->execute([$currentUser]);
        $partners = $relaxed->fetchAll();
    }

    // ── Format each partner for the JS card renderer ──────────
    $formatted = array_map(function (array $p): array {
        $score = min(99, max(50, (int)$p['match_score']));
        // Safe member_since — always return a 4-digit year string
        $memberSince = '2024';
        if (!empty($p['member_since'])) {
            $ts = strtotime($p['member_since']);
            if ($ts !== false) {
                $memberSince = date('Y', $ts);
            }
        }
        return [
            'id'            => (int)$p['id'],
            'username'      => $p['username'],
            'favorite_game' => $p['favorite_game'] ?: 'Various Games',
            'playing_time'  => $p['playing_time']  ?: 'Flexible',
            'play_style'    => ucfirst($p['play_style'] ?: 'casual'),
            'preferred_genre'=> $p['preferred_genre'] ?: '',
            'skill_level'   => ucfirst($p['skill_level'] ?: 'intermediate'),
            'discord_tag'   => $p['discord_tag'] ?: '',
            'match'         => $score,
            'online'        => ['🟢', '🟢', '🟡', '🔴'][rand(0, 3)],
            'member_since'  => $memberSince,
        ];
    }, $partners);

    jsonResponse([
        'success'  => true,
        'partners' => $formatted,
        'count'    => count($formatted),
    ]);
}

/* ── Save / Update Gamer Profile ────────────────────────────── */
function saveProfile(PDO $db, array $body): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false,
            'message' => 'Login required to save your gamer profile.',
            'auth'    => false]);
    }

    $favoriteGame   = sanitize($body['favorite_game']   ?? '');
    $playingTime    = $body['playing_time']  ?? 'evenings';
    $playStyle      = $body['play_style']    ?? 'casual';
    $preferredGenre = sanitize($body['preferred_genre'] ?? '');
    $skillLevel     = $body['skill_level']   ?? 'beginner';
    $discordTag     = sanitize($body['discord_tag']     ?? '');

    // Enforce ENUM values — must exactly match schema definition
    $validTimes  = ['mornings','afternoons','evenings','late night','weekends','flexible'];
    $validStyles = ['casual','competitive','cooperative','speedrun','roleplay'];
    $validSkills = ['beginner','intermediate','advanced','pro'];

    if (!in_array($playingTime, $validTimes))  $playingTime = 'evenings';
    if (!in_array($playStyle,   $validStyles)) $playStyle   = 'casual';
    if (!in_array($skillLevel,  $validSkills)) $skillLevel  = 'beginner';

    // Upsert — saving the profile automatically sets looking_for_partner = 1
    // so the user becomes visible to other players in the finder
    $existing = $db->prepare('SELECT profile_id FROM gamer_profiles WHERE user_id = ? LIMIT 1');
    $existing->execute([$userId]);

    if ($existing->fetch()) {
        $db->prepare(
            'UPDATE gamer_profiles
             SET favorite_game = ?, playing_time = ?, play_style = ?,
                 preferred_genre = ?, skill_level = ?, discord_tag = ?,
                 looking_for_partner = 1
             WHERE user_id = ?'
        )->execute([$favoriteGame, $playingTime, $playStyle,
                    $preferredGenre, $skillLevel, $discordTag, $userId]);
    } else {
        $db->prepare(
            'INSERT INTO gamer_profiles
                (user_id, favorite_game, playing_time, play_style,
                 preferred_genre, skill_level, discord_tag, looking_for_partner)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        )->execute([$userId, $favoriteGame, $playingTime, $playStyle,
                    $preferredGenre, $skillLevel, $discordTag]);
    }

    jsonResponse(['success' => true,
        'message' => 'Gamer profile saved! You are now visible to other players. 🎮']);
}

/* ── Get Own Profile ─────────────────────────────────────────── */
function getProfile(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    }

    $stmt = $db->prepare('SELECT * FROM gamer_profiles WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'profile' => $profile ?: [
            'favorite_game'       => '',
            'playing_time'        => 'evenings',
            'play_style'          => 'casual',
            'preferred_genre'     => '',
            'skill_level'         => 'beginner',
            'discord_tag'         => '',
            'looking_for_partner' => 0,
        ],
    ]);
}

/* ── List All Profiles (admin only) ──────────────────────────── */
function listProfiles(PDO $db): void {
    requireAdmin();
    $stmt = $db->query(
        'SELECT u.id, u.username, gp.*
         FROM gamer_profiles gp
         JOIN users u ON gp.user_id = u.id
         ORDER BY u.username ASC
         LIMIT 100'
    );
    jsonResponse(['success' => true, 'profiles' => $stmt->fetchAll()]);
}
