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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --bg-light: #f8fafc;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }

        .login-container {
            width: 100%;
        }

        /* Top branding section */
        .brand-section {
            text-align: center;
            margin-bottom: 30px;
            animation: slideDown 0.6s ease-out;
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            font-size: 40px;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .brand-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .brand-title {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
        }

        /* Main login card */
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            backdrop-filter: blur(10px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 40px 30px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .login-header h2 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            margin: 8px 0 0;
            opacity: 0.95;
            font-size: 13px;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }

        /* Login form */
        .login-body {
            padding: 40px;
        }

        .form-group-custom {
            margin-bottom: 22px;
            position: relative;
        }

        .form-control-custom {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            background: #fff;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #fff;
        }

        .form-label-custom {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label-custom i {
            margin-right: 6px;
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 44px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.3s;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Login button */
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            color: #fff;
            transition: all 0.35s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* Alerts */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 14px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.4s ease-out;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
        }

        /* Demo credentials section */
        .demo-section {
            margin-top: 35px;
            padding-top: 30px;
            border-top: 2px solid var(--border-color);
        }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary);
            font-size: 14px;
        }

        .credential-cards {
            display: grid;
            gap: 12px;
        }

        .credential-card {
            background: var(--bg-light);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            group: hover;
        }

        .credential-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .credential-card:hover::before {
            left: 100%;
        }

        .credential-card:hover {
            transform: translateX(8px);
        }

        .credential-card.admin {
            border-left: 4px solid #ef4444;
        }

        .credential-card.admin:hover {
            background: rgba(239, 68, 68, 0.08);
            border-color: #ef4444;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.15);
        }

        .credential-card.doctor {
            border-left: 4px solid #2563eb;
        }

        .credential-card.doctor:hover {
            background: rgba(37, 99, 235, 0.08);
            border-color: #2563eb;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
        }

        .credential-card.assistant {
            border-left: 4px solid #10b981;
        }

        .credential-card.assistant:hover {
            background: rgba(16, 185, 129, 0.08);
            border-color: #10b981;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
        }

        .card-info {
            flex: 1;
        }

        .card-role {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .card-role.admin {
            color: #ef4444;
        }

        .card-role.doctor {
            color: #2563eb;
        }

        .card-role.assistant {
            color: #10b981;
        }

        .card-creds {
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }

        .card-creds code {
            background: #fff;
            padding: 3px 7px;
            border-radius: 5px;
            color: #334155;
            border: 1px solid #e2e8f0;
            margin: 0 3px;
        }

        .card-select {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-custom {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: var(--primary);
            transition: transform 0.2s;
        }

        .checkbox-custom:hover {
            transform: scale(1.1);
        }

        /* Developer tools */
        .dev-tools-section {
            background: var(--bg-light);
            border-radius: 14px;
            padding: 20px;
            margin-top: 25px;
            border: 2px solid var(--border-color);
            animation: slideUp 0.6s ease-out 0.2s both;
        }

        .dev-tools-title {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dev-tools-title i {
            color: var(--primary);
        }

        .dev-tools-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 8px;
        }

        .dev-tool-link {
            background: #fff;
            color: var(--primary) !important;
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-height: 50px;
        }

        .dev-tool-link:hover {
            background: var(--primary);
            color: #fff !important;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .dev-tool-link i {
            font-size: 18px;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-body {
                padding: 30px 20px;
            }

            .brand-title {
                font-size: 24px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .dev-tools-links {
                grid-template-columns: 1fr;
            }
        }

        /* Loading state */
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .loading {
            pointer-events: none;
        }

        .loader {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- Brand Section -->
        <div class="brand-section">
            <div class="brand-icon">
                <i class="bi bi-hospital"></i>
            </div>
            <h1 class="brand-title"><?php echo APP_NAME; ?></h1>
            <p class="brand-subtitle"><?php echo CLINIC_NAME; ?></p>
        </div>

        <!-- Login Card -->
        <div class="login-container">
            <div class="login-card">
                <!-- Header -->
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account to continue</p>
                </div>

                <!-- Body -->
                <div class="login-body">
                    <!-- Alerts -->
                    <?php if ($error): ?>
                        <div class="alert-custom alert-danger">
                            <i class="bi bi-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert-custom alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert-custom alert-success">
                            <i class="bi bi-check-circle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="" autocomplete="off" id="loginForm">
                        <?php echo csrfField(); ?>

                        <!-- Username -->
                        <div class="form-group-custom">
                            <label class="form-label-custom" for="username">
                                <i class="bi bi-person-circle"></i>Username
                            </label>
                            <input type="text" class="form-control-custom" id="username" name="username"
                                placeholder="Enter your username" required autofocus
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <!-- Password -->
                        <div class="form-group-custom">
                            <label class="form-label-custom" for="password">
                                <i class="bi bi-shield-lock"></i>Password
                            </label>
                            <div style="position: relative;">
                                <input type="password" class="form-control-custom" id="password" name="password"
                                    placeholder="Enter your password" required>
                                <span class="password-toggle" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login" id="submitBtn">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>

                    <!-- Demo Credentials -->
                    <div class="demo-section">
                        <div class="section-title">
                            <i class="bi bi-lock-fill"></i> Demo Credentials
                        </div>

                        <div class="credential-cards">
                            <!-- Admin Card -->
                            <div class="credential-card admin" onclick="setCredentials('admin', '12345678')">
                                <div class="card-info">
                                    <div class="card-role admin">
                                        <i class="bi bi-shield-check"></i> Administrator
                                    </div>
                                    <div class="card-creds">
                                        <code>admin</code> • <code>12345678</code>
                                    </div>
                                </div>
                                <div class="card-select">
                                    <input type="checkbox" class="checkbox-custom" id="check-admin" 
                                        onchange="setCredentials('admin', '12345678')">
                                </div>
                            </div>

                            <!-- Doctor Card -->
                            <div class="credential-card doctor" onclick="setCredentials('doctor', '12345678')">
                                <div class="card-info">
                                    <div class="card-role doctor">
                                        <i class="bi bi-heart-pulse"></i> Doctor
                                    </div>
                                    <div class="card-creds">
                                        <code>doctor</code> • <code>12345678</code>
                                    </div>
                                </div>
                                <div class="card-select">
                                    <input type="checkbox" class="checkbox-custom" id="check-doctor" 
                                        onchange="setCredentials('doctor', '12345678')">
                                </div>
                            </div>

                            <!-- Assistant Card -->
                            <div class="credential-card assistant" onclick="setCredentials('assistant', '12345678')">
                                <div class="card-info">
                                    <div class="card-role assistant">
                                        <i class="bi bi-person-badge"></i> Assistant
                                    </div>
                                    <div class="card-creds">
                                        <code>assistant</code> • <code>12345678</code>
                                    </div>
                                </div>
                                <div class="card-select">
                                    <input type="checkbox" class="checkbox-custom" id="check-assistant" 
                                        onchange="setCredentials('assistant', '12345678')">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Tools -->
            <div class="dev-tools-section">
                <div class="dev-tools-title">
                    <i class="bi bi-bug"></i> Developer Tools
                </div>
                <div class="dev-tools-links">
                    <a href="../debug.php" class="dev-tool-link" title="Debug Utilities">
                        <i class="bi bi-terminal"></i>
                        <span>Debug</span>
                    </a>
                    <a href="../test-assistant.php" class="dev-tool-link" title="Test Assistant">
                        <i class="bi bi-person"></i>
                        <span>Assistant</span>
                    </a>
                    <a href="../test-pages.php" class="dev-tool-link" title="Test Pages">
                        <i class="bi bi-file-earmark"></i>
                        <span>Pages</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.add('bi-eye');
                toggleIcon.classList.remove('bi-eye-slash');
            }
        }

        // Set credentials from demo cards
        function setCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.getElementById('password').type = 'password';
            document.getElementById('toggleIcon').classList.remove('bi-eye-slash');
            document.getElementById('toggleIcon').classList.add('bi-eye');
            
            // Update checkboxes
            document.getElementById('check-admin').checked = (username === 'admin');
            document.getElementById('check-doctor').checked = (username === 'doctor');
            document.getElementById('check-assistant').checked = (username === 'assistant');
            
            // Focus on username for visual feedback
            document.getElementById('username').focus();
        }

        // Clear checkboxes when user manually types
        document.getElementById('username').addEventListener('input', function() {
            if (this.value !== 'admin' && this.value !== 'doctor' && this.value !== 'assistant') {
                document.getElementById('check-admin').checked = false;
                document.getElementById('check-doctor').checked = false;
                document.getElementById('check-assistant').checked = false;
            }
        });

        // Clear password field on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('password').value = '';
            
            // Add loading state to form
            document.getElementById('loginForm').addEventListener('submit', function() {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.classList.add('loading');
                btn.innerHTML = '<span class="loader"></span>Signing in...';
            });
        });

        // Enter key submits form
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>

</html>