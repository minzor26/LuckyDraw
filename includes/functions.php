<?php
/**
 * Mobile Gallery Lucky Draw - Core Utilities & Authoritative Draw Logic
 */

require_once __DIR__ . '/../config/database.php';

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Masks mobile number for privacy (e.g., 9876543210 -> 98******10)
 */
function maskMobileNumber($mobile) {
    $clean = preg_replace('/[^0-9]/', '', (string)$mobile);
    $len = strlen($clean);
    if ($len <= 4) {
        return $clean;
    }
    $firstTwo = substr($clean, 0, 2);
    $lastTwo = substr($clean, -2);
    $maskedMiddle = str_repeat('*', max(4, $len - 4));
    return $firstTwo . $maskedMiddle . $lastTwo;
}

function logAdminAction($adminUsername, $action, $details = '') {
    try {
        $db = getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO admin_audit_logs (admin_username, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminUsername, $action, $details, $ip]);
    } catch (Exception $e) {
        // Suppress audit log errors
    }
}

function getSystemSettings() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function getSetting($key, $default = null) {
    $settings = getSystemSettings();
    return $settings[$key] ?? $default;
}

/**
 * Authoritative Backend Draw Engine
 */
function executeSpinDraw($couponNumber) {
    $db = getDbConnection();
    $couponNumber = (int)$couponNumber;

    if ($couponNumber <= 0) {
        return ['success' => false, 'error' => 'Invalid coupon number.'];
    }

    $settings = getSystemSettings();
    $enableDraw = ($settings['enable_draw'] ?? '1') === '1';
    if (!$enableDraw) {
        return ['success' => false, 'error' => 'Lucky Draw is currently paused by the administrator.'];
    }

    $isTestMode = ($settings['test_mode'] ?? '0') === '1';

    try {
        $db->beginTransaction();

        // 1. Fetch coupon details
        $stmt = $db->prepare("SELECT * FROM coupons WHERE coupon_number = ? LIMIT 1");
        $stmt->execute([$couponNumber]);
        $coupon = $stmt->fetch();

        // Auto-create coupon if missing but within campaign max limit (1 to 357)
        if (!$coupon) {
            $maxLimit = (int)($settings['max_coupon_number'] ?? '357');
            if ($couponNumber <= $maxLimit) {
                $ins = $db->prepare("INSERT INTO coupons (coupon_number, customer_name, mobile, address, city, state, status) VALUES (?, ?, ?, ?, ?, ?, 'eligible')");
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
            $db->rollBack();
            return ['success' => false, 'error' => "Coupon #{$couponNumber} does not exist."];
        }

        // 2. Check if coupon is marked ineligible
        if ($coupon['status'] === 'ineligible') {
            $db->rollBack();
            return ['success' => false, 'error' => "Coupon #{$couponNumber} is marked as ineligible for this draw."];
        }

        // 3. Check if coupon has already won
        $winnerCheck = $db->prepare("SELECT * FROM winners WHERE coupon_number = ? LIMIT 1");
        $winnerCheck->execute([$couponNumber]);
        $existingWinner = $winnerCheck->fetch();

        if ($existingWinner && !$isTestMode) {
            $db->rollBack();
            return [
                'success' => false,
                'already_won' => true,
                'error' => "Coupon #{$couponNumber} has already won a prize: " . $existingWinner['prize_name'],
                'winner_details' => [
                    'coupon_number' => $existingWinner['coupon_number'],
                    'customer_name' => $existingWinner['customer_name'],
                    'prize_name' => $existingWinner['prize_name'],
                    'prize_type' => $existingWinner['prize_type'],
                    'won_at' => $existingWinner['won_at']
                ]
            ];
        }

        $selectedPrize = null;
        $prizeType = 'regular';

        // 4. Check SPECIAL PRIZE assignment
        $specialStmt = $db->prepare("
            SELECT p.* 
            FROM special_prize_assignments spa 
            JOIN prizes p ON spa.prize_id = p.id 
            WHERE spa.coupon_number = ? AND p.status = 'active'
            LIMIT 1
        ");
        $specialStmt->execute([$couponNumber]);
        $specialPrize = $specialStmt->fetch();

        if ($specialPrize) {
            if ($specialPrize['remaining_quantity'] > 0 || $isTestMode) {
                $selectedPrize = $specialPrize;
                $prizeType = 'special';
            }
        }

        // 5. If no special prize assigned, pick an available REGULAR prize
        if (!$selectedPrize) {
            $regStmt = $db->query("
                SELECT * FROM prizes 
                WHERE type = 'regular' AND status = 'active' AND remaining_quantity > 0
            ");
            $regularPrizes = $regStmt->fetchAll();

            if (empty($regularPrizes)) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Sorry! All available regular prizes have been claimed.'];
            }

            // Weighted selection based on remaining quantity
            $pool = [];
            foreach ($regularPrizes as $rp) {
                $qty = max(1, (int)$rp['remaining_quantity']);
                for ($k = 0; $k < $qty; $k++) {
                    $pool[] = $rp;
                }
            }
            shuffle($pool);
            $selectedPrize = $pool[array_rand($pool)];
            $prizeType = 'regular';
        }

        if (!$selectedPrize) {
            $db->rollBack();
            return ['success' => false, 'error' => 'No available prize found for this draw.'];
        }

        // 6. Record winning draw & update inventory (if not in test mode)
        if (!$isTestMode) {
            $updatePrize = $db->prepare("UPDATE prizes SET remaining_quantity = remaining_quantity - 1 WHERE id = ? AND remaining_quantity > 0");
            $updatePrize->execute([$selectedPrize['id']]);

            $updateCoupon = $db->prepare("UPDATE coupons SET status = 'winner' WHERE id = ?");
            $updateCoupon->execute([$coupon['id']]);

            $insertWinner = $db->prepare("
                INSERT INTO winners (coupon_id, prize_id, coupon_number, customer_name, mobile, prize_name, prize_type, is_test) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $insertWinner->execute([
                $coupon['id'],
                $selectedPrize['id'],
                $coupon['coupon_number'],
                $coupon['customer_name'],
                $coupon['mobile'],
                $selectedPrize['name'],
                $prizeType
            ]);
        }

        $db->commit();

        return [
            'success' => true,
            'coupon_number' => (int)$coupon['coupon_number'],
            'customer_name' => $coupon['customer_name'],
            'mobile_masked' => maskMobileNumber($coupon['mobile']),
            'prize_id' => (int)$selectedPrize['id'],
            'prize' => $selectedPrize['name'],
            'prize_type' => $prizeType,
            'prize_description' => $selectedPrize['description'] ?? '',
            'prize_image' => $selectedPrize['image'] ?? '',
            'is_test' => $isTestMode,
            'won_at' => date('Y-m-d H:i:s')
        ];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'error' => 'Database error executing draw: ' . $e->getMessage()];
    }
}
