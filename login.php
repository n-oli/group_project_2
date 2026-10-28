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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In</title>
</head>
<body>
    <h1>Log In</h1>

    <?php if (isset($_GET['registered'])): ?>
        <p style="color: green;">Account created. You can log in now.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <p>
            <label>Username<br>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required></label>
        </p>
        <p>
            <label>Password<br>
            <input type="password" name="password" required></label>
        </p>
        <button type="submit">Log In</button>
    </form>

    <p>No account yet? <a href="register.php">Create one</a></p>
</body>
</html>