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
        <script>
        (function(){try{var t=localStorage.getItem('mj_theme')||'dark';document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
    </script>
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
    
        :root{--bg-body:#0b1120;--bg-card:#131c31;--bg-surface:#1a2744;--border-color:#1e3050;--text-primary:#e8edf5;--text-secondary:#8899b4;--text-muted:#5a6d8a;--gold-primary:#DAA520;--shadow-auth:0 20px 50px rgba(0,0,0,.5)}
        html[data-theme="light"]{--bg-body:#f5f7fb;--bg-card:#ffffff;--bg-surface:#f1f5f9;--border-color:#d9e2ec;--text-primary:#111827;--text-secondary:#4b5563;--text-muted:#6b7280;--gold-primary:#B8860B;--shadow-auth:0 20px 50px rgba(15,23,42,.12)}
        html[data-theme="light"] body{background:var(--bg-body)!important;color:var(--text-primary)!important}
        html[data-theme="light"] .card, html[data-theme="light"] .module-card{background:var(--bg-card)!important;border-color:var(--border-color)!important;box-shadow:var(--shadow-auth)!important;color:var(--text-primary)!important}
        html[data-theme="light"] .field input{background:#fff!important;color:var(--text-primary)!important;border-color:var(--border-color)!important}
        .standalone-theme-toggle{position:fixed;top:16px;right:16px;z-index:20;display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:999px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-secondary);font-weight:700;cursor:pointer}
        html[data-theme="light"] .logo p, html[data-theme="light"] .subtitle, html[data-theme="light"] .hint, html[data-theme="light"] .field label, html[data-theme="light"] .checkbox{color:var(--text-secondary)!important}


    </style>
</head>
<body>
    <button type="button" class="standalone-theme-toggle" id="standalone-theme-toggle">🌙 <span>Dark</span></button>

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
            <!--<p class="hint">Admin: admin@goldworkshop.test / admin123<br>User: user@goldworkshop.test / user12345</p>-->
        </div>
    </div>

<script>
(function(){
 function apply(t,p){t=t==='light'?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(p){try{localStorage.setItem('mj_theme',t)}catch(e){}}var b=document.getElementById('standalone-theme-toggle');if(b){b.innerHTML=(t==='light'?'☀️ <span>Light</span>':'🌙 <span>Dark</span>');}}
 document.addEventListener('DOMContentLoaded',function(){var t='dark';try{t=localStorage.getItem('mj_theme')||document.documentElement.getAttribute('data-theme')||'dark'}catch(e){}apply(t,false);var b=document.getElementById('standalone-theme-toggle');if(b)b.addEventListener('click',function(){apply(document.documentElement.getAttribute('data-theme')==='light'?'dark':'light',true)});});
})();
</script>
</body>
</html>