CREATE DATABASE IF NOT EXISTS scoring_system;

USE scoring_system;

-- Drop tables in reverse order of dependency, ensuring all V2 tables are gone
DROP TABLE IF EXISTS `scores`;
DROP TABLE IF EXISTS `participants`; -- V2 specific
DROP TABLE IF EXISTS `users`; -- This will be recreated as V1 participants table
DROP TABLE IF EXISTS `judges`; -- This will be recreated as V1 judges table

-- V1 Schema:

CREATE TABLE IF NOT EXISTS `judges` (
  `judge_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `users` ( -- This is the PARTICIPANTS table in V1
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(255) NOT NULL
);

INSERT INTO `users` (`username`, `display_name`) VALUES
('user1', 'Participant One'),
('user2', 'Participant Two'),
('user3', 'Participant Three');

CREATE TABLE IF NOT EXISTS `scores` (
  `score_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL, -- Foreign key to users (participants)
  `judge_id` INT NOT NULL, -- Foreign key to judges
  `points` INT NOT NULL CHECK (points >= 1 AND points <= 100),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_judge` (`user_id`, `judge_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`judge_id`) REFERENCES `judges`(`judge_id`) ON DELETE CASCADE
);
