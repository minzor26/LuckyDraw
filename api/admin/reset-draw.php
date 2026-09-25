<?php
/**
 * Admin API: Reset Draw Data (Test Mode vs Production Reset)
 * POST /api/admin/reset-draw.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
requireCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$mode = sanitizeInput($_POST['mode'] ?? 'test'); // 'test' or 'production'
$confirmCode = sanitizeInput($_POST['confirm_code'] ?? '');

$db = getDbConnection();

try {
    if ($mode === 'test') {
        // Remove test winners only
        $stmt = $db->prepare("DELETE FROM winners WHERE is_test = 1");
        $stmt->execute();
        $count = $stmt->rowCount();

        logAdminAction($_SESSION['admin_username'], 'RESET_TEST_DRAW', "Cleared {$count} test winner records");
        jsonResponse(['success' => true, 'message' => "Successfully cleared {$count} test draw records."]);

    } elseif ($mode === 'production') {
        if ($confirmCode !== 'RESET_PRODUCTION_CONFIRM') {
            jsonResponse(['success' => false, 'error' => 'Invalid confirmation code. Type RESET_PRODUCTION_CONFIRM to verify.'], 400);
        }

        $db->beginTransaction();

        // 1. Delete all winners
        $db->exec("DELETE FROM winners");

        // 2. Restore all prize remaining quantities back to total quantity
        $db->exec("UPDATE prizes SET remaining_quantity = quantity");

        // 3. Reset coupon statuses from 'winner' / 'used' back to 'eligible'
        $db->exec("UPDATE coupons SET status = 'eligible' WHERE status IN ('winner', 'used')");

        $db->commit();

        logAdminAction($_SESSION['admin_username'], 'RESET_PRODUCTION_DRAW', 'FULL PRODUCTION DRAW RESET PERFORMED');
        jsonResponse([
            'success' => true,
            'message' => 'PRODUCTION DRAW HAS BEEN RESET! All winner records cleared, coupon statuses reset to eligible, and prize inventories restored.'
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Invalid reset mode.'], 400);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Database error executing reset: ' . $e->getMessage()], 500);
}
