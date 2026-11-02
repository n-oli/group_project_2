<?php
require __DIR__ . '/includes/auth.php';
$user = current_user();
if ($user) {
    redirect_to(dashboard_path($user['role_type']));
}
require_once __DIR__ . '/includes/db.php';

$errors = [];
$old = array_fill_keys(['first_name', 'last_name', 'email', 'username',
    'phone', 'street_1', 'street_2', 'city', 'state_code', 'post_code'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token()) {
        $errors[] = 'Your form has expired. Please try again.';
    }
    // Read and tidy the form input
    $old['first_name'] = trim($_POST['first_name'] ?? '');
    $old['last_name']  = trim($_POST['last_name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['username']   = trim($_POST['username'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';

    foreach (['phone', 'street_1', 'street_2', 'city', 'state_code', 'post_code'] as $field) {
        $old[$field] = trim($_POST[$field] ?? '');
    }
    $old['state_code'] = strtoupper($old['state_code']);

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

    foreach (['first_name', 'last_name', 'email', 'street_1', 'street_2', 'city'] as $field) {
        if (strlen($old[$field]) > 255) {
            $errors[] = 'Name, email, and address fields must be 255 bytes or fewer.';
            break;
        }
    }
    if (strlen($old['phone']) > 20) {
        $errors[] = 'Phone must be 20 characters or fewer.';
    }
    if ($old['state_code'] !== '' && !preg_match('/^[A-Z]{2}$/', $old['state_code'])) {
        $errors[] = 'Enter a two-letter state code.';
    }
    if ($old['post_code'] !== '' && !preg_match('/^[0-9]{5}$/', $old['post_code'])) {
        $errors[] = 'Enter a five-digit ZIP code.';
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
            $stmt = $pdo->prepare('INSERT INTO contacts (first_name, last_name, email, phone, street_1, street_2, city, state_code, post_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$old['first_name'], $old['last_name'], $old['email'],
                $old['phone'] ?: null, $old['street_1'] ?: null, $old['street_2'] ?: null,
                $old['city'] ?: null, $old['state_code'] ?: null,
                $old['post_code'] !== '' ? $old['post_code'] : null]);
            $contactId = $pdo->lastInsertId();

            // New accounts get the "listener" role
            $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_type = 'listener'")->fetchColumn();

            $stmt = $pdo->prepare('INSERT INTO users (role_id, contact_id) VALUES (?, ?)');
            $stmt->execute([$roleId, $contactId]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO credentials (username, user_id, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$old['username'], $userId, password_hash($password, PASSWORD_DEFAULT)]);

            $pdo->commit();

            redirect_to('login.php?registered=1');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}
$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <p class="eyebrow">Join Decibel</p>
    <h1>Create your account</h1>
    <?php if ($errors): ?>
        <div class="alert error" role="alert"><ul>
            <?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="<?= escape(app_url('register.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="form-grid">
        <?php foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label): ?>
            <div class="field">
                <label for="<?= escape($field) ?>"><?= escape($label) ?></label>
                <input id="<?= escape($field) ?>" name="<?= escape($field) ?>" value="<?= escape($old[$field]) ?>" autocomplete="<?= $field === 'first_name' ? 'given-name' : 'family-name' ?>" maxlength="255" required>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?= escape($old['email']) ?>" autocomplete="email" maxlength="255" required>
        </div>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" value="<?= escape($old['username']) ?>" autocomplete="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" aria-describedby="username-help" required>
            <small id="username-help">3–30 letters, numbers, or underscores.</small>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" aria-describedby="password-help" required>
                <small id="password-help">At least 8 characters.</small>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
            </div>
        </div>
        <details>
            <summary>Contact details (optional)</summary>
            <?php foreach ([
                'phone' => ['Phone', 'tel', 20],
                'street_1' => ['Street address', 'address-line1', 255],
                'street_2' => ['Address line 2', 'address-line2', 255],
                'city' => ['City', 'address-level2', 255],
                'state_code' => ['State (two letters)', 'address-level1', 2],
                'post_code' => ['ZIP code (five digits)', 'postal-code', 5],
            ] as $field => [$label, $autocomplete, $max]): ?>
                <div class="field">
                    <label for="<?= escape($field) ?>"><?= escape($label) ?></label>
                    <input id="<?= escape($field) ?>" type="<?= $field === 'phone' ? 'tel' : 'text' ?>" name="<?= escape($field) ?>" value="<?= escape($old[$field]) ?>" autocomplete="<?= escape($autocomplete) ?>" maxlength="<?= $max ?>">
                </div>
            <?php endforeach; ?>
        </details>
        <button type="submit">Create account</button>
    </form>
    <p>Already have an account? <a href="<?= escape(app_url('login.php')) ?>">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
