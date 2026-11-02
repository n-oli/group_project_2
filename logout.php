<?php
require __DIR__ . '/includes/app.php';

// A visit to a URL must not silently log someone out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('welcome.php');
}
if (!valid_csrf_token()) {
    http_response_code(403);
    exit('Your form has expired. Go back, refresh the page, and try again.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'],
        $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
redirect_to('login.php');
