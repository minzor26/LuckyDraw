<?php
/**
 * API: Authoritative Wheel Spin Endpoint
 * POST /api/spin.php
 * Payload: { "coupon_number": 357 }
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

// Read raw JSON or POST data
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

if (!$inputData) {
    $inputData = $_POST;
}

$couponNumber = isset($inputData['coupon_number']) ? (int)$inputData['coupon_number'] : 0;

if ($couponNumber <= 0) {
    jsonResponse(['success' => false, 'error' => 'Valid Coupon Number is required to spin.'], 400);
}

// Execute Authoritative Backend Selection Logic
$result = executeSpinDraw($couponNumber);

if (!$result['success']) {
    jsonResponse($result, 400);
}

jsonResponse($result, 200);
