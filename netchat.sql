-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 04 mai 2026 à 08:54
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `netchat`
--

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 18, 3, 'Hey', '2026-04-27 08:39:08');

-- --------------------------------------------------------

--
-- Structure de la table `comment_mentions`
--

DROP TABLE IF EXISTS `comment_mentions`;
CREATE TABLE IF NOT EXISTS `comment_mentions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `mentioned_user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comment_mention` (`comment_id`,`mentioned_user_id`),
  KEY `mentioned_user_id` (`mentioned_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `id` int NOT NULL AUTO_INCREMENT,
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  KEY `idx_following` (`following_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(14, 6, 3, '2026-04-27 08:38:19'),
(15, 3, 2, '2026-04-27 08:43:18'),
(16, 2, 3, '2026-04-29 06:43:51');

-- --------------------------------------------------------

--
-- Structure de la table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
CREATE TABLE IF NOT EXISTS `friendships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `friend_id` int NOT NULL,
  `status` enum('pending','accepted','blocked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_friendship` (`user_id`,`friend_id`),
  KEY `friend_id` (`friend_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `friendships`
--

INSERT INTO `friendships` (`id`, `user_id`, `friend_id`, `status`, `created_at`) VALUES
(4, 3, 2, 'accepted', '2026-03-09 13:01:49'),
(5, 4, 2, 'accepted', '2026-03-09 14:45:23'),
(7, 3, 4, 'accepted', '2026-03-10 07:38:57'),
(8, 4, 3, 'accepted', '2026-03-10 07:39:43');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `is_read`, `created_at`) VALUES
(1, 5, 1, 'Salut', 1, '2026-04-25 13:41:09'),
(2, 1, 5, 'Salut mec', 1, '2026-04-25 13:41:14'),
(3, 5, 1, 'eeee*', 1, '2026-04-25 14:00:38'),
(4, 2, 3, 'Salut !', 1, '2026-04-29 06:43:59'),
(5, 3, 2, 'Salut !', 0, '2026-04-29 06:46:26');

-- --------------------------------------------------------

--
-- Structure de la table `message_requests`
--

DROP TABLE IF EXISTS `message_requests`;
CREATE TABLE IF NOT EXISTS `message_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `requester_id` int NOT NULL,
  `target_id` int NOT NULL,
  `status` enum('pending','accepted','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_request` (`requester_id`,`target_id`),
  KEY `idx_target_status` (`target_id`,`status`),
  KEY `idx_requester` (`requester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('friend_request','friend_accept','follow','mention','message','system','like','comment','message_request','report') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` int DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_user_time` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `actor_id`, `target_id`, `data`, `is_read`, `created_at`) VALUES
(4, 5, 'follow', 1, NULL, NULL, 1, '2026-04-25 13:40:52'),
(6, 5, 'message', 1, NULL, '{\"excerpt\": \"Salut mec\", \"message_id\": 2}', 1, '2026-04-25 13:41:14'),
(8, 3, 'like', 6, 17, '{\"excerpt\": \"Bonjour !\", \"post_id\": 17}', 1, '2026-04-27 08:38:03'),
(9, 3, 'follow', 6, NULL, NULL, 0, '2026-04-27 08:38:19'),
(10, 6, 'comment', 3, 18, '{\"excerpt\": \"Hey\", \"post_id\": 18, \"comment_id\": 1}', 0, '2026-04-27 08:39:08'),
(11, 6, 'comment', 3, 18, '{\"excerpt\": \"Hey\", \"post_id\": 18}', 0, '2026-04-27 08:39:08'),
(12, 6, 'like', 3, 18, '{\"excerpt\": \"Salut @FoxyTheBigYT\", \"post_id\": 18}', 0, '2026-04-27 08:39:09'),
(13, 2, 'follow', 3, NULL, NULL, 0, '2026-04-27 08:43:18'),
(14, 3, 'like', 6, 19, '{\"excerpt\": \"#actu Guerre au Moyen-Orient: le chef de la diplomatie iranienne accuse les États-Unis de l\'échec des discussions au Pak\", \"post_id\": 19}', 0, '2026-04-27 09:01:31'),
(15, 6, 'like', 2, 20, '{\"excerpt\": \"#actu #usa Un suspect a été arrêté après son intrusion, armé, dans l\'hôtel Hilton de Washington, où avait lieu le tradit\", \"post_id\": 20}', 0, '2026-04-29 06:38:17'),
(16, 3, 'like', 2, 19, '{\"excerpt\": \"#actu Guerre au Moyen-Orient: le chef de la diplomatie iranienne accuse les États-Unis de l\'échec des discussions au Pak\", \"post_id\": 19}', 0, '2026-04-29 06:38:20'),
(17, 6, 'like', 2, 18, '{\"excerpt\": \"Salut @FoxyTheBigYT\", \"post_id\": 18}', 0, '2026-04-29 06:38:23'),
(18, 3, 'follow', 2, NULL, NULL, 0, '2026-04-29 06:43:51'),
(19, 3, 'message', 2, NULL, '{\"excerpt\": \"Salut !\", \"message_id\": 4}', 0, '2026-04-29 06:43:59'),
(20, 2, 'message', 3, NULL, '{\"excerpt\": \"Salut !\", \"message_id\": 5}', 0, '2026-04-29 06:46:26');

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 2, 'lets goat #test @test', NULL, '2026-03-09 10:18:06', '2026-03-09 10:18:06'),
(3, 3, 'test @Malenia', NULL, '2026-03-09 12:46:26', '2026-03-09 12:46:26'),
(17, 3, 'Bonjour !', 'assets/posts/69ef201632d62_1777278998.jpg', '2026-04-27 08:36:38', '2026-04-27 08:36:38'),
(18, 6, 'Salut @FoxyTheBigYT', NULL, '2026-04-27 08:37:56', '2026-04-27 08:37:56'),
(19, 3, '#actu Guerre au Moyen-Orient: le chef de la diplomatie iranienne accuse les États-Unis de l\'échec des discussions au Pakistan', 'assets/posts/69ef219459257_1777279380.jpg', '2026-04-27 08:43:00', '2026-04-27 08:43:00'),
(20, 6, '#actu #usa Un suspect a été arrêté après son intrusion, armé, dans l\'hôtel Hilton de Washington, où avait lieu le traditionnel dîner des correspondants de presse. Donald et Melania Trump ont été évacués.', NULL, '2026-04-27 09:01:25', '2026-04-27 09:01:25'),
(21, 2, '@FoxyTheBigYT', NULL, '2026-04-29 06:44:15', '2026-04-29 06:44:15');

-- --------------------------------------------------------

--
-- Structure de la table `post_mentions`
--

DROP TABLE IF EXISTS `post_mentions`;
CREATE TABLE IF NOT EXISTS `post_mentions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `mentioned_user_id` int NOT NULL,
  `mention_position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mention` (`post_id`,`mentioned_user_id`),
  KEY `mentioned_user_id` (`mentioned_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `post_mentions`
--

INSERT INTO `post_mentions` (`id`, `post_id`, `mentioned_user_id`, `mention_position`, `created_at`) VALUES
(1, 3, 2, 5, '2026-03-09 12:47:55'),
(7, 18, 3, 6, '2026-04-27 08:37:56'),
(8, 21, 3, 0, '2026-04-29 06:44:15');

-- --------------------------------------------------------

--
-- Structure de la table `reactions`
--

DROP TABLE IF EXISTS `reactions`;
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reaction_type` enum('like','dislike') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reaction` (`post_id`,`user_id`,`reaction_type`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reactions`
--

INSERT INTO `reactions` (`id`, `post_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(12, 1, 3, 'like', '2026-03-09 14:47:15'),
(22, 17, 6, 'like', '2026-04-27 08:38:03'),
(24, 18, 3, 'like', '2026-04-27 08:39:09'),
(25, 19, 6, 'like', '2026-04-27 09:01:31'),
(26, 18, 6, 'like', '2026-04-27 09:01:34'),
(27, 20, 2, 'like', '2026-04-29 06:38:17'),
(28, 19, 2, 'like', '2026-04-29 06:38:20'),
(29, 18, 2, 'like', '2026-04-29 06:38:23');

-- --------------------------------------------------------

--
-- Structure de la table `report`
--

DROP TABLE IF EXISTS `report`;
CREATE TABLE IF NOT EXISTS `report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_user_id` int DEFAULT NULL,
  `reported_user_id` int DEFAULT NULL,
  `post_id` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_status` (`status`),
  KEY `idx_report_created` (`created_at`),
  KEY `idx_report_reporter` (`reporter_user_id`),
  KEY `idx_report_reported` (`reported_user_id`),
  KEY `idx_report_post` (`post_id`),
  KEY `idx_report_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/user_icon.png',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `followers_count` int UNSIGNED NOT NULL DEFAULT '0',
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('online','away','busy','offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'offline',
  `account_type` enum('user','moderator','administrator') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `google_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` enum('local','google','github') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'local',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_google` (`google_id`),
  UNIQUE KEY `unique_github` (`github_id`),
  KEY `idx_users_admin` (`admin`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `phone`, `age`, `birthdate`, `profile_picture`, `admin`, `followers_count`, `bio`, `status`, `account_type`, `is_verified`, `created_at`, `last_login`, `google_id`, `github_id`, `provider`) VALUES
(2, 'Malenia', 'malenia@eldenring.fs', '$2y$10$d3IRYdadjRdYgiRBXOnMI.l15tfN7qVBxX7j.c151h/42vUZob3VS', '01 65 45 32 52', 400, '1625-08-07', 'assets/user_icon.png', 0, 3, NULL, 'offline', 'user', 0, '2026-03-09 10:17:29', '2026-04-29 06:44:23', NULL, NULL, 'local'),
(3, 'FoxyTheBigYT', 'enzoingelaere@gmail.com', '$2y$10$j8Bcs/t7lShM7adM9yG7vupXnIYf00ELQ/npO66m4Oujcq16fWkKO', '06 67 30 65 59', 19, '2006-10-23', 'assets/user_icon.png', 0, 3, 'Foxy ! fan de renard', 'offline', 'user', 0, '2026-03-09 10:25:56', '2026-04-27 08:45:12', NULL, NULL, 'local'),
(5, 'testeur', 'testeur@gmail.com', '$2y$10$kleAbAmP4pMTYUV/jW/aze9zep8ZId.bJuhtH9vMBLBoKwz7Km9.q', '', 13, NULL, 'assets/user_icon.png', 0, 1, NULL, 'offline', 'user', 0, '2026-04-25 10:11:45', '2026-04-25 12:32:26', NULL, NULL, 'local'),
(6, 'JohnDoe', 'johndoe@gmail.com', '$2y$10$YSlLdsS5UbMnHojUV8Rb8udEmBWEUrvNnmypr8Ch1kZDh0vFT3L9i', '06 06 06 06 06', 20, NULL, 'assets/user_icon.png', 0, 0, NULL, 'offline', 'user', 0, '2026-04-27 08:37:38', NULL, NULL, NULL, 'local');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `comment_mentions`
--
ALTER TABLE `comment_mentions`
  ADD CONSTRAINT `fk_commentmentions_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commentmentions_user` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `fk_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `post_mentions`
--
ALTER TABLE `post_mentions`
  ADD CONSTRAINT `fk_postmentions_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_postmentions_user` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `fk_reactions_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_report_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_report_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
