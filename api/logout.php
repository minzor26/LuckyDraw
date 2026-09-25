<?php
/**
 * Admin API: Authentication Logout
 * GET/POST /api/logout.php
 */

require_once __DIR__ . '/../includes/auth.php';

logoutAdmin();

if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} else {
    header('Location: /admin/index.php');
}
exit;
