<?php
/**
 * Admin Coupon Management View
 */
require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(coupon_number LIKE ? OR customer_name LIKE ? OR mobile LIKE ? OR city LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($statusFilter !== '') {
    $whereClauses[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count query
$countStmt = $db->prepare("SELECT COUNT(*) FROM coupons {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Data query
$dataStmt = $db->prepare("SELECT * FROM coupons {$whereSql} ORDER BY coupon_number ASC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$coupons = $dataStmt->fetchAll();
?>

<div class="admin-panel">
    <div class="panel-header">
        <h2 class="panel-title">🎟️ Coupon Management</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="document.getElementById('importCsvModal').style.display='flex'" class="btn-action btn-secondary">📥 Import CSV</button>
            <button onclick="openAddCouponModal()" class="btn-action btn-primary">+ Add Single Coupon</button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" class="form-row" style="align-items: flex-end; margin-bottom: 20px;">
        <div class="form-group" style="flex: 2;">
            <label class="form-label">Search Coupon / Name / Mobile</label>
            <input type="text" name="search" class="form-control" placeholder="Search coupon # or customer..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="form-group" style="flex: 1;">
            <label class="form-label">Filter by Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="eligible" <?php echo $statusFilter === 'eligible' ? 'selected' : ''; ?>>Eligible</option>
                <option value="winner" <?php echo $statusFilter === 'winner' ? 'selected' : ''; ?>>Winner</option>
                <option value="ineligible" <?php echo $statusFilter === 'ineligible' ? 'selected' : ''; ?>>Ineligible</option>
                <option value="used" <?php echo $statusFilter === 'used' ? 'selected' : ''; ?>>Used</option>
            </select>
        </div>

        <button type="submit" class="btn-action btn-secondary" style="height: 42px;">Filter</button>
        <a href="coupons.php" class="btn-action btn-secondary" style="height: 42px;">Reset</a>
    </form>

    <!-- Coupons Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Coupon #</th>
                <th>Customer Name</th>
                <th>Mobile Number</th>
                <th>City / State</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($coupons)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No coupons found matching criteria.</td></tr>
            <?php else: ?>
            <?php foreach ($coupons as $c): ?>
            <tr>
                <td><strong>#<?php echo $c['coupon_number']; ?></strong></td>
                <td><?php echo htmlspecialchars($c['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($c['mobile']); ?></td>
                <td><?php echo htmlspecialchars(($c['city'] ?? '') . ', ' . ($c['state'] ?? '')); ?></td>
                <td>
                    <span class="badge badge-<?php echo strtolower($c['status']); ?>">
                        <?php echo strtoupper($c['status']); ?>
                    </span>
                </td>
                <td>
                    <button onclick='editCoupon(<?php echo json_encode($c); ?>)' class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 0.8rem;">Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <span style="font-size: 0.85rem; color: var(--text-muted);">Page <?php echo $page; ?> of <?php echo $totalPages; ?> (Total: <?php echo $totalRecords; ?>)</span>
        <div style="display: flex; gap: 6px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn-action btn-secondary">&laquo; Prev</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn-action btn-secondary">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Coupon Modal -->
<div id="couponModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; max-width: 500px; width: 100%; padding: 24px;">
        <h3 id="modalTitle" style="margin-bottom: 16px; color: var(--primary-gold);">Add New Coupon</h3>
        <form action="/api/admin/add-coupon.php" method="POST" id="couponForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="id" id="couponId" value="">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Coupon Number *</label>
                    <input type="number" name="coupon_number" id="fieldCouponNumber" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" name="customer_name" id="fieldCustomerName" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mobile Number *</label>
                    <input type="text" name="mobile" id="fieldMobile" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="fieldStatus" class="form-control">
                        <option value="eligible">Eligible</option>
                        <option value="ineligible">Ineligible</option>
                        <option value="winner">Winner</option>
                        <option value="used">Used</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" id="fieldCity" class="form-control" value="Rewa">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" id="fieldState" class="form-control" value="Madhya Pradesh">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Address</label>
                <input type="text" name="address" id="fieldAddress" class="form-control">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('couponModal').style.display='none'" class="btn-action btn-secondary">Cancel</button>
                <button type="submit" class="btn-action btn-primary">Save Coupon</button>
            </div>
        </form>
    </div>
</div>

<!-- CSV Import Modal -->
<div id="importCsvModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; max-width: 500px; width: 100%; padding: 24px;">
        <h3 style="margin-bottom: 12px; color: var(--primary-gold);">📥 Import Coupons CSV</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
            Expected CSV Format:<br>
            <code>coupon_number,name,mobile,address,city,state</code><br>
            Example: <code>357,Rahul Sharma,9876543210,Main Road,Rewa,Madhya Pradesh</code>
        </p>

        <form action="/api/admin/import-coupons.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" class="form-control" required>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('importCsvModal').style.display='none'" class="btn-action btn-secondary">Cancel</button>
                <button type="submit" class="btn-action btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCouponModal() {
    document.getElementById('modalTitle').innerText = 'Add New Coupon';
    document.getElementById('couponId').value = '';
    document.getElementById('fieldCouponNumber').value = '';
    document.getElementById('fieldCustomerName').value = '';
    document.getElementById('fieldMobile').value = '';
    document.getElementById('fieldAddress').value = '';
    document.getElementById('fieldCity').value = 'Rewa';
    document.getElementById('fieldState').value = 'Madhya Pradesh';
    document.getElementById('fieldStatus').value = 'eligible';
    document.getElementById('couponModal').style.display = 'flex';
}

function editCoupon(c) {
    document.getElementById('modalTitle').innerText = `Edit Coupon #${c.coupon_number}`;
    document.getElementById('couponId').value = c.id;
    document.getElementById('fieldCouponNumber').value = c.coupon_number;
    document.getElementById('fieldCustomerName').value = c.customer_name;
    document.getElementById('fieldMobile').value = c.mobile;
    document.getElementById('fieldAddress').value = c.address || '';
    document.getElementById('fieldCity').value = c.city || '';
    document.getElementById('fieldState').value = c.state || '';
    document.getElementById('fieldStatus').value = c.status;
    document.getElementById('couponModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
