<?php
/**
 * Admin Dashboard View
 */
require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Metrics calculations
$totalCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
$eligibleCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status = 'eligible'")->fetchColumn();
$usedCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status IN ('winner', 'used')")->fetchColumn();
$ineligibleCoupons = (int)$db->query("SELECT COUNT(*) FROM coupons WHERE status = 'ineligible'")->fetchColumn();

$totalWinners = (int)$db->query("SELECT COUNT(*) FROM winners")->fetchColumn();
$specialWinners = (int)$db->query("SELECT COUNT(*) FROM winners WHERE prize_type = 'special'")->fetchColumn();
$regularWinners = (int)$db->query("SELECT COUNT(*) FROM winners WHERE prize_type = 'regular'")->fetchColumn();

// Special winner pre-assignments check
$carAssign = $db->query("
    SELECT spa.coupon_number, c.customer_name, w.won_at 
    FROM special_prize_assignments spa 
    JOIN prizes p ON spa.prize_id = p.id 
    JOIN coupons c ON spa.coupon_id = c.id 
    LEFT JOIN winners w ON spa.coupon_number = w.coupon_number
    WHERE p.name LIKE '%Car%' 
    LIMIT 1
")->fetch();

$scootyAssign = $db->query("
    SELECT spa.coupon_number, c.customer_name, w.won_at 
    FROM special_prize_assignments spa 
    JOIN prizes p ON spa.prize_id = p.id 
    JOIN coupons c ON spa.coupon_id = c.id 
    LEFT JOIN winners w ON spa.coupon_number = w.coupon_number
    WHERE p.name LIKE '%Scooty%' 
    LIMIT 1
")->fetch();

// Remaining Prizes List
$prizes = $db->query("SELECT * FROM prizes ORDER BY type DESC, id ASC")->fetchAll();

$isTestMode = ($settings['test_mode'] ?? '0') === '1';
?>

<?php if ($isTestMode): ?>
<div style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #fef08a; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <strong>⚠️ TEST MODE IS CURRENTLY ACTIVE!</strong> Draws will not decrement real inventory or block coupons permanently.
    </div>
    <a href="settings.php" class="btn-action btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;">Change in Settings</a>
</div>
<?php endif; ?>

<!-- Top Metrics -->
<div class="grid-4">
    <div class="stat-card">
        <div class="stat-icon" style="color: var(--primary-gold);">🎟️</div>
        <div>
            <div class="stat-value"><?php echo number_format($totalCoupons); ?></div>
            <div class="stat-label">Total Coupons Registered</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: var(--accent-green);">✅</div>
        <div>
            <div class="stat-value"><?php echo number_format($eligibleCoupons); ?></div>
            <div class="stat-label">Eligible / Remaining Coupons</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: #3b82f6;">🏆</div>
        <div>
            <div class="stat-value"><?php echo number_format($totalWinners); ?></div>
            <div class="stat-label">Total Draw Winners</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: #ec4899;">⭐</div>
        <div>
            <div class="stat-value"><?php echo number_format($specialWinners); ?></div>
            <div class="stat-label">Special Major Winners</div>
        </div>
    </div>
</div>

<!-- Special Winner Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="admin-panel" style="margin-bottom: 0; border-color: rgba(245, 175, 25, 0.4);">
        <div class="panel-header">
            <h3 class="panel-title" style="color: var(--primary-gold);">🚗 Car Pre-Assigned Winner</h3>
            <a href="special-winners.php" class="btn-action btn-secondary" style="font-size: 0.8rem;">Manage</a>
        </div>
        <div style="font-size: 1.1rem; font-weight: 700;">
            <?php if ($carAssign): ?>
                Coupon #<?php echo $carAssign['coupon_number']; ?> - <?php echo htmlspecialchars($carAssign['customer_name']); ?>
                <?php if ($carAssign['won_at']): ?>
                    <span class="badge badge-winner" style="margin-left: 8px;">WON (<?php echo date('d M Y', strtotime($carAssign['won_at'])); ?>)</span>
                <?php else: ?>
                    <span class="badge badge-eligible" style="margin-left: 8px;">Assigned (Waiting Spin)</span>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: var(--text-muted);">Not Assigned</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-panel" style="margin-bottom: 0; border-color: rgba(225, 40, 38, 0.4);">
        <div class="panel-header">
            <h3 class="panel-title" style="color: #ef4444;">🛵 Scooty Pre-Assigned Winner</h3>
            <a href="special-winners.php" class="btn-action btn-secondary" style="font-size: 0.8rem;">Manage</a>
        </div>
        <div style="font-size: 1.1rem; font-weight: 700;">
            <?php if ($scootyAssign): ?>
                Coupon #<?php echo $scootyAssign['coupon_number']; ?> - <?php echo htmlspecialchars($scootyAssign['customer_name']); ?>
                <?php if ($scootyAssign['won_at']): ?>
                    <span class="badge badge-winner" style="margin-left: 8px;">WON (<?php echo date('d M Y', strtotime($scootyAssign['won_at'])); ?>)</span>
                <?php else: ?>
                    <span class="badge badge-eligible" style="margin-left: 8px;">Assigned (Waiting Spin)</span>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: var(--text-muted);">Not Assigned</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Prize Inventory Table -->
<div class="admin-panel">
    <div class="panel-header">
        <h3 class="panel-title">🎁 Prize Inventory & Stock Status</h3>
        <a href="prizes.php" class="btn-action btn-primary">+ Manage Prizes</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Prize Name</th>
                <th>Type</th>
                <th>Total Qty</th>
                <th>Remaining</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prizes as $p): ?>
            <tr>
                <td>#<?php echo $p['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td>
                    <span class="badge <?php echo $p['type'] === 'special' ? 'badge-winner' : 'badge-eligible'; ?>">
                        <?php echo strtoupper($p['type']); ?>
                    </span>
                </td>
                <td><?php echo $p['quantity']; ?></td>
                <td>
                    <strong style="color: <?php echo $p['remaining_quantity'] > 0 ? '#10b981' : '#ef4444'; ?>">
                        <?php echo $p['remaining_quantity']; ?>
                    </strong>
                </td>
                <td>
                    <span class="badge <?php echo $p['status'] === 'active' ? 'badge-eligible' : 'badge-ineligible'; ?>">
                        <?php echo strtoupper($p['status']); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
