<?php
/**
 * Mobile Gallery Lucky Draw - Database Configuration & PDO Factory
 */

// Database Connection Parameters (Edit for Hostinger/Production MySQL)
define('DB_DRIVER', 'mysql'); // 'mysql' or 'sqlite'
define('DB_HOST', 'localhost');
define('DB_NAME', 'mobile_gallery_draw');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Determine writable SQLite file path (Support Vercel / Serverless read-only environment)
if (isset($_ENV['VERCEL']) || getenv('VERCEL') || !is_writable(__DIR__ . '/../database')) {
    define('SQLITE_FILE', sys_get_temp_dir() . '/lucky_draw.sqlite');
} else {
    define('SQLITE_FILE', __DIR__ . '/../database/lucky_draw.sqlite');
}

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $dsn = "sqlite:" . SQLITE_FILE;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            bootstrapSqliteDb($pdo);
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL connection fails or on Vercel / local demo
        try {
            $dsn = "sqlite:" . SQLITE_FILE;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            bootstrapSqliteDb($pdo);
        } catch (PDOException $ex) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    return $pdo;
}

function bootstrapSqliteDb(PDO $pdo) {
    // Check if tables exist in SQLite
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if (!$tables) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                coupon_number INTEGER NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                mobile TEXT NOT NULL,
                address TEXT,
                city TEXT,
                state TEXT,
                status TEXT DEFAULT 'eligible',
                purchase_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS prizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL DEFAULT 'regular',
                quantity INTEGER NOT NULL DEFAULT 1,
                remaining_quantity INTEGER NOT NULL DEFAULT 1,
                image TEXT,
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS special_prize_assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prize_id INTEGER NOT NULL UNIQUE,
                coupon_id INTEGER NOT NULL UNIQUE,
                coupon_number INTEGER NOT NULL UNIQUE,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE CASCADE,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS winners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                coupon_id INTEGER NOT NULL UNIQUE,
                prize_id INTEGER NOT NULL,
                coupon_number INTEGER NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                mobile TEXT NOT NULL,
                prize_name TEXT NOT NULL,
                prize_type TEXT NOT NULL,
                is_test INTEGER DEFAULT 0,
                won_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_username TEXT NOT NULL,
                action TEXT NOT NULL,
                details TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed initial admin user: admin / admin123
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@mobilegallery.com', $adminPass, 'admin']);

        // Settings
        $settings = [
            ['shop_name', 'Mobile Gallery'],
            ['campaign_name', 'Mobile Gallery Mega Lucky Draw'],
            ['subtitle', 'Empowering Your Tech Lifestyle'],
            ['max_coupon_number', '357'],
            ['enable_draw', '1'],
            ['enable_sound', '1'],
            ['test_mode', '0'],
            ['show_customer_details', '1']
        ];
        $stmtS = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $s) {
            $stmtS->execute($s);
        }

        // Prizes
        $prizes = [
            [1, 'Car (Hyundai i20)', 'special', 1, 1, 'Grand Prize: Brand new Hyundai i20 Hatchback', 'active'],
            [2, 'Scooty (Activa 6G)', 'special', 1, 1, 'Major Prize: Honda Activa 6G Scooter', 'active'],
            [3, 'Bluetooth Headphones', 'regular', 20, 20, 'Premium Wireless Over-Ear Headphones', 'active'],
            [4, 'Smart Watch', 'regular', 15, 15, 'Fitness Tracker Smart Watch', 'active'],
            [5, 'Wireless Earphones', 'regular', 30, 30, 'True Wireless TWS Earbuds', 'active'],
            [6, 'Power Bank 20000mAh', 'regular', 25, 25, 'Fast Charging Power Bank', 'active'],
            [7, 'Bluetooth Speaker', 'regular', 10, 10, 'Portable Bass Speaker', 'active'],
            [8, 'Mobile Accessories Kit', 'regular', 50, 50, 'Cable, Car Charger & Stand', 'active']
        ];
        $stmtP = $pdo->prepare("INSERT OR IGNORE INTO prizes (id, name, type, quantity, remaining_quantity, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($prizes as $p) {
            $stmtP->execute($p);
        }

        // Coupons 1 to 357 seed
        $stmtC = $pdo->prepare("INSERT OR IGNORE INTO coupons (coupon_number, customer_name, mobile, address, city, state, status) VALUES (?, ?, ?, ?, ?, ?, 'eligible')");
        $sampleCustomers = [
            1 => ['Aarav Patel', '9876543210', 'Station Road', 'Rewa', 'Madhya Pradesh'],
            2 => ['Priya Singh', '9812345678', 'Civil Lines', 'Rewa', 'Madhya Pradesh'],
            3 => ['Rohan Verma', '9988776655', 'College Road', 'Satna', 'Madhya Pradesh'],
            4 => ['Ananya Sharma', '9765432109', 'Main Market', 'Rewa', 'Madhya Pradesh'],
            5 => ['Vikram Malhotra', '9654321098', 'Bus Stand Area', 'Satna', 'Madhya Pradesh'],
            125 => ['Rahul Sharma', '9876500125', 'Main Road Sector 4', 'Rewa', 'Madhya Pradesh'],
            276 => ['Amit Verma', '9711220276', 'Nehru Nagar', 'Satna', 'Madhya Pradesh'],
            357 => ['Neha Jain', '9988770357', 'Malviya Marg', 'Rewa', 'Madhya Pradesh']
        ];

        for ($i = 1; $i <= 357; $i++) {
            if (isset($sampleCustomers[$i])) {
                $c = $sampleCustomers[$i];
                $stmtC->execute([$i, $c[0], $c[1], $c[2], $c[3], $c[4]]);
            } else {
                $stmtC->execute([$i, "Customer #" . $i, "98" . str_pad($i, 8, "0", STR_PAD_LEFT), "Main Bazaar", "Rewa", "Madhya Pradesh"]);
            }
        }

        // Special Prize Assignments (Car -> 125, Scooty -> 276)
        $stmtA = $pdo->prepare("INSERT OR IGNORE INTO special_prize_assignments (prize_id, coupon_id, coupon_number) SELECT p.id, c.id, c.coupon_number FROM prizes p, coupons c WHERE p.id = ? AND c.coupon_number = ?");
        $stmtA->execute([1, 125]);
        $stmtA->execute([2, 276]);
    }
}
