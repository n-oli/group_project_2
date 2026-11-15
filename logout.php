<?php
/**
 * logout.php - ends the session.
 *
 * It only responds to the navbar's "Log out" button, which is a small POST
 * form with a CSRF token (see includes/header.php). There is no page to
 * look at here: it always finishes by redirecting.
 */
require __DIR__ . '/includes/app.php';

// If logout worked from a plain link, any website could log you out just by
// embedding that link as an image. So opening this URL directly does nothing.
// A visit to a URL must not silently log someone out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('welcome.php');
}

// The POST must also come from our own form.
if (!valid_csrf_token()) {
    http_response_code(403);
    exit('Your form has expired. Go back, refresh the page, and try again.');
}

// Three steps to fully log out:
// 1. empty the session data,
// 2. tell the browser to delete the session cookie (set it to expire in the past),
// 3. destroy the session on the server.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'],
        $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
redirect_to('login.php');
