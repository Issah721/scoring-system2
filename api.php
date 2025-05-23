<?php
require_once 'db.php'; // $pdo object will be available

header('Content-Type: application/json');

// Determine action
$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add_judge') {
            if (empty($_POST['username']) || empty($_POST['display_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username and display name are required.']);
                exit;
            }
            $username = $_POST['username'];
            $display_name = $_POST['display_name'];

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT judge_id FROM judges WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already exists']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO judges (username, display_name) VALUES (?, ?)");
            $stmt->execute([$username, $display_name]);
            $judge_id = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                "message" => "Judge added successfully",
                "judge" => ["judge_id" => $judge_id, "username" => $username, "display_name" => $display_name]
            ]);

        } elseif ($action === 'add_score') {
            if (!isset($_POST['judge_id']) || !isset($_POST['user_id']) || !isset($_POST['points'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Judge ID, User ID, and Points are required.']);
                exit;
            }
            $judge_id = $_POST['judge_id'];
            $user_id = $_POST['user_id'];
            $points = $_POST['points'];

            // Validate inputs
            if (!filter_var($judge_id, FILTER_VALIDATE_INT) || !filter_var($user_id, FILTER_VALIDATE_INT) || !filter_var($points, FILTER_VALIDATE_INT)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input types. IDs and points must be integers.']);
                exit;
            }
            $points = (int)$points;
            if ($points < 1 || $points > 100) {
                http_response_code(400);
                echo json_encode(['error' => 'Points must be an integer between 1 and 100.']);
                exit;
            }

            // Check if judge exists
            $stmt = $pdo->prepare("SELECT judge_id FROM judges WHERE judge_id = ?");
            $stmt->execute([$judge_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Judge not found.']);
                exit;
            }

            // Check if user exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'User not found.']);
                exit;
            }

            // Check if score already exists
            $stmt = $pdo->prepare("SELECT score_id FROM scores WHERE user_id = ? AND judge_id = ?");
            $stmt->execute([$user_id, $judge_id]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Score already submitted for this user by this judge']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO scores (user_id, judge_id, points) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $judge_id, $points]);

            http_response_code(201);
            echo json_encode(["message" => "Score submitted successfully"]);

        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_judges') {
            $stmt = $pdo->query("SELECT judge_id, username, display_name FROM judges");
            $judges = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($judges);

        } elseif ($action === 'get_users_not_scored') {
            if (!isset($_GET['judge_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Judge ID is required.']);
                exit;
            }
            $judge_id = $_GET['judge_id'];
            if (!filter_var($judge_id, FILTER_VALIDATE_INT)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Judge ID. Must be an integer.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.display_name 
                                   FROM users u
                                   LEFT JOIN scores s ON u.user_id = s.user_id AND s.judge_id = ?
                                   WHERE s.score_id IS NULL");
            $stmt->execute([$judge_id]);
            $users = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($users);

        } elseif ($action === 'get_scoreboard') {
            $stmt = $pdo->query("SELECT u.user_id, u.display_name, SUM(s.points) as total_points
                                 FROM users u
                                 JOIN scores s ON u.user_id = s.user_id
                                 GROUP BY u.user_id, u.display_name
                                 ORDER BY total_points DESC");
            $scoreboard = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($scoreboard);

        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    error_log("PDOException: " . $e->getMessage()); // Log the detailed error
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage()); // Log the detailed error
    http_response_code(400); // Bad Request for other exceptions
    echo json_encode(['error' => $e->getMessage()]);
}

?>
