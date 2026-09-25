<?php
/**
 * Admin Panel - Shared Header Template
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();

$settings = getSystemSettings();
$shopName = htmlspecialchars($settings['shop_name'] ?? 'Mobile Gallery');
$currentPage = basename($_SERVER['PHP_SELF']);
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $shopName; ?> Lucky Draw</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --primary-gold: #f5af19;
            --accent-red: #ef4444;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Admin Navbar */
        .admin-nav {
            background: #090d16;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
        }

        .admin-brand-icon {
            background: linear-gradient(135deg, var(--primary-gold), #d97706);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #000;
        }

        .admin-brand-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .admin-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
            flex-wrap: wrap;
        }

        .admin-menu a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .admin-menu a:hover, .admin-menu a.active {
            background: rgba(245, 175, 25, 0.15);
            color: var(--primary-gold);
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--accent-red);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: var(--accent-red);
            color: #fff;
        }

        /* Container */
        .admin-container {
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
            padding: 24px;
            flex: 1;
        }

        /* Cards Grid */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: rgba(255, 255, 255, 0.05);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Panels & Tables */
        .admin-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .data-table th, .data-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-eligible { background: rgba(16, 185, 129, 0.15); color: var(--accent-green); }
        .badge-winner { background: rgba(245, 175, 25, 0.15); color: var(--primary-gold); }
        .badge-ineligible { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); }
        .badge-used { background: rgba(148, 163, 184, 0.15); color: var(--text-muted); }

        /* Buttons */
        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary-gold); color: #000; }
        .btn-primary:hover { filter: brightness(1.1); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid var(--border-color); }
        .btn-danger { background: var(--accent-red); color: #fff; }

        /* Form Controls */
        .form-control {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-gold);
        }

        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <a href="dashboard.php" class="admin-brand">
            <div class="admin-brand-icon">MG</div>
            <span class="admin-brand-title"><?php echo $shopName; ?> ADMIN</span>
        </a>

        <ul class="admin-menu">
            <li><a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="coupons.php" class="<?php echo $currentPage === 'coupons.php' ? 'active' : ''; ?>">Coupons</a></li>
            <li><a href="prizes.php" class="<?php echo $currentPage === 'prizes.php' ? 'active' : ''; ?>">Prizes</a></li>
            <li><a href="special-winners.php" class="<?php echo $currentPage === 'special-winners.php' ? 'active' : ''; ?>">Special Winners</a></li>
            <li><a href="winners.php" class="<?php echo $currentPage === 'winners.php' ? 'active' : ''; ?>">Winner History</a></li>
            <li><a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">Settings</a></li>
        </ul>

        <div class="admin-user-info">
            <span>Logged in: <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></strong></span>
            <a href="/api/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
