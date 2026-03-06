<?php
/**
 * admin/ajax/save_doctor.php
 * Handles Add and Edit for doctors
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Auth guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Sanitize inputs
$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$password  = trim($_POST['password'] ?? '');
$status    = isset($_POST['status']) ? intval($_POST['status']) : 1;

// Validation
if ($full_name === '' || $username === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and username are required.']);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
    exit();
}

// Validate username format (alphanumeric + underscores)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.']);
    exit();
}

try {
    if ($doctor_id > 0) {
        // ===== UPDATE existing doctor =====

        // Check duplicate username (exclude current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $doctor_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        if ($password !== '') {
            // Update WITH new password
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                exit();
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = :full_name, username = :username, password = :password, status = :status WHERE id = :id AND role = 'doctor'");
            $stmt->bindParam(':password', $hashed);
        } else {
            // Update WITHOUT changing password
            $stmt = $conn->prepare("UPDATE users SET full_name = :full_name, username = :username, status = :status WHERE id = :id AND role = 'doctor'");
        }

        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $doctor_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Doctor updated successfully.']);

    } else {
        // ===== INSERT new doctor =====

        if ($password === '' || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password is required (minimum 6 characters).']);
            exit();
        }

        // Check duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit();
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role   = 'doctor';

        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, status) VALUES (:full_name, :username, :password, :role, :status)");
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Doctor added successfully.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}