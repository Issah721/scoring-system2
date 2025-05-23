CREATE DATABASE IF NOT EXISTS scoring_system;
USE scoring_system;

DROP TABLE IF EXISTS `scores`;
DROP TABLE IF EXISTS `judges`; -- Old table, ensure it's dropped
DROP TABLE IF EXISTS `users`; -- Will be recreated as auth table first, then old users content to participants
DROP TABLE IF EXISTS `participants`; -- In case of re-running script

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'judge') NOT NULL,
  `display_name` VARCHAR(100) NOT NULL
);

-- Example Hashes:
-- 'adminpass123' -> $2y$10$DO.eNle82N9JXHyuY9GUrIQh72e32N0xZq0mk7g0i1fLqC
-- 'judgepass123' -> $2y$10$yqWWBqM.A.uU7NnrcQx2p8S8U/CNxVzAGYaRix1v2YgneeS
INSERT INTO `users` (`username`, `password`, `role`, `display_name`) VALUES
('admin1', '$2y$10$DO.eNle82N9JXHyuY9GUrIQh72e32N0xZq0mk7g0i1fLqC', 'admin', 'Admin One'),
('judge1', '$2y$10$yqWWBqM.A.uU7NnrcQx2p8S8U/CNxVzAGYaRix1v2YgneeS', 'judge', 'Judge One');

CREATE TABLE IF NOT EXISTS `participants` (
  `participant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(255) NOT NULL
);

INSERT INTO `participants` (`username`, `display_name`) VALUES
('user1', 'Participant One'),
('user2', 'Participant Two'),
('user3', 'Participant Three');

CREATE TABLE IF NOT EXISTS `scores` (
  `score_id` INT AUTO_INCREMENT PRIMARY KEY,
  `participant_id` INT NOT NULL,
  `judge_user_id` INT NOT NULL,
  `points` INT NOT NULL CHECK (points >= 1 AND points <= 100),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_participant_judge` (`participant_id`, `judge_user_id`),
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`participant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`judge_user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
);
