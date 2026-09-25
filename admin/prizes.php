<?php
/**
 * Admin Prize Management View
 */
require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();
$prizes = $db->query("SELECT * FROM prizes ORDER BY type DESC, id ASC")->fetchAll();
?>

<div class="admin-panel">
    <div class="panel-header">
        <h2 class="panel-title">🎁 Prize Catalog & Inventory</h2>
        <button onclick="openAddPrizeModal()" class="btn-action btn-primary">+ Add New Prize</button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Prize Name</th>
                <th>Type</th>
                <th>Total Inventory</th>
                <th>Remaining Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prizes as $p): ?>
            <tr>
                <td>#<?php echo $p['id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                    <?php if ($p['description']): ?>
                        <div style="font-size:0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($p['description']); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $p['type'] === 'special' ? 'badge-winner' : 'badge-eligible'; ?>">
                        <?php echo strtoupper($p['type']); ?>
                    </span>
                </td>
                <td><?php echo $p['quantity']; ?></td>
                <td>
                    <strong style="color: <?php echo $p['remaining_quantity'] > 0 ? '#10b981' : '#ef4444'; ?>">
                        <?php echo $p['remaining_quantity']; ?> / <?php echo $p['quantity']; ?>
                    </strong>
                </td>
                <td>
                    <span class="badge <?php echo $p['status'] === 'active' ? 'badge-eligible' : 'badge-ineligible'; ?>">
                        <?php echo strtoupper($p['status']); ?>
                    </span>
                </td>
                <td>
                    <button onclick='editPrize(<?php echo json_encode($p); ?>)' class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 0.8rem;">Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Prize Modal -->
<div id="prizeModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; max-width: 500px; width: 100%; padding: 24px;">
        <h3 id="prizeModalTitle" style="margin-bottom: 16px; color: var(--primary-gold);">Add New Prize</h3>
        
        <form action="/api/admin/update-prize.php" method="POST" id="prizeForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="id" id="prizeId" value="">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Prize Name *</label>
                <input type="text" name="name" id="prizeName" class="form-control" required placeholder="e.g. Smart Watch">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prize Type *</label>
                    <select name="type" id="prizeType" class="form-control">
                        <option value="regular">Regular Prize (Auto Selected)</option>
                        <option value="special">Special Prize (Pre-Assigned Winner)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Quantity *</label>
                    <input type="number" name="quantity" id="prizeQuantity" class="form-control" required min="1" value="1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Remaining Quantity</label>
                    <input type="number" name="remaining_quantity" id="prizeRemaining" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="prizeStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Description / Model Details</label>
                <input type="text" name="description" id="prizeDescription" class="form-control" placeholder="Short description...">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('prizeModal').style.display='none'" class="btn-action btn-secondary">Cancel</button>
                <button type="submit" class="btn-action btn-primary">Save Prize</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPrizeModal() {
    document.getElementById('prizeForm').action = '/api/admin/add-prize.php';
    document.getElementById('prizeModalTitle').innerText = 'Add New Prize';
    document.getElementById('prizeId').value = '';
    document.getElementById('prizeName').value = '';
    document.getElementById('prizeType').value = 'regular';
    document.getElementById('prizeQuantity').value = '10';
    document.getElementById('prizeRemaining').value = '10';
    document.getElementById('prizeDescription').value = '';
    document.getElementById('prizeStatus').value = 'active';
    document.getElementById('prizeModal').style.display = 'flex';
}

function editPrize(p) {
    document.getElementById('prizeForm').action = '/api/admin/update-prize.php';
    document.getElementById('prizeModalTitle').innerText = `Edit Prize: ${p.name}`;
    document.getElementById('prizeId').value = p.id;
    document.getElementById('prizeName').value = p.name;
    document.getElementById('prizeType').value = p.type;
    document.getElementById('prizeQuantity').value = p.quantity;
    document.getElementById('prizeRemaining').value = p.remaining_quantity;
    document.getElementById('prizeDescription').value = p.description || '';
    document.getElementById('prizeStatus').value = p.status;
    document.getElementById('prizeModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
