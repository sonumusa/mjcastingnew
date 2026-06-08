<?php
require_once __DIR__ . '/config.php';
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');
redirect('login.php');