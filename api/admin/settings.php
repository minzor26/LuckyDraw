<?php
/**
 * Admin API: System Settings (GET/POST)
 * GET/POST /api/admin/settings.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();

$db = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = getSystemSettings();
    jsonResponse(['success' => true, 'settings' => $settings]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $updatableKeys = [
        'shop_name',
        'campaign_name',
        'subtitle',
        'max_coupon_number',
        'enable_draw',
        'enable_sound',
        'test_mode',
        'show_customer_details'
    ];

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($updatableKeys as $key) {
            if (isset($_POST[$key])) {
                $val = sanitizeInput($_POST[$key]);
                $stmt->execute([$key, $val]);
            }
        }

        $db->commit();
        logAdminAction($_SESSION['admin_username'], 'UPDATE_SETTINGS', 'Updated system campaign settings');

        jsonResponse(['success' => true, 'message' => 'Settings updated successfully.', 'settings' => getSystemSettings()]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}
