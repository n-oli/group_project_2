<?php
// Access checks. Call require_login() or require_admin() at the top of a page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /music_player/login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Admins only.');
    }
}
