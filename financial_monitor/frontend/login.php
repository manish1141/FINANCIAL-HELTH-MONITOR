<?php
require_once '../backend/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FinAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem;
        }
        .auth-card {
            width: 100%; max-width: 400px; padding: 2.5rem; text-align: center;
        }
        .auth-card h2 { margin-bottom: 2rem; }
        .auth-card .form-group { text-align: left; margin-bottom: 1.5rem; }
        .error-msg { background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.4); text-align: left;}
        .auth-link { color: var(--accent-primary); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .auth-link:hover { color: var(--accent-primary-hover); }
        .btn-block { width: 100%; justify-content: center; padding: 0.8rem; font-size: 1rem; }
    </style>
</head>
<body class="dark-theme">
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="auth-container">
        <div class="auth-card glass-panel">
            <div class="logo" style="justify-content: center; margin-bottom: 1.5rem;">
                <i class='bx bx-cube-alt'></i> <span>FinAI</span>
            </div>
            <h2>Welcome Back</h2>
            <?php if(!empty($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-with-icon">
                        <i class='bx bx-envelope'></i>
                        <input type="email" name="email" required placeholder="you@example.com">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-with-icon">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <p style="margin-top: 1.5rem; font-size: 0.9rem;">
                Don't have an account? <a href="register.php" class="auth-link">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
