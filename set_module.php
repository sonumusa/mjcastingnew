<?php
require_once __DIR__ . '/config.php';
requireAuth();

$module = query('module', '');
if ($module === 'wax') {
    $_SESSION['module'] = 'wax';
    redirect('wax/index.php');
} else {
    $_SESSION['module'] = 'casting';
    redirect('dashboard.php');
}