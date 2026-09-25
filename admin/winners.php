<?php
/**
 * Admin Winner History & Export View
 */
require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Filters
$search = trim($_GET['search'] ?? '');
$prizeFilter = trim($_GET['prize_id'] ?? '');
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(w.coupon_number LIKE ? OR w.customer_name LIKE ? OR w.mobile LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($prizeFilter !== '') {
    $whereClauses[] = "w.prize_id = ?";
    $params[] = $prizeFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

if ($exportCsv) {
    // Export CSV download stream
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=lucky_draw_winners_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Coupon Number', 'Customer Name', 'Mobile Number', 'Prize Won', 'Prize Type', 'Date Won', 'Time Won']);

    $stmt = $db->prepare("SELECT w.* FROM winners w {$whereSql} ORDER BY w.won_at DESC");
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['coupon_number'],
            $row['customer_name'],
            $row['mobile'],
            $row['prize_name'],
            strtoupper($row['prize_type']),
            date('d M Y', strtotime($row['won_at'])),
            date('h:i A', strtotime($row['won_at']))
        ]);
    }
    fclose($output);
    exit;
}

// Data query
$stmt = $db->prepare("SELECT w.* FROM winners w {$whereSql} ORDER BY w.won_at DESC");
$stmt->execute($params);
$winners = $stmt->fetchAll();

// Fetch prizes for filter dropdown
$allPrizes = $db->query("SELECT id, name FROM prizes ORDER BY name ASC")->fetchAll();
?>

<div class="admin-panel">
    <div class="panel-header">
        <h2 class="panel-title">🏆 Winner History & Official Log</h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-action btn-secondary">🖨️ Print Winner List</button>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-action btn-primary">📊 Export CSV</a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" class="form-row" style="align-items: flex-end; margin-bottom: 20px;">
        <div class="form-group" style="flex: 2;">
            <label class="form-label">Search Winner / Coupon / Phone</label>
            <input type="text" name="search" class="form-control" placeholder="Search coupon # or customer..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="form-group" style="flex: 1;">
            <label class="form-label">Filter by Prize</label>
            <select name="prize_id" class="form-control">
                <option value="">All Prizes</option>
                <?php foreach ($allPrizes as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $prizeFilter == $p['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-action btn-secondary" style="height: 42px;">Search</button>
        <a href="winners.php" class="btn-action btn-secondary" style="height: 42px;">Reset</a>
    </form>

    <!-- Winners Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Coupon No</th>
                <th>Customer Name</th>
                <th>Mobile Number</th>
                <th>Prize Won</th>
                <th>Prize Type</th>
                <th>Date</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($winners)): ?>
            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No winning records found.</td></tr>
            <?php else: ?>
            <?php foreach ($winners as $w): ?>
            <tr>
                <td><strong style="color: var(--primary-gold);">#<?php echo $w['coupon_number']; ?></strong></td>
                <td><strong><?php echo htmlspecialchars($w['customer_name']); ?></strong></td>
                <td><?php echo htmlspecialchars(maskMobileNumber($w['mobile'])); ?></td>
                <td><?php echo htmlspecialchars($w['prize_name']); ?></td>
                <td>
                    <span class="badge <?php echo $w['prize_type'] === 'special' ? 'badge-winner' : 'badge-eligible'; ?>">
                        <?php echo strtoupper($w['prize_type']); ?>
                    </span>
                </td>
                <td><?php echo date('d M Y', strtotime($w['won_at'])); ?></td>
                <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo date('h:i A', strtotime($w['won_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
