-- ============================================================
--  EventHub EMS — phpMyAdmin Import File
--  Import this directly in phpMyAdmin
--  Database: ems_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ems_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ems_db`;

-- ────────────────────────────────────────────────────────────
--  TABLE: categories
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `color`      VARCHAR(7)   NOT NULL DEFAULT '#6366f1',
  `icon`       VARCHAR(10)  NOT NULL DEFAULT '📅',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: events
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200)  NOT NULL,
  `description` TEXT,
  `category_id` INT           DEFAULT NULL,
  `event_date`  DATE          NOT NULL,
  `event_time`  TIME          NOT NULL DEFAULT '09:00:00',
  `location`    VARCHAR(200)  NOT NULL,
  `capacity`    INT           NOT NULL DEFAULT 100,
  `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`      ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_event_category` (`category_id`),
  CONSTRAINT `fk_event_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: admins
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: bookings
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`        INT  NOT NULL AUTO_INCREMENT,
  `user_id`   INT  NOT NULL,
  `event_id`  INT  NOT NULL,
  `tickets`   INT  NOT NULL DEFAULT 1,
  `status`    ENUM('confirmed','cancelled','pending') NOT NULL DEFAULT 'confirmed',
  `booked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_event` (`user_id`, `event_id`),
  KEY `fk_booking_event` (`event_id`),
  CONSTRAINT `fk_booking_user`
    FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- ── Categories ───────────────────────────────────────────────
INSERT INTO `categories` (`name`, `color`, `icon`) VALUES
('Music',        '#8b5cf6', '🎵'),
('Technology',   '#3b82f6', '💻'),
('Sports',       '#10b981', '⚽'),
('Arts & Culture','#f59e0b','🎨'),
('Business',     '#6366f1', '💼'),
('Food & Drink', '#ef4444', '🍕'),
('Health',       '#06b6d4', '🏥'),
('Education',    '#84cc16', '📚');


-- ── Admin account ────────────────────────────────────────────
-- Email   : admin@eventhub.com
-- Password: admin123
-- (bcrypt hash of "admin123")
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Admin', 'admin@eventhub.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


-- ── Sample Users ─────────────────────────────────────────────
-- Password for all users: password123
INSERT INTO `users` (`name`, `email`, `password`) VALUES
('Arjun Sharma',  'arjun@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Priya Patel',   'priya@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Rahul Verma',   'rahul@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Sneha Nair',    'sneha@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Dev Kumar',     'dev@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


-- ── Sample Events ────────────────────────────────────────────
INSERT INTO `events` (`title`, `description`, `category_id`, `event_date`, `event_time`, `location`, `capacity`, `price`, `status`) VALUES

('Tech Summit 2025',
 'A full-day conference bringing together developers, founders, and tech enthusiasts from across India. Featuring keynotes, workshops, and networking sessions.',
 2, '2025-08-15', '09:00:00', 'NIMHANS Convention Centre, Bengaluru', 500, 999.00, 'upcoming'),

('Classical Music Night',
 'An enchanting evening of Hindustani classical music performed by award-winning artists. Experience the magic of ragas under the stars.',
 1, '2025-07-20', '18:30:00', 'Chowdiah Memorial Hall, Bengaluru', 300, 499.00, 'upcoming'),

('Startup Pitch Battle',
 'Top 10 startups compete for a ₹10 lakh seed grant. Open for all to attend and witness the next big ideas. Judges include prominent VCs and angel investors.',
 5, '2025-07-28', '10:00:00', 'T-Hub, Hyderabad', 200, 0.00, 'upcoming'),

('Mysuru Food Festival',
 'Celebrate the rich culinary heritage of Karnataka with over 60 food stalls, live cooking demos, and food challenges. Family friendly event!',
 6, '2025-08-03', '11:00:00', 'Mysore Palace Grounds, Mysuru', 1000, 0.00, 'upcoming'),

('Photography Workshop',
 'Hands-on workshop covering composition, lighting, and post-processing. Bring your DSLR or mirrorless camera. All skill levels welcome.',
 4, '2025-07-25', '10:00:00', 'The Photography Studio, Pune', 30, 1499.00, 'upcoming'),

('IPL Fan Zone — Finals Watch Party',
 'Watch the IPL final on a massive LED screen with fellow cricket fans. Food stalls, games, and prizes throughout the event.',
 3, '2025-05-25', '19:00:00', 'Kanteerava Stadium Grounds, Bengaluru', 2000, 199.00, 'completed'),

('Digital Marketing Bootcamp',
 'Two-day intensive bootcamp covering SEO, paid ads, social media strategy, and analytics. Certificate provided on completion.',
 8, '2025-09-10', '09:00:00', 'IIM Bangalore Campus, Bengaluru', 80, 2499.00, 'upcoming'),

('Wellness & Yoga Retreat',
 'A rejuvenating one-day retreat with expert-led yoga sessions, meditation, nutrition talks, and healthy meals included.',
 7, '2025-08-10', '07:00:00', 'Coorg Wilderness Resort, Coorg', 50, 3999.00, 'upcoming');


-- ── Sample Bookings ───────────────────────────────────────────
INSERT INTO `bookings` (`user_id`, `event_id`, `tickets`, `status`) VALUES
(1, 1, 2, 'confirmed'),
(2, 1, 1, 'confirmed'),
(3, 1, 3, 'confirmed'),
(1, 3, 1, 'confirmed'),
(4, 3, 2, 'confirmed'),
(2, 4, 4, 'confirmed'),
(5, 4, 2, 'confirmed'),
(3, 2, 2, 'confirmed'),
(1, 6, 1, 'confirmed'),
(2, 6, 2, 'confirmed'),
(4, 5, 1, 'confirmed'),
(5, 7, 1, 'confirmed'),
(3, 8, 1, 'cancelled');
