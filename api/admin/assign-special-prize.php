<?php
/**
 * Admin API: Assign Special Prize Winner (Car, Scooty, etc.)
 * POST /api/admin/assign-special-prize.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
requireCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$prizeId = filter_input(INPUT_POST, 'prize_id', FILTER_VALIDATE_INT);
$couponNumber = filter_input(INPUT_POST, 'coupon_number', FILTER_VALIDATE_INT);
$action = sanitizeInput($_POST['action'] ?? 'assign');

if (!$prizeId || $prizeId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Please select a valid Special Prize.'], 400);
}

$db = getDbConnection();

try {
    if ($action === 'remove') {
        $stmt = $db->prepare("DELETE FROM special_prize_assignments WHERE prize_id = ?");
        $stmt->execute([$prizeId]);
        logAdminAction($_SESSION['admin_username'], 'REMOVE_SPECIAL_ASSIGNMENT', "Removed special prize assignment for Prize ID {$prizeId}");
        jsonResponse(['success' => true, 'message' => 'Special prize assignment removed successfully.']);
    }

    if (!$couponNumber || $couponNumber <= 0) {
        jsonResponse(['success' => false, 'error' => 'Please enter a valid Coupon Number.'], 400);
    }

    // 1. Check Prize existence & type
    $prizeStmt = $db->prepare("SELECT * FROM prizes WHERE id = ? AND type = 'special' LIMIT 1");
    $prizeStmt->execute([$prizeId]);
    $prize = $prizeStmt->fetch();

    if (!$prize) {
        jsonResponse(['success' => false, 'error' => 'Selected prize is not a valid Special Prize.'], 400);
    }

    // 2. Check Coupon existence & eligibility
    $couponStmt = $db->prepare("SELECT * FROM coupons WHERE coupon_number = ? LIMIT 1");
    $couponStmt->execute([$couponNumber]);
    $coupon = $couponStmt->fetch();

    if (!$coupon) {
        jsonResponse(['success' => false, 'error' => "Coupon #{$couponNumber} does not exist."], 400);
    }

    if ($coupon['status'] === 'ineligible') {
        jsonResponse(['success' => false, 'error' => "Coupon #{$couponNumber} is currently marked as ineligible."], 400);
    }

    // 3. Check if coupon has already won
    $winnerStmt = $db->prepare("SELECT * FROM winners WHERE coupon_number = ? LIMIT 1");
    $winnerStmt->execute([$couponNumber]);
    if ($winnerStmt->fetch()) {
        jsonResponse(['success' => false, 'error' => "Coupon #{$couponNumber} has already won a prize and cannot be assigned a special prize."], 400);
    }

    // 4. Check if coupon is assigned to ANOTHER special prize
    $otherCheck = $db->prepare("SELECT spa.*, p.name as prize_name FROM special_prize_assignments spa JOIN prizes p ON spa.prize_id = p.id WHERE spa.coupon_number = ? AND spa.prize_id != ? LIMIT 1");
    $otherCheck->execute([$couponNumber, $prizeId]);
    $otherAssign = $otherCheck->fetch();

    if ($otherAssign) {
        jsonResponse(['success' => false, 'error' => "Coupon #{$couponNumber} is already assigned to special prize: " . $otherAssign['prize_name']], 400);
    }

    // 5. Insert or Update Assignment
    $db->beginTransaction();

    // Remove old assignment for this prize if any
    $db->prepare("DELETE FROM special_prize_assignments WHERE prize_id = ?")->execute([$prizeId]);

    // Insert new assignment
    $assignStmt = $db->prepare("
        INSERT INTO special_prize_assignments (prize_id, coupon_id, coupon_number) 
        VALUES (?, ?, ?)
    ");
    $assignStmt->execute([$prizeId, $coupon['id'], $coupon['coupon_number']]);

    $db->commit();

    logAdminAction($_SESSION['admin_username'], 'ASSIGN_SPECIAL_PRIZE', "Assigned Special Prize '{$prize['name']}' to Coupon #{$couponNumber} ({$coupon['customer_name']})");

    jsonResponse([
        'success' => true,
        'message' => "Special prize '{$prize['name']}' successfully assigned to Coupon #{$couponNumber} ({$coupon['customer_name']}).",
        'assignment' => [
            'prize_id' => $prizeId,
            'prize_name' => $prize['name'],
            'coupon_number' => $coupon['coupon_number'],
            'customer_name' => $coupon['customer_name']
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
