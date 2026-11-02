<?php
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
$pageTitle = 'Listener dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card">
    <p class="eyebrow">Listener dashboard</p>
    <h1>Welcome, <?= escape($user['first_name']) ?>!</h1>
    <p>Your Decibel account is ready.</p>
    <p class="muted">Music playback and library features are coming next.</p>
    <a class="button" href="<?= escape(app_url('profile.php')) ?>">View my account</a>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
