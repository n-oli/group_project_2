<?php
require_once __DIR__ . '/app.php';

// Refresh identity and permissions from the database on each protected request.
function current_user()
{
    global $pdo;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    require_once __DIR__ . '/db.php';
    $stmt = $pdo->prepare(
        'SELECT u.user_id, u.creation_date, cr.username, r.role_type,
                ct.first_name, ct.last_name, ct.email, ct.phone,
                ct.street_1, ct.street_2, ct.city, ct.state_code, ct.post_code
         FROM users u
         JOIN credentials cr ON cr.user_id = u.user_id
         JOIN roles r ON r.role_id = u.role_id
         JOIN contacts ct ON ct.contact_id = u.contact_id
         WHERE u.user_id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !in_array($user['role_type'], ['admin', 'listener'], true)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role_type'];
    return $user;
}

function require_user($role = null)
{
    $user = current_user();
    if (!$user) {
        redirect_to('login.php');
    }
    if ($role !== null && $user['role_type'] !== $role) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/header.php';
        echo '<section class="card"><h1>Access denied</h1><p>Your account cannot access this page.</p>';
        echo '<a class="button" href="' . escape(app_url(dashboard_path($user['role_type']))) . '">Go to your dashboard</a></section>';
        require __DIR__ . '/footer.php';
        exit;
    }
    return $user;
}
