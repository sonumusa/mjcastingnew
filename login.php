<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = post('email');
    $password = post('password');
    $remember = post('remember');
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $_SESSION['remember_token'] = $token;
                setcookie('remember_token', $token, time() + 86400 * 30, '/');
            }
            
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('module_select.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Gold Workshop</title>
    <style>
        body{margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;background:#0b1120;color:#e8edf5}
        .page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{width:100%;max-width:420px;background:#131c31;border:1px solid #1e3050;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.5);padding:32px}
        .logo{text-align:center;margin-bottom:24px}
        .logo h1{margin:0;font-family:'Playfair Display',serif;color:#DAA520;font-size:28px}
        .logo p{color:#5a6d8a;margin:4px 0 0;font-size:13px}
        .heading{margin:0 0 24px;font-size:22px;font-weight:700;text-align:center}
        .field{display:flex;flex-direction:column;margin-bottom:16px}
        .field label{margin-bottom:8px;font-size:13px;font-weight:600;color:#8899b4}
        .field input{border:1px solid #1e3050;border-radius:12px;padding:12px 14px;font-size:15px;background:#1a2744;color:#e8edf5;font-family:'Inter',sans-serif}
        .field input:focus{outline:none;border-color:#DAA520}
        .actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .actions a{color:#DAA520;text-decoration:none;font-size:14px}
        .button{width:100%;border:none;border-radius:12px;background:linear-gradient(135deg,#B8860B,#DAA520);color:#000;padding:14px 18px;font-size:15px;font-weight:700;cursor:pointer}
        .button:hover{opacity:0.95}
        .hint{font-size:13px;color:#5a6d8a;line-height:1.5;margin-top:16px;text-align:center}
        .alert{margin-bottom:16px;padding:14px 16px;border:1px solid #f43f5e;background:rgba(244,63,94,0.1);color:#f43f5e;border-radius:12px}
        .checkbox{display:inline-flex;align-items:center;gap:8px;font-size:14px;color:#8899b4}
        .checkbox input{accent-color:#DAA520}
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="logo">
                <h1>M.J Casting</h1>
                <p>ایم جے کاسٹنگ</p>
            </div>
            <h1 class="heading">Sign in</h1>
            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= htmlspecialchars(post('email', '')) ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="actions">
                    <label class="checkbox"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="register.php">Create account</a>
                </div>
                <button class="button" type="submit">Log in</button>
            </form>
            <p class="hint">Admin: admin@goldworkshop.test / admin123<br>User: user@goldworkshop.test / user12345</p>
        </div>
    </div>
</body>
</html>