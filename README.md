# ScoringSystem

## Overview
A simple LAMP stack based scoring system for multiple judges and users. This system allows administrators to add judges, judges to score registered users, and displays a real-time scoreboard.

## Features
*   Admin panel to add judges.
*   Judge portal for scoring users.
*   Dynamic scoreboard with auto-refresh (every 10 seconds).
*   Glassmorphism UI design with a dark theme.
*   Responsive design for basic usability on smaller screens.

## Technology Stack
*   **L**inux (Assumed, typically part of XAMPP/LAMP stack)
*   **A**pache (Typically via XAMPP or a standard LAMP setup)
*   **M**ySQL (Typically via XAMPP or a standard LAMP setup)
*   **P**HP (For backend logic and API)
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
    *   Choose the `setup.sql` file from the cloned repository and click "Go". This will create the necessary tables and insert initial data.
5.  **Access Application**:
    *   **Admin Panel**: `http://localhost/scoring-system/admin.php` (or the subdirectory you chose)
    *   **Judge Portal**: `http://localhost/scoring-system/judge.php`
    *   **Scoreboard**: `http://localhost/scoring-system/scoreboard.php`

## Database Schema
The following SQL script (`setup.sql`) is used to create the database and tables:

```sql
CREATE DATABASE IF NOT EXISTS scoring_system;

USE scoring_system;

CREATE TABLE IF NOT EXISTS `judges` (
  `judge_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `scores` (
  `score_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `judge_id` INT NOT NULL,
  `points` INT NOT NULL CHECK (points >= 1 AND points <= 100),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_judge` (`user_id`, `judge_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`judge_id`) REFERENCES `judges`(`judge_id`) ON DELETE CASCADE
);

INSERT INTO `users` (`username`, `display_name`) VALUES
('user1', 'Participant One'),
('user2', 'Participant Two'),
('user3', 'Participant Three');
```

**Table Descriptions:**
*   `judges`: Stores information about the judges, including a unique username and a display name.
*   `users`: Stores information about the participants/users being scored, including a unique username and a display name. Includes some initial sample users.
*   `scores`: Records the scores given by judges to users. It ensures a user can only be scored once by the same judge and that points are between 1 and 100. Foreign keys link to `users` and `judges` tables, with cascade delete enabled.

## API Endpoints
The `api.php` file provides the backend logic for the application. All responses are in JSON format.
*   `POST /api.php?action=add_judge`: Adds a new judge.
    *   Expects `username` and `display_name` in the POST body.
*   `GET /api.php?action=get_judges`: Retrieves a list of all judges.
*   `GET /api.php?action=get_users_not_scored&judge_id=X`: Retrieves users not yet scored by the specified `judge_id`.
*   `POST /api.php?action=add_score`: Submits a score for a user by a judge.
    *   Expects `user_id`, `judge_id`, and `points` in the POST body.
*   `GET /api.php?action=get_scoreboard`: Retrieves the current scoreboard data, including total points for each user, ordered by points.

## Design Choices
*   **UI Styling**:
    *   **Theme**: A modern dark theme is used for aesthetics and reduced eye strain.
    *   **Glassmorphism**: Container elements use a frosted glass effect (`rgba` background, `backdrop-filter: blur()`).
    *   **Primary Color**: `#00ff88` (a vibrant green) is used for highlights, buttons, and important elements.
    *   **Responsive Design**: Basic media queries are implemented in `styles.css` to improve layout and usability on smaller screens (e.g., adjusting form element widths, container padding).
*   **LAMP Integration**:
    *   **PHP**: Serves as the backend language, handling API requests (`api.php`), database interactions (`db.php`), and server-side logic.
    *   **MySQL**: Used as the relational database to store judge, user, and score data.
    *   **Apache**: The web server responsible for serving the PHP, HTML, CSS, and JS files.
*   **Frontend Interactivity**:
    *   **JavaScript (Vanilla JS)**: Extensively used for client-side logic.
    *   **AJAX (`fetch` API)**: Employed for asynchronous communication with `api.php` to add judges, submit scores, and load data without full page reloads.
    *   **Dynamic Content Updates**: The judge portal and scoreboard dynamically update content based on API responses.
    *   **Form Handling**: Client-side validation and submission for a smoother user experience.

## Assumptions
*   The application is primarily designed and tested for a local XAMPP environment.
*   No user authentication or authorization is implemented. All pages (`admin.php`, `judge.php`) are publicly accessible.
*   Error handling is basic: messages are typically displayed on the page or logged to the browser console.
*   The `root` MySQL user with an empty password is used for database connection in `db.php`, which is standard for default XAMPP setups.

## Future Features
*   **User Authentication**: Implement a login system for judges and administrators.
*   **Real-time Scoreboard**: Enhance the scoreboard using WebSockets for instant updates instead of polling.
*   **Robust Error Handling**: Implement more comprehensive server-side and client-side error logging and user feedback.
*   **User Management**: Add features for admins to edit or delete users.
*   **Judge Management**: Add features for admins to edit or delete judges.
*   **Scoring Criteria**: Allow for more detailed scoring metrics or different types of scores.
*   **Navigation**: Add a simple navigation bar or links to easily switch between Admin, Judge, and Scoreboard pages.
*   **Input Validation & Sanitization**: Enhance security by adding more robust server-side input validation and output encoding.
*   **Session Management**: Properly manage judge sessions in the judge portal.

This project serves as a foundational example of a dynamic web application using the LAMP stack.
Ensure your XAMPP MySQL server is running on the default port (3306) and that the `root` user has no password, or update `db.php` accordingly.
```
