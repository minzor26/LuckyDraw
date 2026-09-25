<?php
/**
 * Admin API: Add or Create New Prize
 * POST /api/admin/add-prize.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
requireCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$name = sanitizeInput($_POST['name'] ?? '');
$type = sanitizeInput($_POST['type'] ?? 'regular');
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$description = sanitizeInput($_POST['description'] ?? '');
$status = sanitizeInput($_POST['status'] ?? 'active');

if (empty($name) || !$quantity || $quantity <= 0) {
    jsonResponse(['success' => false, 'error' => 'Prize Name and Quantity (> 0) are required.'], 400);
}

if (!in_array($type, ['special', 'regular'])) {
    $type = 'regular';
}

if (!in_array($status, ['active', 'inactive'])) {
    $status = 'active';
}

$db = getDbConnection();

try {
    $stmt = $db->prepare("
        INSERT INTO prizes (name, type, quantity, remaining_quantity, description, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $type, $quantity, $quantity, $description, $status]);
    $prizeId = $db->lastInsertId();

    logAdminAction($_SESSION['admin_username'], 'ADD_PRIZE', "Added new prize: {$name} ({$type}, Qty: {$quantity})");

    jsonResponse([
        'success' => true,
        'message' => "Prize '{$name}' created successfully.",
        'prize_id' => $prizeId
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
