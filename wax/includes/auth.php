<?php
function require_login() {
    if (!isLoggedIn()) {
        header("Location: " . getBaseUrl() . "login.php");
        exit;
    }
}

function is_logged_in() {
    return isLoggedIn();
}