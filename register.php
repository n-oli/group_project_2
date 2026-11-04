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

$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Create Account</h1>

                <?php require __DIR__ . '/includes/errors.php'; ?>

                <form method="post" action="register.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?= htmlspecialchars($old['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?= htmlspecialchars($old['last_name']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($old['email']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= htmlspecialchars($old['username']) ?>" required>
                            <div class="form-text">3-30 letters, numbers, or underscores.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                            <div class="form-text">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">Create Account</button>
                        </div>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>