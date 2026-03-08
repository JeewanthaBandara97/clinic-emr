<?php
/**
 * AJAX: Get Sub Categories
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Medicine.php';

if (!User::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

if ($parentId <= 0) {
    echo json_encode([]);
    exit;
}

$medicine = new Medicine();
$subCategories = $medicine->getSubCategories($parentId);

echo json_encode($subCategories);