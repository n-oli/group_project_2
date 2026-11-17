<?php
/**
 * login.php - shows the log-in form and checks the username + password.
 *
 * FLOW
 * 1. Already logged in? Skip the form and go to the dashboard.
 * 2. GET request  -> just show the empty form.
 * 3. POST request -> check the CSRF token, look the username up, verify the
 * password. Success: remember the user in the session and redirect to
 * their dashboard. Failure: show the form again with an error.
 *
 * The top half of this file is the logic, the bottom half is the HTML.
 */
require __DIR__ . '/includes/auth.php';

// current_user() returns the logged-in user or null. Someone who is already
// logged in has no reason to see this page.
$user = current_user();
if ($user) {
    redirect_to(dashboard_path($user['role_type']));
}
require_once __DIR__ . '/includes/db.php';

// Values the HTML below needs. $username is kept so the box is still filled
// in when the form is shown again after a wrong password.
$error = '';
$username = '';
$oauthError = $_SESSION['oauth_error'] ?? '';
unset($_SESSION['oauth_error']);

// REQUEST_METHOD is 'POST' when the form was submitted and 'GET' when the
// page was simply opened. First check: a submitted form must carry our secret
// CSRF token (see includes/app.php). A missing or stale token is refused.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !valid_csrf_token()) {
    $error = 'Your form has expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // "?? ''" means "use an empty string if the field is missing".
    // The password is NOT trimmed: spaces can be a real part of a password.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Find the account by username and fetch its password hash and role.
    // credentials holds the login, users links it to a role, roles has the name.
    // The "?" placeholder keeps the typed username out of the SQL (no injection).
    $stmt = $pdo->prepare(
        'SELECT c.user_id, c.password_hash, r.role_type
         FROM credentials c
         JOIN users u ON u.user_id = c.user_id
         JOIN roles r ON r.role_id = u.role_id
         WHERE c.username = ?'
    );
    $stmt->execute([$username]);
    $account = $stmt->fetch();

    // Three things must all be true: the username exists, the role is one we
    // know, and the password matches. We never store real passwords, only a
    // scrambled "hash"; password_verify() scrambles what was typed the same way
    // and compares the results.
    if ($account && in_array($account['role_type'], ['admin', 'listener'], true)
        && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true); // new session ID after login (prevents session fixation)
        $_SESSION['user_id']  = $account['user_id'];
        $_SESSION['username'] = $username;

        // Throw away the pre-login CSRF token; a fresh one is made on the next form.
        unset($_SESSION['csrf_token']);
        $_SESSION['role']     = $account['role_type'];

        redirect_to(dashboard_path($account['role_type']));
    }

    // Same message whether the username or the password was wrong
    $error = 'Invalid username or password.';
}

// ---------- HTML starts here ----------
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Log in to Decibel</h1>

                <?php // register.php redirects here with ?registered=1 after a successful sign-up. ?>
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success" role="status">Account created. You can log in now.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
                <?php endif; ?>

                <?php if ($oauthError): ?>
                    <div class="alert alert-danger" role="alert"><?= escape($oauthError) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= escape(app_url('login.php')) ?>">
                    <?php // Hidden secret that proves this form came from our own page (CSRF protection). ?>
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= escape($username) ?>" autocomplete="username" maxlength="30" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Log in</button>
                </form>
                <?php if (google_oauth_config() !== null): ?>
                    <div class="text-center text-secondary my-3">or</div>
                    <a href="<?= escape(app_url('oauth/google_start.php')) ?>" class="btn btn-outline-light w-100">
                        <i class="bi bi-google me-2"></i>Continue with Google
                    </a>
                <?php endif; ?>

                <p class="text-center mt-3 mb-0">No account yet? <a href="<?= escape(app_url('register.php')) ?>">Create one</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
