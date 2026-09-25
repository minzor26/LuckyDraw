<?php
/**
 * Admin API: Authentication Login
 * POST /api/login.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?: $_POST;

$username = trim($inputData['username'] ?? '');
$password = trim($inputData['password'] ?? '');

if (empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'error' => 'Username and Password are required.'], 400);
}

$loginResult = loginAdmin($username, $password);

if (!$loginResult['success']) {
    jsonResponse(['success' => false, 'error' => $loginResult['error']], 401);
}

// Generate new CSRF token for session
$csrfToken = getCsrfToken();

jsonResponse([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ],
    'csrf_token' => $csrfToken,
    'redirect' => '/admin/dashboard.php'
]);
