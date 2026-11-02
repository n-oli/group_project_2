<?php
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('admin');

// Authorization runs before this query or any page output.
$users = $pdo->query(
    'SELECT u.user_id, ct.first_name, ct.last_name, ct.email, cr.username, r.role_type
     FROM users u
     JOIN contacts ct ON ct.contact_id = u.contact_id
     JOIN credentials cr ON cr.user_id = u.user_id
     JOIN roles r ON r.role_id = u.role_id
     ORDER BY u.user_id'
)->fetchAll();
$pageTitle = 'Admin dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <p class="eyebrow">Admin dashboard</p>
    <h1>Welcome, <?= escape($user['first_name']) ?>!</h1>
    <p>View the accounts registered with Decibel.</p>
</section>
<section class="card">
    <h2>All users <span class="badge"><?= count($users) ?></span></h2>
    <div class="table-container" role="region" aria-label="Registered users" tabindex="0">
        <table>
            <caption>Account directory &middot; visible to administrators</caption>
            <thead><tr><th scope="col">ID</th><th scope="col">First name</th><th scope="col">Last name</th><th scope="col">Email</th><th scope="col">Username</th><th scope="col">Role</th></tr></thead>
            <tbody>
            <?php foreach ($users as $account): ?>
                <tr>
                    <th scope="row"><?= escape($account['user_id']) ?></th>
                    <td><?= escape($account['first_name']) ?></td>
                    <td><?= escape($account['last_name']) ?></td>
                    <td><?= escape($account['email']) ?></td>
                    <td><?= escape($account['username']) ?></td>
                    <td><span class="badge"><?= escape($account['role_type']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
