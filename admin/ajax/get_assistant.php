<?php
/**
 * admin/ajax/get_assistant.php
 * Returns assistant data for the edit modal
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assistant ID.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, full_name, username, status FROM users WHERE id = :id AND role = 'assistant' LIMIT 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $assistant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assistant) {
        echo json_encode(['success' => true, 'data' => $assistant]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assistant not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}