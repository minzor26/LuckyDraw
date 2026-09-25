<?php
/**
 * Admin API: Add or Edit Single Coupon
 * POST /api/admin/add-coupon.php
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
$couponNumber = filter_input(INPUT_POST, 'coupon_number', FILTER_VALIDATE_INT);
$customerName = sanitizeInput($_POST['customer_name'] ?? '');
$mobile = sanitizeInput($_POST['mobile'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$city = sanitizeInput($_POST['city'] ?? '');
$state = sanitizeInput($_POST['state'] ?? '');
$status = sanitizeInput($_POST['status'] ?? 'eligible');

$validStatuses = ['eligible', 'ineligible', 'winner', 'used'];
if (!in_array($status, $validStatuses)) {
    $status = 'eligible';
}

if (!$couponNumber || $couponNumber <= 0 || empty($customerName) || empty($mobile)) {
    jsonResponse(['success' => false, 'error' => 'Coupon Number, Customer Name, and Mobile Number are required.'], 400);
}

$db = getDbConnection();

try {
    if ($id && $id > 0) {
        // Edit Existing Coupon
        $stmt = $db->prepare("
            UPDATE coupons 
            SET coupon_number = ?, customer_name = ?, mobile = ?, address = ?, city = ?, state = ?, status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$couponNumber, $customerName, $mobile, $address, $city, $state, $status, $id]);
        logAdminAction($_SESSION['admin_username'], 'UPDATE_COUPON', "Updated Coupon #{$couponNumber} for {$customerName}");
        jsonResponse(['success' => true, 'message' => "Coupon #{$couponNumber} updated successfully."]);
    } else {
        // Create New Coupon
        // Check uniqueness
        $check = $db->prepare("SELECT id FROM coupons WHERE coupon_number = ? LIMIT 1");
        $check->execute([$couponNumber]);
        if ($check->fetch()) {
            jsonResponse(['success' => false, 'error' => "Coupon #{$couponNumber} already exists in the database."], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO coupons (coupon_number, customer_name, mobile, address, city, state, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$couponNumber, $customerName, $mobile, $address, $city, $state, $status]);
        
        // Update max coupon setting if this is larger
        $maxSetting = getSetting('max_coupon_number', '357');
        if ($couponNumber > (int)$maxSetting) {
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'max_coupon_number'")->execute([$couponNumber]);
        }

        logAdminAction($_SESSION['admin_username'], 'ADD_COUPON', "Added Coupon #{$couponNumber} for {$customerName}");
        jsonResponse(['success' => true, 'message' => "Coupon #{$couponNumber} added successfully."]);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
