<?php
/**
 * API: Get Recent Public Winners List
 * GET /api/winners.php?limit=20
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
$limit = min(100, max(1, $limit));

$db = getDbConnection();
$stmt = $db->prepare("
    SELECT coupon_number, customer_name, mobile, prize_name, prize_type, won_at 
    FROM winners 
    ORDER BY won_at DESC 
    LIMIT :limit
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$winners = $stmt->fetchAll();

$publicWinners = array_map(function($w) {
    return [
        'coupon_number' => (int)$w['coupon_number'],
        'customer_name' => $w['customer_name'],
        'mobile_masked' => maskMobileNumber($w['mobile']),
        'prize_name' => $w['prize_name'],
        'prize_type' => $w['prize_type'],
        'won_at' => $w['won_at'],
        'formatted_date' => date('d M Y, h:i A', strtotime($w['won_at']))
    ];
}, $winners);

jsonResponse(['success' => true, 'winners' => $publicWinners]);
