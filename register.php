<?php
/**
 * register.php - the sign-up form and the code that creates a new account.
 *
 * FLOW
 * 1. Already logged in? Go to the dashboard instead.
 * 2. GET  -> show the empty form.
 * 3. POST -> read the fields, validate them, make sure the username and email
 * are free, then save the account and redirect to login.php?registered=1.
 * Any problem: show the form again with the errors and the typed values.
 *
 * ONE ACCOUNT = THREE ROWS
 * contacts    (name, email, optional phone/address)
 * users       (links the contact to a role)
 * credentials (username + password hash)
 * All three are saved inside a transaction, so you never get half an account.
 *
 * Everyone who signs up here becomes a "listener". Admins are made by hand in
 * the database (see README).
 */
require __DIR__ . '/includes/auth.php';

// Logged-in users do not need to sign up again.
$user = current_user();
if ($user) {
    redirect_to(dashboard_path($user['role_type']));
}
require_once __DIR__ . '/includes/db.php';

// $errors collects every validation message; includes/errors.php prints them.
// $old holds what the user typed so the form can be refilled after an error
// (nobody wants to retype everything because of one mistake).
// array_fill_keys just builds ['first_name' => '', 'last_name' => '', ...].
$errors = [];
$old = array_fill_keys(['first_name', 'last_name', 'email', 'username',
    'phone', 'street_1', 'street_2', 'city', 'state_code', 'post_code'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form must carry our secret CSRF token (see includes/app.php).
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

    // Accept "ct" as well as "CT".
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

    // Length limits match the column sizes in sql/schema.sql. Checking here gives
    // a friendly message instead of a database error.
    foreach (['first_name', 'last_name', 'email', 'street_1', 'street_2', 'city'] as $field) {
        if (strlen($old[$field]) > 255) {
            $errors[] = 'Name, email, and address fields must be 255 bytes or fewer.';
            break;
        }
    }
    if (strlen($old['phone']) > 20) {
        $errors[] = 'Phone must be 20 characters or fewer.';
    }

    // Optional fields are only checked when something was typed.
    // The patterns mean: exactly two capital letters / exactly five digits.
    if ($old['state_code'] !== '' && !preg_match('/^[A-Z]{2}$/', $old['state_code'])) {
        $errors[] = 'Enter a two-letter state code.';
    }
    if ($old['post_code'] !== '' && !preg_match('/^[0-9]{5}$/', $old['post_code'])) {
        $errors[] = 'Enter a five-digit ZIP code.';
    }

    // Only hit the database when the basic checks passed.
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

    // A transaction groups several queries into one all-or-nothing step:
    // beginTransaction() starts it, commit() makes the changes permanent,
    // rollBack() undoes everything since the start.
    // Save to all three tables; if any insert fails, none are kept
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO contacts (first_name, last_name, email, phone, street_1, street_2, city, state_code, post_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$old['first_name'], $old['last_name'], $old['email'],
                $old['phone'] ?: null, $old['street_1'] ?: null, $old['street_2'] ?: null,
                $old['city'] ?: null, $old['state_code'] ?: null,
                $old['post_code'] !== '' ? $old['post_code'] : null]);

            // lastInsertId() is the auto-generated id of the row we just inserted.
            // We need it to link the next table to this one.
            // "X ?: null" above stores NULL in the database when an optional field is empty.
            $contactId = $pdo->lastInsertId();

            // New accounts get the "listener" role
            $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_type = 'listener'")->fetchColumn();

            $stmt = $pdo->prepare('INSERT INTO users (role_id, contact_id) VALUES (?, ?)');
            $stmt->execute([$roleId, $contactId]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO credentials (username, user_id, password_hash) VALUES (?, ?, ?)');

            // password_hash() turns the password into a one-way scrambled string.
            // Even someone who can read the database cannot get the password back.
            $stmt->execute([$old['username'], $userId, password_hash($password, PASSWORD_DEFAULT)]);

            $pdo->commit();

            // Redirect after a successful POST so refreshing the page cannot submit the
            // form a second time. login.php shows a green "Account created" message.
            redirect_to('login.php?registered=1');
        } catch (PDOException $e) {
            // Something failed part-way: undo the inserts that did succeed.
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}

// Open the optional section when it already holds something, so errors in it are visible
$hasContactDetails = false;
foreach (['phone', 'street_1', 'street_2', 'city', 'state_code', 'post_code'] as $field) {
    if ($old[$field] !== '') {
        $hasContactDetails = true;
    }
}

// ---------- HTML starts here ----------
$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Create your account</h1>

                <?php require __DIR__ . '/includes/errors.php'; ?>

                <form method="post" action="<?= escape(app_url('register.php')) ?>">
                    <?php // Hidden secret that proves this form came from our own page (CSRF protection). ?>
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?= escape($old['first_name']) ?>" autocomplete="given-name" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?= escape($old['last_name']) ?>" autocomplete="family-name" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= escape($old['email']) ?>" autocomplete="email" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= escape($old['username']) ?>" autocomplete="username"
                                   minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" aria-describedby="username-help" required>
                            <div class="form-text" id="username-help">3-30 letters, numbers, or underscores.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   autocomplete="new-password" minlength="8" aria-describedby="password-help" required>
                            <div class="form-text" id="password-help">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   autocomplete="new-password" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <?php // Optional fields live in a collapsible <details> block so the form stays short. ?>
                            <details<?= $hasContactDetails ? ' open' : '' ?>>
                                <summary class="mb-3">Contact details (optional)</summary>
                                <div class="row g-3">
                                    <?php // Each entry below: field name => [label, browser autofill hint, max length, Bootstrap column width]. ?>
                                    <?php foreach ([
                                        'phone'      => ['Phone', 'tel', 20, 'col-md-6'],
                                        'street_1'   => ['Street address', 'address-line1', 255, 'col-12'],
                                        'street_2'   => ['Address line 2', 'address-line2', 255, 'col-12'],
                                        'city'       => ['City', 'address-level2', 255, 'col-md-6'],
                                        'state_code' => ['State (two letters)', 'address-level1', 2, 'col-md-3'],
                                        'post_code'  => ['ZIP code (five digits)', 'postal-code', 5, 'col-md-3'],
                                    ] as $field => [$label, $autocomplete, $max, $column]): ?>
                                        <div class="<?= $column ?>">
                                            <label for="<?= escape($field) ?>" class="form-label"><?= escape($label) ?></label>
                                            <input type="<?= $field === 'phone' ? 'tel' : 'text' ?>" class="form-control"
                                                   id="<?= escape($field) ?>" name="<?= escape($field) ?>"
                                                   value="<?= escape($old[$field]) ?>" autocomplete="<?= escape($autocomplete) ?>" maxlength="<?= $max ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">Create account</button>
                        </div>
                    </div>
                </form>
                <?php if (google_oauth_config() !== null): ?>
                    <div class="text-center text-secondary my-3">or</div>
                    <a href="<?= escape(app_url('oauth/google_start.php')) ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-google me-2"></i>Sign up with Google
                    </a>
                <?php endif; ?>

                <p class="text-center mt-3 mb-0">Already have an account? <a href="<?= escape(app_url('login.php')) ?>">Log in</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
