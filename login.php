<?php
/**
 * Prime Dental Clinic Management System
 * Login Screen
 */

define('PRIME_DENTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

// Redirect if already logged in
if (Auth::check()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        if (Auth::attempt($username, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/index.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid credentials. Please check your username/email and password.';
        }
    }
}

$clinic = getClinicSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= e(CLINIC_NAME) ?> - <?= e(CLINIC_TAGLINE) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f766e 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-header {
            background: linear-gradient(135deg, #0d9488, #0284c7);
            padding: 32px 28px;
            text-align: center;
            color: white;
        }
        .login-logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .login-title {
            font-size: 24px;
            font-weight: 800;
            color: white;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }
        .login-subtitle {
            font-size: 13px;
            color: #ccfbf1;
            font-weight: 500;
        }
        .doctor-badge-pill {
            display: inline-block;
            background: rgba(0, 0, 0, 0.15);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            margin-top: 10px;
            font-weight: 600;
        }
        .login-body {
            padding: 32px 28px;
        }
        .demo-box {
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12.5px;
            color: #0f766e;
            margin-top: 20px;
        }
        .demo-box strong {
            color: #115e59;
        }
        .fill-demo-btn {
            background: #ccfbf1;
            color: #0f766e;
            border: 1px solid #99f6e4;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            float: right;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">🦷</div>
            <h1 class="login-title"><?= e(CLINIC_NAME) ?></h1>
            <div class="login-subtitle">"<?= e(CLINIC_TAGLINE) ?>"</div>
            <div class="doctor-badge-pill">
                <?= e(DENTIST_NAME) ?> &bull; Reg: <?= e(DENTIST_REG_NO) ?>
            </div>
        </div>

        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:8px;font-size:13.5px;margin-bottom:20px;">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom:18px;">
                    <label class="form-label" for="username">Username or Email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="admin or dr.rutuja" 
                           required 
                           autofocus>
                </div>

                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="••••••••" 
                           required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:15px;">
                    Sign In to Dental Portal &rarr;
                </button>
            </form>

            <div class="demo-box">
                <button type="button" class="fill-demo-btn" onclick="fillDemo()">Auto-Fill</button>
                <strong>Quick Login Access:</strong><br>
                Username: <code>admin</code> / Password: <code>admin123</code>
            </div>
        </div>
    </div>

    <script>
        function fillDemo() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
        }
    </script>
</body>
</html>
