<?php
/**
 * includes/auth.php - "who is logged in, and are they allowed here?"
 *
 * HOW TO USE IT (put one of these at the very top of a page):
 * $user = current_user();        // logged-in user's details, or null. Page stays public.
 * $user = require_user();        // must be logged in (any role), else go to login.
 * $user = require_user('admin'); // must be logged in AND be an admin, else 403 page.
 * $user = require_user('listener');
 *
 * $user is an array with: user_id, username, role_type, first_name, last_name,
 * email, phone, street_1, street_2, city, state_code, post_code, creation_date.
 * header.php reads $user to decide what the navbar shows, so keep that
 * variable name.
 *
 * WHY IT RE-READS THE DATABASE EVERY TIME
 * The session only stores the user's id. The role and details are fetched
 * fresh on every request, so if an admin is demoted or an account is deleted
 * the change takes effect on their very next click, not at their next login.
 */
require_once __DIR__ . '/app.php';

// Returns the logged-in user's row from the database, or null if nobody is
// logged in (or the account no longer exists).
// Refresh identity and permissions from the database on each protected request.
function current_user()
{
    global $pdo;

    // No user id in the session means nobody has logged in on this browser.
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    // Open the database connection ($pdo) if this page has not already done so.
    // "global $pdo" above makes the connection visible outside this function too.
    require_once __DIR__ . '/db.php';

    // One query that joins the four "people" tables to get everything about the
    // account. The "?" is a placeholder: the real id is sent separately in
    // execute(), so user input can never change the SQL itself (no SQL injection).
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

    // The id in the session points to no account (it was deleted) or to an
    // unknown role: wipe the session so this browser is logged out, and give it
    // a brand-new session id.
    if (!$user || !in_array($user['role_type'], ['admin', 'listener'], true)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }

    // Keep the session copy in step with the database.
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role_type'];
    return $user;
}

// Gatekeeper for protected pages.
// - Not logged in            -> redirected to the login page.
// - Logged in, wrong role    -> "Access denied" page with HTTP status 403.
// - Otherwise                -> returns the user's details (same as current_user()).
// $role is optional: null means "any logged-in user".
function require_user($role = null)
{
    $user = current_user();

    // Nobody is logged in: send them to log in. redirect_to() stops the script.
    if (!$user) {
        redirect_to('login.php');
    }

    // Logged in but not the role this page needs (e.g. a listener opening an
    // admin page). Show a full page with the normal header and footer, then stop
    // so none of the protected page's own code runs.
    if ($role !== null && $user['role_type'] !== $role) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/header.php';
        echo '<div class="alert alert-danger"><h1 class="h4">Access denied</h1><p>Your account cannot access this page.</p>';
        echo '<a class="btn btn-success" href="' . escape(app_url(dashboard_path($user['role_type']))) . '">Go to your dashboard</a></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
    return $user;
}
