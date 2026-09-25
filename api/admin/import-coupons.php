<?php
/**
 * Admin API: CSV Import Coupons
 * POST /api/admin/import-coupons.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
requireCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'error' => 'Please select a valid CSV file to upload.'], 400);
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
$handle = fopen($tmpPath, 'r');

if (!$handle) {
    jsonResponse(['success' => false, 'error' => 'Failed to read uploaded CSV file.'], 500);
}

$db = getDbConnection();

$imported = 0;
$skipped = 0;
$maxCoupon = 0;
$rowNum = 0;

$stmt = $db->prepare("
    INSERT INTO coupons (coupon_number, customer_name, mobile, address, city, state, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'eligible') 
    ON DUPLICATE KEY UPDATE 
    customer_name = VALUES(customer_name),
    mobile = VALUES(mobile),
    address = VALUES(address),
    city = VALUES(city),
    state = VALUES(state)
");

try {
    $db->beginTransaction();

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;
        
        // Skip header if present
        if ($rowNum === 1 && (strtolower(trim($data[0])) === 'coupon_number' || strtolower(trim($data[0])) === 'coupon')) {
            continue;
        }

        if (count($data) < 2) {
            $skipped++;
            continue;
        }

        $couponNum = (int)trim($data[0]);
        $name = sanitizeInput($data[1] ?? '');
        $mobile = sanitizeInput($data[2] ?? '');
        $address = sanitizeInput($data[3] ?? '');
        $city = sanitizeInput($data[4] ?? '');
        $state = sanitizeInput($data[5] ?? '');

        if ($couponNum <= 0 || empty($name)) {
            $skipped++;
            continue;
        }

        $stmt->execute([$couponNum, $name, $mobile, $address, $city, $state]);
        $imported++;

        if ($couponNum > $maxCoupon) {
            $maxCoupon = $couponNum;
        }
    }

    fclose($handle);

    // Update max coupon setting if imported coupons exceed existing max
    if ($maxCoupon > 0) {
        $maxSetting = (int)getSetting('max_coupon_number', '357');
        if ($maxCoupon > $maxSetting) {
            $upd = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'max_coupon_number'");
            $upd->execute([$maxCoupon]);
        }
    }

    $db->commit();
    logAdminAction($_SESSION['admin_username'], 'IMPORT_COUPONS', "Imported {$imported} coupons from CSV, skipped {$skipped}");

    jsonResponse([
        'success' => true,
        'message' => "Successfully imported/updated {$imported} coupons. Skipped {$skipped} invalid rows.",
        'imported_count' => $imported,
        'skipped_count' => $skipped
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Error processing CSV import: ' . $e->getMessage()], 500);
}
