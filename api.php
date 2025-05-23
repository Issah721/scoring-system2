<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // $pdo object will be available
header('Content-Type: application/json');

// --- Utility Functions ---
function check_session($allowed_roles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "Authentication required."]);
        exit;
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403); // Forbidden
        echo json_encode(["error" => "Access denied for your role."]);
        exit;
    }
    // Return user data from session for convenience if needed later
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'display_name' => $_SESSION['display_name']
    ];
}

// Determine action
$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- Authentication Endpoints ---
        if ($action === 'login') {
            if (empty($_POST['username']) || empty($_POST['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username and password are required.']);
                exit;
            }
            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $pdo->prepare("SELECT user_id, username, password, role, display_name FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['display_name'] = $user['display_name'];

                http_response_code(200);
                echo json_encode([
                    "success" => true, 
                    "message" => "Login successful", 
                    "user_id" => $user['user_id'],
                    "username" => $user['username'],
                    "role" => $user['role'],
                    "display_name" => $user['display_name']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
            }
        } elseif ($action === 'logout') {
            session_unset();
            session_destroy();
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Logout successful']);
        
        // --- User Management Endpoints (Admin Only) ---
        } elseif ($action === 'add_user') {
            check_session(['admin']);
            if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['role']) || empty($_POST['display_name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username, password, role, and display name are required.']);
                exit;
            }
            if (!in_array($_POST['role'], ['admin', 'judge'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid role. Must be "admin" or "judge".']);
                exit;
            }

            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            $display_name = $_POST['display_name'];

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => 'Username already exists']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, display_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role, $display_name]);
            $user_id = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'User added successfully', 'user_id' => $user_id]);

        } elseif ($action === 'update_user') {
            check_session(['admin']);
            if (empty($_POST['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID is required.']);
                exit;
            }
            $user_id = $_POST['user_id'];
            
            // Fetch existing user to check if it exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $existing_user = $stmt->fetch();
            if (!$existing_user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found.']);
                exit;
            }

            $username = $_POST['username'] ?? $existing_user['username'];
            $display_name = $_POST['display_name'] ?? $existing_user['display_name'];
            $role = $_POST['role'] ?? $existing_user['role'];
            
            if (isset($_POST['role']) && !in_array($_POST['role'], ['admin', 'judge'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid role. Must be "admin" or "judge".']);
                exit;
            }

            // Check for username conflict if username is being changed
            if ($username !== $existing_user['username']) {
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $stmt->execute([$username, $user_id]);
                if ($stmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'error' => 'Username already exists']);
                    exit;
                }
            }
            
            $password_sql_part = "";
            $params = [$username, $display_name, $role];
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_sql_part = ", password = ?";
                $params[] = $password;
            }
            $params[] = $user_id;

            $sql = "UPDATE users SET username = ?, display_name = ?, role = ? $password_sql_part WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);

        } elseif ($action === 'delete_user') {
            check_session(['admin']);
            if (empty($_POST['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID is required.']);
                exit;
            }
            $user_id = $_POST['user_id'];

            // Prevent admin from deleting themselves (optional safeguard)
            if ($user_id == $_SESSION['user_id']) {
                 http_response_code(403);
                 echo json_encode(['success' => false, 'error' => 'Admin users cannot delete their own account.']);
                 exit;
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                http_response_code(404); // Or 200 if you prefer to not indicate existence
                echo json_encode(['success' => false, 'error' => 'User not found or already deleted.']);
            }
        }
        // --- OLD Endpoints that need refactoring or removal for this subtask ---
        // The following were part of the old API structure and are being replaced or deferred
        // For example, 'add_judge' is replaced by 'add_user' with role 'judge'.
        // 'get_judges' will be replaced by 'get_users' with role 'judge'.
        // Scoring related endpoints ('add_score', 'get_users_not_scored', 'get_scoreboard') will be handled in a later task.
        else {
             // Catch-all for POST actions not defined above in the new structure
            http_response_code(404);
            echo json_encode(['error' => 'POST endpoint not found or not yet refactored.']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_users') {
            check_session(['admin']); // Only admin can get full user list
            
            $sql = "SELECT user_id, username, role, display_name FROM users";
            $params = [];
            if (!empty($_GET['role'])) {
                if (in_array($_GET['role'], ['admin', 'judge'])) {
                    $sql .= " WHERE role = ?";
                    $params[] = $_GET['role'];
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid role filter.']);
                    exit;
                }
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($users);
        }
        } elseif ($action === 'get_users_not_scored') {
            $current_user = check_session(['judge']); // Ensures only logged-in judges can access
            $judge_user_id = $current_user['user_id']; // Get judge_id from session

            $stmt = $pdo->prepare("
                SELECT p.participant_id, p.username, p.display_name 
                FROM participants p
                LEFT JOIN scores s ON p.participant_id = s.participant_id AND s.judge_user_id = :judge_user_id
                WHERE s.score_id IS NULL
            ");
            $stmt->bindParam(':judge_user_id', $judge_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $participants = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($participants);

        } elseif ($action === 'get_scoreboard') {
            // Publicly accessible
            $stmt = $pdo->query("
                SELECT p.participant_id, p.display_name, SUM(s.points) AS total_points
                FROM participants p
                JOIN scores s ON p.participant_id = s.participant_id
                GROUP BY p.participant_id, p.display_name
                ORDER BY total_points DESC
            ");
            $scoreboard = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode($scoreboard);

        } else {
            // Catch-all for GET actions not defined above
            http_response_code(404);
            echo json_encode(['error' => 'GET endpoint not found.']);
        }
    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    error_log("PDOException: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(400); // Bad Request for other general exceptions
    echo json_encode(['error' => $e->getMessage()]);
}
?>
