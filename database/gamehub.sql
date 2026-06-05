-- ============================================================
--  GameHub — gamehub.sql  (UPDATED v2.0 — Role-Based Edition)
--  Complete Database Schema + Seed Data
--  Import via phpMyAdmin or:  mysql -u root gamehub < gamehub.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Create & select database ──────────────────────────────────


-- ══════════════════════════════════════════════════════════════
--  TABLE: categories
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL UNIQUE,
  icon       VARCHAR(10)  DEFAULT '🎮',
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, icon) VALUES
  ('Action',        '⚔️'),
  ('RPG',           '🧙'),
  ('FPS',           '🔫'),
  ('Strategy',      '♟️'),
  ('Sports',        '⚽'),
  ('Racing',        '🏎️'),
  ('Horror',        '👻'),
  ('Adventure',     '🗺️'),
  ('Simulation',    '🏗️'),
  ('Fighting',      '🥊'),
  ('Puzzle',        '🧩'),
  ('Battle Royale', '🎯'),
  ('MOBA',          '🏆'),
  ('Stealth',       '🕵️'),
  ('Survival',      '🌲');

-- ══════════════════════════════════════════════════════════════
--  TABLE: users  (UPDATED — role-based auth)
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username         VARCHAR(30)          NOT NULL UNIQUE,
  email            VARCHAR(150)         NOT NULL UNIQUE,
  password_hash    VARCHAR(255)         NOT NULL,
  role             ENUM('user','admin') NOT NULL DEFAULT 'user',
  favorite_genres  VARCHAR(255)         DEFAULT '',
  avatar_url       VARCHAR(255)         DEFAULT '',
  bio              TEXT,
  is_banned        TINYINT(1)           DEFAULT 0,
  last_login       TIMESTAMP            NULL,
  created_at       TIMESTAMP            DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email    (email),
  INDEX idx_username (username),
  INDEX idx_role     (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed users ────────────────────────────────────────────────
-- Default admin  (password: admin123)
-- Default tester (password: gamer123)
INSERT INTO users (username, email, password_hash, role, favorite_genres) VALUES
  ('admin',       'admin@gamehub.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'RPG,Action,Strategy'),
  ('NeonSword99', 'neon@gamehub.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'FPS,Action,Battle Royale'),
  ('PixelHunter', 'pixel@gamehub.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'RPG,Adventure'),
  ('CryptoMage',  'crypto@gamehub.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Strategy,Simulation'),
  ('VoidWalker',  'void@gamehub.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'Horror,Survival,Stealth');

-- ══════════════════════════════════════════════════════════════
--  TABLE: games
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS games;
CREATE TABLE games (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  genre        VARCHAR(50)  NOT NULL,
  description  TEXT,
  rating       DECIMAL(3,1) DEFAULT 0.0,
  release_date DATE         NULL,
  image_url    VARCHAR(255) DEFAULT '',
  cracked_link VARCHAR(500) DEFAULT NULL  COMMENT "External cracked/download link (admin only)",
  is_trending  TINYINT(1)   DEFAULT 0,
  category_id  INT UNSIGNED NULL,
  min_ram      INT UNSIGNED DEFAULT 8    COMMENT 'GB',
  min_gpu      VARCHAR(100) DEFAULT '',
  min_cpu      VARCHAR(100) DEFAULT '',
  min_storage  INT UNSIGNED DEFAULT 50   COMMENT 'GB',
  views        INT UNSIGNED DEFAULT 0,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_genre    (genre),
  INDEX idx_rating   (rating),
  INDEX idx_trending (is_trending),
  INDEX idx_release  (release_date),
  CONSTRAINT fk_game_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO games (title, genre, description, rating, release_date, is_trending, category_id, min_ram, min_gpu, min_cpu, min_storage, views) VALUES
  ('Cyber Odyssey 2049',     'RPG',           'An open-world cyberpunk RPG set in a neon-soaked megacity.',                                      4.8, '2024-03-15', 1,  2,  16, 'RTX 3060',       'Intel i7-10700',   80,  125000),
  ('Phantom Strike',         'FPS',           'A tactical first-person shooter with destructible environments.',                                   4.5, '2024-01-20', 1,  3,  8,  'GTX 1660 Super', 'Intel i5-9600K',   60,  98000),
  ('Galaxy Commanders',      'Strategy',      'Lead your interstellar armada across procedurally generated galaxy maps.',                          4.3, '2023-09-10', 0,  4,  8,  'GTX 1060',       'Intel i5-8400',    40,  72000),
  ('Neon Racer X',           'Racing',        'High-octane anti-gravity racing on cyberpunk tracks.',                                             4.6, '2024-06-01', 1,  6,  8,  'GTX 1070',       'Intel i5-8600',    30,  89000),
  ('Shadow Covenant',        'Stealth',       'Infiltrate a global shadow organisation as an elite operative.',                                    4.4, '2023-11-25', 0,  14, 12, 'RTX 2060',       'Intel i7-8700K',   70,  54000),
  ('Abyss Survival',         'Survival',      'Crash-land on an alien ocean planet. Gather resources, build underwater bases.',                   4.2, '2024-04-12', 0,  15, 8,  'GTX 1080',       'Intel i5-8600',    20,  61000),
  ('Iron Fist Championship', 'Fighting',      'Next-gen fighting game featuring 60 unique fighters.',                                             4.1, '2023-07-30', 0,  10, 8,  'RTX 2060',       'Intel i7-8700',    40,  45000),
  ('Cursed Hollow',          'Horror',        'Co-op survival horror — four players explore a procedurally generated haunted mansion.',            4.7, '2024-02-14', 1,  7,  12, 'GTX 1070',       'Intel i5-9600',    25,  113000),
  ('World Architect',        'Simulation',    'Build and manage civilisations across 10,000 years of history.',                                   4.0, '2023-05-18', 0,  9,  8,  'GTX 1060',       'Intel i5-6600',    15,  38000),
  ('Puzzle Dimension',       'Puzzle',        'Mind-bending 3D puzzles that warp geometry and physics.',                                          4.9, '2024-07-22', 0,  11, 4,  'GTX 960',        'Intel i3-8100',    5,   29000),
  ('Storm Legends',          'Battle Royale', 'A fantasy Battle Royale where 100 players with unique abilities compete.',                          4.3, '2023-12-05', 1,  12, 8,  'GTX 1660',       'Intel i5-9400',    30,  141000),
  ('Eternal Kingdoms',       'MOBA',          'Team-based 5v5 MOBA featuring over 120 champions.',                                                4.2, '2022-08-15', 0,  13, 8,  'GTX 1050 Ti',    'Intel i5-3570',    30,  210000),
  ('Desert Mirage',          'Action',        'Third-person action-adventure set in a mythological Middle Eastern world.',                         4.5, '2024-05-30', 1,  1,  12, 'RTX 2070',       'Intel i7-9700K',   60,  77000),
  ('Arctic Expedition',      'Adventure',     'Explore a frozen open world as a survival photographer.',                                          4.0, '2023-10-08', 0,  8,  8,  'GTX 1070',       'Intel i5-8500',    40,  34000),
  ('VR Warriors',            'Fighting',      'Full VR support fighting game.',                                                                   4.3, '2024-08-18', 0,  10, 16, 'RTX 3070',       'Intel i7-10700K',  20,  48000),
  ('Apex Dungeon',           'RPG',           'Hack-and-slash dungeon crawler with randomly generated floors.',                                   4.6, '2023-06-22', 0,  2,  8,  'GTX 1060',       'Intel i5-7600K',   15,  91000),
  ('MechWar: Uprising',      'Action',        'Pilot massive mechs in fast-paced 6v6 battles.',                                                   4.4, '2024-03-29', 1,  1,  12, 'RTX 2060 Super', 'Intel i7-8700',    40,  67000),
  ('Fleet Commander',        'Strategy',      'Real-time naval strategy with historically accurate warships from 1900-1950.',                      4.1, '2023-04-14', 0,  4,  8,  'GTX 1060',       'Intel i5-7500',    25,  28000),
  ('Velocity Rush',          'Racing',        'Street-racing game with full car damage modelling and realistic physics.',                          4.5, '2024-09-01', 1,  6,  12, 'RTX 3060 Ti',    'Intel i7-10700',   50,  82000),
  ('The Last Colony',        'Survival',      'A post-apocalyptic city builder. Manage resources and protect your colony from mutant hordes.',    4.2, '2023-08-27', 0,  15, 8,  'GTX 1070',       'Intel i5-8400',    30,  44000);

-- ══════════════════════════════════════════════════════════════
--  TABLE: reviews
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  game_id         INT UNSIGNED NOT NULL,
  rating          TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment         TEXT NOT NULL,
  is_approved     TINYINT(1)   DEFAULT 1,
  helpful_votes   INT UNSIGNED DEFAULT 0,
  unhelpful_votes INT UNSIGNED DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_game (user_id, game_id),
  INDEX idx_game_id (game_id),
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reviews (user_id, game_id, rating, comment, helpful_votes) VALUES
  (2, 1,  5, 'Absolutely mind-blowing open world. The story choices actually matter and the cybernetic upgrades feel satisfying. Easily my GOTY.', 47),
  (3, 1,  4, 'Beautiful game with incredible world-building. Minor bugs at launch but now mostly patched.', 31),
  (4, 2,  5, 'Best tactical shooter on the market right now. The environmental destruction is next level.', 38),
  (2, 3,  4, 'Deepest space strategy I have played. The diplomacy system is surprisingly nuanced.', 22),
  (5, 4,  5, 'Neon Racer X is pure adrenaline! The anti-gravity mechanics feel fresh.', 55),
  (3, 8,  5, 'Cursed Hollow is the scariest game I have played. The procedural generation keeps every run fresh.', 63),
  (2, 11, 4, 'Storm Legends is great fun. The unique abilities add a lot of depth to the Battle Royale formula.', 29),
  (4, 10, 5, 'Puzzle Dimension is absolutely perfect. Beautifully designed puzzles.', 41),
  (5, 13, 4, 'Desert Mirage has stunning visuals and satisfying combat. The mythological setting is refreshing.', 33);

-- ══════════════════════════════════════════════════════════════
--  TABLE: wishlist
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS wishlist;
CREATE TABLE wishlist (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  INT UNSIGNED NOT NULL,
  game_id  INT UNSIGNED NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_game (user_id, game_id),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wishlist (user_id, game_id) VALUES
  (2, 3), (2, 4), (2, 8),
  (3, 1), (3, 11), (3, 13),
  (4, 2), (4, 16), (4, 19),
  (5, 1), (5, 4), (5, 17);

-- ══════════════════════════════════════════════════════════════
--  TABLE: gamer_profiles
-- ══════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS gamer_profiles;
CREATE TABLE gamer_profiles (
  profile_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  favorite_game VARCHAR(200) DEFAULT '',
  playing_time  ENUM('mornings','afternoons','evenings','late night','weekends','flexible') DEFAULT 'evenings',
  play_style    ENUM('casual','competitive','cooperative','speedrun','roleplay') DEFAULT 'casual',
  bio           TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gamer_profiles (user_id, favorite_game, playing_time, play_style) VALUES
  (2, 'Phantom Strike',     'evenings',   'competitive'),
  (3, 'Cyber Odyssey 2049', 'weekends',   'casual'),
  (4, 'Galaxy Commanders',  'evenings',   'competitive'),
  (5, 'Cursed Hollow',      'late night', 'cooperative');


-- ── Partner Requests Table ────────────────────────────────────
CREATE TABLE IF NOT EXISTS partner_requests (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id   INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  message     VARCHAR(200) DEFAULT '',
  status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_request (sender_id, receiver_id),
  FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Quick-access queries ─────────────────────────────────────
-- Login as admin:  email=admin@gamehub.com  password=admin123
-- Login as user:   email=neon@gamehub.com   password=gamer123

-- ── Run this if your database already exists (adds cracked_link column) ──
ALTER TABLE games ADD COLUMN IF NOT EXISTS cracked_link VARCHAR(500) DEFAULT NULL COMMENT 'External cracked/download link (admin only)' AFTER image_url;

-- ════════════════════════════════════════════
-- Run these if upgrading an existing database
-- ════════════════════════════════════════════
ALTER TABLE users  ADD COLUMN IF NOT EXISTS last_login    TIMESTAMP NULL DEFAULT NULL AFTER password_hash;
ALTER TABLE users  ADD COLUMN IF NOT EXISTS favorite_genres VARCHAR(200) DEFAULT NULL AFTER bio;
ALTER TABLE games  ADD COLUMN IF NOT EXISTS views         INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_trending;
ALTER TABLE games  ADD COLUMN IF NOT EXISTS cracked_link  VARCHAR(500) DEFAULT NULL AFTER image_url;
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS helpful_votes   INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS unhelpful_votes INT UNSIGNED NOT NULL DEFAULT 0;