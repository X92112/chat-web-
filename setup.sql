-- Snapwall / Nexly Database Schema
-- Plain PHP 8 + MySQL (PDO)

CREATE DATABASE IF NOT EXISTS `snapwall` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `snapwall`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(80) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `status_mood` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(200) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `banner` VARCHAR(255) DEFAULT NULL,
  `last_seen` DATETIME DEFAULT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `is_banned` TINYINT(1) NOT NULL DEFAULT 0,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `thumb` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(150) DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_posts_user_id` (`user_id`),
  INDEX `idx_posts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Likes table
CREATE TABLE IF NOT EXISTS `likes` (
  `user_id` INT NOT NULL,
  `post_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `post_id`),
  INDEX `idx_likes_post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_comments_post_id` (`post_id`),
  INDEX `idx_comments_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Follows table
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` INT NOT NULL,
  `following_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`, `following_id`),
  INDEX `idx_follows_following_id` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `actor_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `post_id` INT DEFAULT NULL,
  `is_read` TINYINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_user_id` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1-to-1 Direct Messages table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `from_id` INT NOT NULL,
  `to_id` INT NOT NULL,
  `body` TEXT NULL,
  `media_type` VARCHAR(20) NOT NULL DEFAULT 'text',
  `media_url` VARCHAR(255) DEFAULT NULL,
  `media_thumb` VARCHAR(255) DEFAULT NULL,
  `media_duration` INT DEFAULT 0,
  `is_seen` TINYINT(1) NOT NULL DEFAULT 0,
  `seen_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_messages_conversation` (`from_id`, `to_id`, `created_at`),
  INDEX `idx_messages_to_seen` (`to_id`, `is_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public Lobby Messages table
CREATE TABLE IF NOT EXISTS `lobby_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `body` TEXT NULL,
  `media_type` VARCHAR(20) NOT NULL DEFAULT 'text',
  `media_url` VARCHAR(255) DEFAULT NULL,
  `media_thumb` VARCHAR(255) DEFAULT NULL,
  `media_duration` INT DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lobby_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Real-time Events table (channel-based SSE)
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `channel` VARCHAR(50) NOT NULL DEFAULT 'global',
  `user_id` INT DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL,
  `payload` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_channel_id` (`channel`, `id`),
  INDEX `idx_events_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Bookmarks / Saved Posts table
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `user_id` INT UNSIGNED NOT NULL,
  `post_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `post_id`),
  INDEX `idx_bookmarks_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24h Stories / Moments table
CREATE TABLE IF NOT EXISTS `stories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `thumb` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(150) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_stories_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
