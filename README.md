# ScoringSystem

## Overview
A simple LAMP stack based scoring system for multiple judges and participants. This system now features user authentication with roles (admin, judge), allowing administrators to manage users (other admins and judges), and judges to score registered participants. It displays a real-time scoreboard.

## Features
*   **Secure Login System**: Admins and Judges can log in using their credentials.
*   **User Management (Admin Panel)**: Admins can add, edit, and delete other admin and judge users.
*   **Role-Based Access Control**:
    *   Admins have access to user management.
    *   Judges have access to scoring participants.
*   **Judge Portal**: Logged-in judges can submit scores for participants.
*   **Dynamic Scoreboard**: Displays real-time scores, auto-refreshing every 10 seconds.
*   **Navigation Bar**: Consistent navigation across all pages, adapting to user's login status and role.
*   **Session Management**: PHP sessions manage user login state. Judges score under their own authenticated identity.
*   **Glassmorphism UI**: Modern dark theme with glass-like card elements.
*   **Responsive Design**: Basic responsiveness for usability on smaller screens.

## Technology Stack
*   **L**inux (Assumed, typically part of XAMPP/LAMP stack)
*   **A**pache (Typically via XAMPP or a standard LAMP setup)
*   **M**ySQL (Typically via XAMPP or a standard LAMP setup)
*   **P**HP (For backend logic, API, and session management)
*   HTML, CSS, JavaScript (For frontend structure, styling, and interactivity)

## Setup Instructions (for XAMPP)
1.  **Download and Install XAMPP**: Get XAMPP from [Apache Friends](https://www.apachefriends.org/index.html) and install it.
2.  **Start Modules**: Launch the XAMPP Control Panel and start the Apache and MySQL modules.
3.  **Clone Repository**: Clone this repository into the `htdocs` directory of your XAMPP installation.
    *   Example paths: `C:\xampp\htdocs\scoring-system` (Windows) or `/opt/lampp/htdocs/scoring-system` (Linux).
4.  **Import Database**:
    *   Open phpMyAdmin by navigating to `http://localhost/phpmyadmin` in your web browser.
    *   Create a new database named `scoring_system` (if it's not automatically created by the SQL script).
    *   Select the `scoring_system` database.
    *   Click on the "Import" tab.
    *   Choose the `setup.sql` file from the cloned repository and click "Go". This will create the necessary tables:
        *   `users`: For admin and judge authentication (stores usernames, hashed passwords, roles).
        *   `participants`: For individuals/teams being scored.
        *   `scores`: For storing scores.
        *   It also inserts initial demo users and participants.
5.  **Access Application**:
    *   **Login Page**: `http://localhost/scoring-system/login.php` (Start here!)
    *   **Admin Panel**: `http://localhost/scoring-system/admin.php` (Requires admin login)
    *   **Judge Portal**: `http://localhost/scoring-system/judge.php` (Requires judge login)
    *   **Scoreboard**: `http://localhost/scoring-system/scoreboard.php` (Publicly accessible)
6.  **Demo Credentials**:
    *   **Admin**:
        *   Username: `admin1`
        *   Password: `adminpass123`
    *   **Judge**:
        *   Username: `judge1`
        *   Password: `judgepass123`

## Database Schema
The following SQL script (`setup.sql`) is used to create the database and tables:

```sql
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
```

**Table Descriptions:**
*   `users`: Stores authentication credentials (username, hashed password) and role (`admin`, `judge`) for system users. Also includes a display name.
*   `participants`: Stores information about the participants/entities being scored (e.g., contestants, teams).
*   `scores`: Records the scores given by judges (from `users` table) to participants (from `participants` table). Ensures a participant can only be scored once by the same judge and that points are between 1 and 100. Foreign keys link to `participants` and `users` tables.

## API Endpoints
The `api.php` file provides the backend logic. All responses are in JSON. Session-based authentication is required for protected endpoints.

**Authentication:**
*   `POST /api.php?action=login`: Authenticates a user (admin/judge) and starts a session.
    *   Expects `username` and `password`.
*   `POST /api.php?action=logout`: Terminates the user session.

**User Management (Admin Role Required):**
*   `POST /api.php?action=add_user`: Adds a new user (admin or judge).
    *   Expects `username`, `password`, `role`, `display_name`.
*   `GET /api.php?action=get_users&role=<role>`: Retrieves a list of users. Can be filtered by `role` (admin/judge).
*   `POST /api.php?action=update_user`: Updates an existing user's details.
    *   Expects `user_id`, and optionally `username`, `display_name`, `password`, `role`.
*   `POST /api.php?action=delete_user`: Deletes a user.
    *   Expects `user_id`.

**Scoring (Judge Role Required for `get_users_not_scored` and `add_score`):**
*   `GET /api.php?action=get_users_not_scored`: Retrieves participants not yet scored by the logged-in judge (judge ID from session).
*   `POST /api.php?action=add_score`: Submits a score for a participant by the logged-in judge.
    *   Expects `participant_id` and `points` (judge ID from session).

**Public Endpoints:**
*   `GET /api.php?action=get_scoreboard`: Retrieves the current scoreboard data.

**Superseded Endpoints (Old API):**
*   `POST /api.php?action=add_judge`: Replaced by `POST /api.php?action=add_user` with `role='judge'`.
*   `GET /api.php?action=get_judges`: Replaced by `GET /api.php?action=get_users&role=judge`.

## Design Choices
*   **UI Styling**:
    *   **Theme**: A modern dark theme with a primary color (`#00ff88`) for highlights.
    *   **Glassmorphism**: Container elements use a frosted glass effect.
    *   **Responsive Design**: Basic media queries for better usability on smaller screens.
*   **Authentication**:
    *   PHP sessions are used for managing user login state.
    *   Passwords are securely hashed using `password_hash()` and verified with `password_verify()`.
*   **Navigation**: A dynamic navigation bar (`navigation.php`) is included on all pages, showing relevant links based on login status and user role.
*   **LAMP Integration**: Standard PHP for backend, MySQL for database, served via Apache.
*   **Frontend Interactivity**: Vanilla JavaScript with `fetch` API for AJAX calls, dynamic content updates, and form handling.

## Assumptions
*   The application is primarily designed for a local XAMPP environment.
*   User authentication is now implemented for admin and judge roles. Admin and Judge pages require login. The scoreboard remains public.
*   The `root` MySQL user with an empty password is used for database connection in `db.php` (default for XAMPP). Update `db.php` if your MySQL setup differs.

## Future Features
*   **Participant Management**: CRUD operations for participants in the admin panel.
*   **Forgot Password Functionality**: Allow users to reset their passwords.
*   **More Granular Permissions**: Potentially different levels of admin access.
*   **Enhanced UI/UX**: More sophisticated styling, form validation, and user feedback.
*   **Real-time Scoreboard (WebSockets)**: For instant updates instead of polling.
*   **Audit Trails**: Log important actions like score submissions or user modifications.
*   **Input Validation & Sanitization**: Further enhance server-side validation and output encoding for security.

This project serves as a foundational example of a dynamic web application using the LAMP stack with authentication and role-based features.
Ensure your XAMPP MySQL server is running on the default port (3306) and that the `root` user has no password, or update `db.php` accordingly.
```
