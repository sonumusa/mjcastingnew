<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (query('finish') === '1') {
    $_SESSION = [];
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    redirect('login.php');
}

redirect('backup_gate.php?event=logout');