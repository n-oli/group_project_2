<?php
/**
 * listener/index.php - home page for listeners (the non-admin role).
 *
 * Right now it is only a welcome box. This is where the music player,
 * library, search, playlists and favorites should be added.
 * Only listeners may open it: an admin gets the "Access denied" page.
 * dirname(__DIR__) means "the folder above this one" (the project root).
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
$pageTitle = 'Listener dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="p-5 mb-4 bg-body-tertiary rounded-3">
    <p class="text-success text-uppercase fw-bold small mb-2">Listener dashboard</p>
    <h1 class="display-5 fw-bold">Welcome, <?= escape($user['first_name']) ?>!</h1>
    <p class="lead">Your Decibel account is ready.</p>
    <p class="text-body-secondary">Music playback and library features are coming next.</p>
    <a class="btn btn-success" href="<?= escape(app_url('profile.php')) ?>"><i class="bi bi-person"></i> View my account</a>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
