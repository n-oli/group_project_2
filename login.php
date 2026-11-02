<?php
require __DIR__ . '/includes/auth.php';
$user = current_user();
if ($user) {
    redirect_to(dashboard_path($user['role_type']));
}
require_once __DIR__ . '/includes/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !valid_csrf_token()) {
    $error = 'Your form has expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT c.user_id, c.password_hash, r.role_type
         FROM credentials c
         JOIN users u ON u.user_id = c.user_id
         JOIN roles r ON r.role_id = u.role_id
         WHERE c.username = ?'
    );
    $stmt->execute([$username]);
    $account = $stmt->fetch();

    if ($account && in_array($account['role_type'], ['admin', 'listener'], true)
        && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true); // new session ID after login (prevents session fixation)
        $_SESSION['user_id']  = $account['user_id'];
        $_SESSION['username'] = $username;
        unset($_SESSION['csrf_token']);
        $_SESSION['role']     = $account['role_type'];

        redirect_to(dashboard_path($account['role_type']));
    }

    // Same message whether the username or the password was wrong
    $error = 'Invalid username or password.';
}
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <p class="eyebrow">Welcome back</p>
    <h1>Log in to Decibel</h1>
    <?php if (isset($_GET['registered'])): ?>
        <p class="alert success" role="status">Account created. You can log in now.</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="alert error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= escape(app_url('login.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="field">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="<?= escape($username) ?>" autocomplete="username" maxlength="30" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit">Log in</button>
    </form>
    <p>No account yet? <a href="<?= escape(app_url('register.php')) ?>">Create one</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
