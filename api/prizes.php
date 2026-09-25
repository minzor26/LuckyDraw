<?php
/**
 * API: Get Active Public Prizes List
 * GET /api/prizes.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

$db = getDbConnection();
$stmt = $db->query("
    SELECT id, name, type, quantity, remaining_quantity, description, image 
    FROM prizes 
    WHERE status = 'active'
    ORDER BY type DESC, id ASC
");
$prizes = $stmt->fetchAll();

// Hide internal quantity details for special prizes on public endpoints if desired, but keep name/type
$publicPrizes = array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'type' => $p['type'],
        'description' => $p['description'],
        'image' => $p['image'],
        'available' => ((int)$p['remaining_quantity'] > 0)
    ];
}, $prizes);

jsonResponse(['success' => true, 'prizes' => $publicPrizes]);
