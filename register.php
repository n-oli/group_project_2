<?php
session_start();
require __DIR__ . '/includes/db.php';

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and tidy the form input
    $old['first_name'] = trim($_POST['first_name'] ?? '');
    $old['last_name']  = trim($_POST['last_name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['username']   = trim($_POST['username'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';

    // Validate
    if ($old['first_name'] === '' || $old['last_name'] === '') {
        $errors[] = 'First and last name are required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username'])) {
        $errors[] = 'Username must be 3-30 letters, numbers, or underscores.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check that the username and email aren't already taken
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM credentials WHERE username = ?');
        $stmt->execute([$old['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }

        $stmt = $pdo->prepare('SELECT 1 FROM contacts WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    // Save to all three tables; if any insert fails, none are kept
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO contacts (first_name, last_name, email) VALUES (?, ?, ?)');
            $stmt->execute([$old['first_name'], $old['last_name'], $old['email']]);
            $contactId = $pdo->lastInsertId();

            // New accounts get the "listener" role
            $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_type = 'listener'")->fetchColumn();

            $stmt = $pdo->prepare('INSERT INTO users (role_id, contact_id) VALUES (?, ?)');
            $stmt->execute([$roleId, $contactId]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO credentials (username, user_id, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$old['username'], $userId, password_hash($password, PASSWORD_DEFAULT)]);

            $pdo->commit();

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
</head>
<body>
    <h1>Create Account</h1>

    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="register.php">
        <p>
            <label>First name<br>
            <input type="text" name="first_name" value="<?= htmlspecialchars($old['first_name']) ?>" required></label>
        </p>
        <p>
            <label>Last name<br>
            <input type="text" name="last_name" value="<?= htmlspecialchars($old['last_name']) ?>" required></label>
        </p>
        <p>
            <label>Email<br>
            <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required></label>
        </p>
        <p>
            <label>Username<br>
            <input type="text" name="username" value="<?= htmlspecialchars($old['username']) ?>" required></label>
        </p>
        <p>
            <label>Password<br>
            <input type="password" name="password" minlength="8" required></label>
        </p>
        <p>
            <label>Confirm password<br>
            <input type="password" name="confirm_password" minlength="8" required></label>
        </p>
        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="login.php">Log in</a></p>
</body>
</html>