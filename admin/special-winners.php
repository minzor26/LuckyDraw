<?php
/**
 * Admin Special Prize Winners Pre-Assignment View
 */
require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch special prizes
$specialPrizes = $db->query("SELECT * FROM prizes WHERE type = 'special' AND status = 'active'")->fetchAll();

// Fetch current assignments
$assignments = $db->query("
    SELECT spa.*, p.name as prize_name, c.customer_name, c.mobile, c.status as coupon_status, w.won_at 
    FROM special_prize_assignments spa 
    JOIN prizes p ON spa.prize_id = p.id 
    JOIN coupons c ON spa.coupon_id = c.id 
    LEFT JOIN winners w ON spa.coupon_number = w.coupon_number
    ORDER BY p.id ASC
")->fetchAll();

// Fetch audit logs for special assignments
$auditLogs = $db->query("
    SELECT * FROM admin_audit_logs 
    WHERE action IN ('ASSIGN_SPECIAL_PRIZE', 'REMOVE_SPECIAL_ASSIGNMENT') 
    ORDER BY created_at DESC 
    LIMIT 15
")->fetchAll();
?>

<div class="admin-panel">
    <div class="panel-header">
        <h2 class="panel-title">⭐ Special Prize Winner Pre-Assignment</h2>
    </div>

    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
        Assign specific coupon numbers to major special prizes (e.g., <strong>Car &rarr; Coupon #125</strong>, <strong>Scooty &rarr; Coupon #276</strong>). 
        The public frontend will automatically award the pre-defined prize when the corresponding customer spins.
    </p>

    <!-- Assignment Form -->
    <form action="/api/admin/assign-special-prize.php" method="POST" style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label">Select Special Prize *</label>
                <select name="prize_id" class="form-control" required>
                    <option value="">-- Choose Special Prize --</option>
                    <?php foreach ($specialPrizes as $sp): ?>
                        <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="flex: 2;">
                <label class="form-label">Winning Coupon Number *</label>
                <input type="number" name="coupon_number" class="form-control" placeholder="e.g. 125 or 276" required min="1">
            </div>

            <div class="form-group" style="flex: 1; display: flex; align-items: flex-end;">
                <button type="submit" class="btn-action btn-primary" style="width: 100%; height: 42px; justify-content: center;">Save Assignment</button>
            </div>
        </div>
    </form>

    <!-- Current Special Prize Assignments Table -->
    <h3 style="font-size: 1.1rem; color: var(--primary-gold); margin-bottom: 14px;">Current Special Prize Assignments</h3>
    <table class="data-table" style="margin-bottom: 30px;">
        <thead>
            <tr>
                <th>Special Prize</th>
                <th>Winning Coupon #</th>
                <th>Customer Name</th>
                <th>Mobile</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assignments)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No special prize assignments created yet.</td></tr>
            <?php else: ?>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($a['prize_name']); ?></strong></td>
                <td><strong style="color: var(--primary-gold);">#<?php echo $a['coupon_number']; ?></strong></td>
                <td><?php echo htmlspecialchars($a['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($a['mobile']); ?></td>
                <td>
                    <?php if ($a['won_at']): ?>
                        <span class="badge badge-winner">CLAIMED / WON (<?php echo date('d M Y', strtotime($a['won_at'])); ?>)</span>
                    <?php else: ?>
                        <span class="badge badge-eligible">PRE-ASSIGNED</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form action="/api/admin/assign-special-prize.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove this special assignment?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="prize_id" value="<?php echo $a['prize_id']; ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn-action btn-danger" style="padding: 4px 8px; font-size: 0.8rem;">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Audit Log History Table -->
    <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 14px;">📜 Assignment Audit History</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Admin User</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($auditLogs)): ?>
            <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No audit logs recorded yet.</td></tr>
            <?php else: ?>
            <?php foreach ($auditLogs as $log): ?>
            <tr>
                <td style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                <td><strong><?php echo htmlspecialchars($log['admin_username']); ?></strong></td>
                <td><span class="badge badge-eligible"><?php echo htmlspecialchars($log['action']); ?></span></td>
                <td><?php echo htmlspecialchars($log['details']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
