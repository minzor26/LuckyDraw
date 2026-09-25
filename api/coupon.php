<?php
/**
 * API: Check Coupon Eligibility
 * GET /api/coupon.php?number=357
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$couponNumber = filter_input(INPUT_GET, 'number', FILTER_VALIDATE_INT);

if (!$couponNumber || $couponNumber <= 0) {
    jsonResponse(['success' => false, 'error' => 'Please enter a valid numeric coupon number.'], 400);
}

$db = getDbConnection();

// Fetch coupon details
$stmt = $db->prepare("SELECT * FROM coupons WHERE coupon_number = ? LIMIT 1");
$stmt->execute([$couponNumber]);
$coupon = $stmt->fetch();

// If coupon not in DB but within max campaign coupon limit (e.g. 1 to 357), create on the fly
if (!$coupon) {
    $maxLimit = (int)getSetting('max_coupon_number', '357');
    if ($couponNumber <= $maxLimit) {
        $ins = $db->prepare("INSERT OR IGNORE INTO coupons (coupon_number, customer_name, mobile, address, city, state, status) VALUES (?, ?, ?, ?, ?, ?, 'eligible')");
        $ins->execute([
            $couponNumber, 
            "Customer #" . $couponNumber, 
            "98" . str_pad($couponNumber, 8, "0", STR_PAD_LEFT), 
            "Main Market", 
            "Rewa", 
            "Madhya Pradesh"
        ]);
        
        $stmt->execute([$couponNumber]);
        $coupon = $stmt->fetch();
    }
}

if (!$coupon) {
    jsonResponse([
        'success' => false,
        'error' => "Coupon #{$couponNumber} was not found. Valid coupons are between 1 and " . getSetting('max_coupon_number', '357') . "."
    ], 400);
}

// Check if coupon is marked ineligible
if ($coupon['status'] === 'ineligible') {
    jsonResponse([
        'success' => false,
        'eligible' => false,
        'error' => "Coupon #{$couponNumber} is marked as ineligible for this draw."
    ]);
}

// Check if coupon has already won
$winnerStmt = $db->prepare("SELECT prize_name, won_at FROM winners WHERE coupon_number = ? LIMIT 1");
$winnerStmt->execute([$couponNumber]);
$existingWinner = $winnerStmt->fetch();

$alreadyWon = !empty($existingWinner);

jsonResponse([
    'success' => true,
    'coupon_number' => (int)$coupon['coupon_number'],
    'customer_name' => $coupon['customer_name'],
    'mobile_masked' => maskMobileNumber($coupon['mobile']),
    'city' => $coupon['city'] ?? '',
    'state' => $coupon['state'] ?? '',
    'status' => $coupon['status'],
    'eligible' => ($coupon['status'] === 'eligible' && !$alreadyWon),
    'already_won' => $alreadyWon,
    'winning_prize' => $existingWinner['prize_name'] ?? null,
    'won_at' => $existingWinner['won_at'] ?? null
]);
