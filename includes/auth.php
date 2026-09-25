<?php
/**
 * Mobile Gallery Lucky Draw - Authentication Middleware & Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function isLoggedIn() {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isLoggedIn()) {
        $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
                 (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin login required.']);
            exit;
        } else {
            header('Location: /admin/index.php');
            exit;
        }
    }
}

function loginAdmin($username, $password) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];

        logAdminAction($user['username'], 'LOGIN', 'Admin logged in successfully');
        return ['success' => true, 'user' => $user];
    }

    return ['success' => false, 'error' => 'Invalid username or password'];
}

function logoutAdmin() {
    if (isLoggedIn()) {
        logAdminAction($_SESSION['admin_username'] ?? 'admin', 'LOGOUT', 'Admin logged out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
