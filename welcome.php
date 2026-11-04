<?php
session_start();
require __DIR__ . '/includes/db.php';

// Logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT ct.first_name
     FROM users u
     JOIN contacts ct ON ct.contact_id = u.contact_id
     WHERE u.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$firstName = $stmt->fetchColumn();

$pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>
<div class="p-5 mb-4 bg-body-tertiary rounded-3">
    <h1 class="display-5 fw-bold">Welcome, <?= htmlspecialchars($firstName) ?>!</h1>
    <p class="lead">You're logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
       (<?= htmlspecialchars($_SESSION['role']) ?>).</p>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a class="btn btn-success btn-lg" href="admin/index.php"><i class="bi bi-gear"></i> Admin panel</a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>