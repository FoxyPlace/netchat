-- Ajouter le champ bio à la table users
ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL DEFAULT NULL AFTER `profile_picture`;
