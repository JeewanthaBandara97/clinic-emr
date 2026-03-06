<?php

/**
 * Login Page
 * Clinic EMR System
 */

// Include config FIRST (before session_start)
require_once __DIR__ . '/../config/config.php';

// Now start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

// Redirect if already logged in
if (User::isLoggedIn()) {
    if (User::isAdmin()) {
        header('Location: ' . APP_URL . '/admin/index.php');
    } elseif (User::isDoctor()) {
        header('Location: ' . APP_URL . '/doctor/index.php');
    } else {
        header('Location: ' . APP_URL . '/assistant/index.php');
    }
    exit;
}

// Process login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $user = new User();
            if ($user->authenticate($username, $password)) {
                $user->createSession();

                // Log the activity
                $user->logActivity('Login', 'users', User::getUserId(), 'User logged in');

                // Redirect based on role
                $redirectUrl = $_SESSION['redirect_url'] ?? null;
                unset($_SESSION['redirect_url']);

                if ($redirectUrl) {
                    header('Location: ' . $redirectUrl);
                } elseif (User::isAdmin()) {
                    header('Location: ' . APP_URL . '/admin/index.php');
                } elseif (User::isDoctor()) {
                    header('Location: ' . APP_URL . '/doctor/index.php');
                } else {
                    header('Location: ' . APP_URL . '/assistant/index.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 30px;
            text-align: center;
            color: #fff;
        }

        .login-header i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .login-header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .login-body {
            padding: 30px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            height: auto;
        }

        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            color: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            color: #fff;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .demo-credentials {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }

        .demo-credentials h6 {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 10px;
        }

        .demo-credentials code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            color: #334155;
        }

        .demo-credentials .role-row {
            display: flex;
            align-items: center;
            padding: 4px 0;
        }

        .demo-credentials .role-badge {
            display: inline-block;
            width: 70px;
            font-weight: 600;
        }

        .demo-credentials .role-badge.admin {
            color: #dc2626;
        }

        .demo-credentials .role-badge.doctor {
            color: #2563eb;
        }

        .demo-credentials .role-badge.assistant {
            color: #16a34a;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-hospital"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p><?php echo CLINIC_NAME; ?></p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['error']);
                                                                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']);
                                                                unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="off">
                    <?php echo csrfField(); ?>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Username" required autofocus
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Password" required value="12345678">
                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>

                <div class="demo-credentials">
                    <h6><i class="bi bi-info-circle me-1"></i>Demo Credentials</h6>
                    <div class="role-row">
                        <span class="role-badge admin"><i class="bi bi-shield-lock me-1"></i>Admin:</span>
                        <span><code>admin</code> / <code>12345678</code></span>
                    </div>
                    <div class="role-row">
                        <span class="role-badge doctor"><i class="bi bi-heart-pulse me-1"></i>Doctor:</span>
                        <span><code>doctor</code> / <code>12345678</code></span>
                    </div>
                    <div class="role-row">
                        <span class="role-badge assistant"><i class="bi bi-person-badge me-1"></i>Assistant:</span>
                        <span><code>assistant</code> / <code>12345678</code></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="container text-center mt-3">
            <a href="debug.php" class="text-light text-decoration-none">Debug</a> ||
            <a href="test-assistant.php" class="text-light text-decoration-none">Test Assistant</a> ||
            <a href="test-pages.php" class="text-light text-decoration-none">Test Pages</a>
        </div>
    </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>