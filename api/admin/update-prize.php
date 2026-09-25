<?php
/**
 * Admin API: Update Existing Prize
 * POST /api/admin/update-prize.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
requireCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = sanitizeInput($_POST['action'] ?? 'update');

if (!$id || $id <= 0) {
    jsonResponse(['success' => false, 'error' => 'Valid Prize ID is required.'], 400);
}

$db = getDbConnection();

try {
    if ($action === 'delete') {
        // Check if prize has assignment or winners
        $check = $db->prepare("SELECT COUNT(*) as count FROM winners WHERE prize_id = ?");
        $check->execute([$id]);
        $row = $check->fetch();
        if ($row && (int)$row['count'] > 0) {
            jsonResponse(['success' => false, 'error' => 'Cannot delete prize that already has recorded winners. You can set status to inactive instead.'], 400);
        }

        $stmt = $db->prepare("DELETE FROM prizes WHERE id = ?");
        $stmt->execute([$id]);
        logAdminAction($_SESSION['admin_username'], 'DELETE_PRIZE', "Deleted Prize ID {$id}");
        jsonResponse(['success' => true, 'message' => 'Prize deleted successfully.']);

    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? 'regular');
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $remainingQuantity = filter_input(INPUT_POST, 'remaining_quantity', FILTER_VALIDATE_INT);
        $description = sanitizeInput($_POST['description'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'active');

        if (empty($name) || !$quantity || $quantity < 0) {
            jsonResponse(['success' => false, 'error' => 'Prize Name and valid Quantity are required.'], 400);
        }

        if ($remainingQuantity === false || $remainingQuantity < 0) {
            $remainingQuantity = $quantity;
        }

        $stmt = $db->prepare("
            UPDATE prizes 
            SET name = ?, type = ?, quantity = ?, remaining_quantity = ?, description = ?, status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $type, $quantity, $remainingQuantity, $description, $status, $id]);

        logAdminAction($_SESSION['admin_username'], 'UPDATE_PRIZE', "Updated Prize ID {$id}: {$name} (Remaining: {$remainingQuantity}/{$quantity})");
        jsonResponse(['success' => true, 'message' => "Prize '{$name}' updated successfully."]);
    }

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
