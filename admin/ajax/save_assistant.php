<?php
/**
 * admin/ajax/save_assistant.php
 * Handles Add and Edit for assistants
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$assistant_id = isset($_POST['assistant_id']) ? intval($_POST['assistant_id']) : 0;
$full_name    = trim($_POST['full_name'] ?? '');
$username     = trim($_POST['username'] ?? '');
$password     = trim($_POST['password'] ?? '');
$status       = isset($_POST['status']) ? intval($_POST['status']) : 1;

if ($full_name === '' || $username === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and username are required.']);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.']);
    exit();
}

try {
    if ($assistant_id > 0) {
        // ===== UPDATE =====
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $assistant_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        if ($password !== '') {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                exit();
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = :full_name, username = :username, password = :password, status = :status WHERE id = :id AND role = 'assistant'");
            $stmt->bindParam(':password', $hashed);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = :full_name, username = :username, status = :status WHERE id = :id AND role = 'assistant'");
        }

        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $assistant_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Assistant updated successfully.']);

    } else {
        // ===== INSERT =====
        if ($password === '' || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password is required (minimum 6 characters).']);
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role   = 'assistant';

        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, status) VALUES (:full_name, :username, :password, :role, :status)");
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Assistant added successfully.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}