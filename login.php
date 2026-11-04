<?php
session_start();
require __DIR__ . '/includes/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if ($account && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true); // new session ID after login (prevents session fixation)
        $_SESSION['user_id']  = $account['user_id'];
        $_SESSION['username'] = $username;
        $_SESSION['role']     = $account['role_type'];

        header('Location: welcome.php');
        exit;
    }

    // Same message whether the username or the password was wrong
    $error = 'Invalid username or password.';
}

$pageTitle = 'Log In';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Log In</h1>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">Account created. You can log in now.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Log In</button>
                </form>

                <p class="text-center mt-3 mb-0">No account yet? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>