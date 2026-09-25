<?php
/**
 * Admin Settings & Reset Control View
 */
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="panel-header">
        <h2 class="panel-title">⚙️ Campaign Settings & Controls</h2>
    </div>

    <!-- General Settings Form -->
    <form action="/api/admin/settings.php" method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Shop / Brand Name</label>
                <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($settings['shop_name'] ?? 'Mobile Gallery'); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Campaign Title</label>
                <input type="text" name="campaign_name" class="form-control" value="<?php echo htmlspecialchars($settings['campaign_name'] ?? 'Mobile Gallery Lucky Draw'); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Maximum Initial Coupon Number</label>
                <input type="number" name="max_coupon_number" class="form-control" value="<?php echo htmlspecialchars($settings['max_coupon_number'] ?? '357'); ?>" required min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Enable Public Lucky Draw</label>
                <select name="enable_draw" class="form-control">
                    <option value="1" <?php echo ($settings['enable_draw'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes - Active & Open</option>
                    <option value="0" <?php echo ($settings['enable_draw'] ?? '1') === '0' ? 'selected' : ''; ?>>No - Draw Paused</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Test Mode (Prevents permanent inventory deduction)</label>
                <select name="test_mode" class="form-control">
                    <option value="0" <?php echo ($settings['test_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>OFF - Live Production Mode</option>
                    <option value="1" <?php echo ($settings['test_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>ON - Demonstration / Test Mode</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Sound Effects Default</label>
                <select name="enable_sound" class="form-control">
                    <option value="1" <?php echo ($settings['enable_sound'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                    <option value="0" <?php echo ($settings['enable_sound'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-action btn-primary">Save Settings</button>
    </form>
</div>

<!-- Reset Controls Section -->
<div class="admin-panel" style="border-color: rgba(239, 68, 68, 0.4);">
    <div class="panel-header">
        <h2 class="panel-title" style="color: #ef4444;">🚨 Campaign Reset & Maintenance Controls</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <!-- Test Data Reset -->
        <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
            <h4 style="margin-bottom: 8px; color: var(--primary-gold);">Clear Test Winner Records</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                Removes winner records marked during Test Mode without affecting production inventory.
            </p>
            <form action="/api/admin/reset-draw.php" method="POST" onsubmit="return confirm('Clear test winner records?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="mode" value="test">
                <button type="submit" class="btn-action btn-secondary">Reset Test Data</button>
            </form>
        </div>

        <!-- Production Reset -->
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--accent-red); border-radius: 12px; padding: 20px;">
            <h4 style="margin-bottom: 8px; color: #ef4444;">Reset Production Draw</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                ⚠️ <strong>WARNING:</strong> This action will delete ALL winning records, restore all prize inventory remaining stock, and reset coupons back to eligible.
            </p>
            <button type="button" onclick="document.getElementById('prodResetModal').style.display='flex'" class="btn-action btn-danger">Reset Production Draw</button>
        </div>
    </div>
</div>

<!-- Production Reset Confirmation Modal -->
<div id="prodResetModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--card-bg); border: 2px solid var(--accent-red); border-radius: 16px; max-width: 480px; width: 100%; padding: 24px;">
        <h3 style="margin-bottom: 12px; color: #ef4444;">⚠️ CONFIRM PRODUCTION RESET</h3>
        <p style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 16px;">
            This will remove all winner records and restore prize inventory. This action cannot be easily undone.
        </p>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
            Please type <code>RESET_PRODUCTION_CONFIRM</code> below to confirm:
        </p>

        <form action="/api/admin/reset-draw.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="mode" value="production">

            <div class="form-group" style="margin-bottom: 20px;">
                <input type="text" name="confirm_code" class="form-control" placeholder="RESET_PRODUCTION_CONFIRM" required autocomplete="off">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('prodResetModal').style.display='none'" class="btn-action btn-secondary">Cancel</button>
                <button type="submit" class="btn-action btn-danger">CONFIRM FULL RESET</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
