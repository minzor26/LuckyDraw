<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = getCsrfToken();
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid security session token.';
    } else {
        $res = loginAdmin($username, $password);
        if ($res['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $errorMsg = $res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mobile Gallery Lucky Draw</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            padding: 32px 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            font-size: 2.5rem;
            margin-bottom: 8px;
        }

        .brand-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #f5af19;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 12px 16px;
            color: #ffffff;
            font-size: 1rem;
            outline: none;
        }

        .form-input:focus {
            border-color: #f5af19;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #f5af19, #d97706);
            color: #000000;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            filter: brightness(1.1);
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 18px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <div class="brand-logo">📱</div>
            <h1 class="brand-title">MOBILE GALLERY</h1>
            <p style="font-size: 0.85rem; color: #94a3b8;">Admin Dashboard Portal</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="error-alert"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-input" placeholder="admin" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Login to Dashboard</button>
        </form>
    </div>

</body>
</html>
