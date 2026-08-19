<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (empty($_SESSION['user'])) {
        $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
        header("Location: $loginUrl");
        exit;
    }
}

function require_role($roles) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        http_response_code(403);
        die('Accès interdit : vous n\'avez pas la permission d\'accéder à cette page.');
    }
}

