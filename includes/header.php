<?php
/**
 * includes/header.php - the top of every page: <head>, Bootstrap, and the navbar.
 *
 * HOW A PAGE USES IT
 * $user = require_user();          // (or current_user(), or nothing on public pages)
 * $pageTitle = 'My page';          // shown in the browser tab
 * require __DIR__ . '/includes/header.php';
 * ... the page's own HTML ...
 * require __DIR__ . '/includes/footer.php';
 *
 * The navbar looks at $user:
 * - $user set    -> Dashboard, My account, "Hi, <username>", Log out button
 * - $user empty  -> Log in, Create account
 *
 * Styling comes from Bootstrap 5 (loaded from a CDN, dark theme) plus our own
 * small assets/style.css. Icons are Bootstrap Icons (the <i class="bi ..."> tags).
 */

// Shared page top: Bootstrap CSS + navbar.
// Callers set $pageTitle and, when signed in, $user (from current_user() or require_user()).
require_once __DIR__ . '/app.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle ?? 'Music Player') ?> | Decibel</title>
     <script>
        // Dark/light mode: use the theme the visitor picked last time (saved by
        // assets/theme.js). It runs here, before the page is drawn, so a light-mode
        // visitor never sees a flash of dark first. Dark stays the default.
        try {
            var savedTheme = localStorage.getItem('decibel-theme');
            if (savedTheme === 'light' || savedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        } catch (e) { /* storage blocked (private window): keep dark */ }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= escape(app_url('assets/style.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<?php // Navigation bar. On small screens Bootstrap collapses the links behind the toggler button. ?>
<nav class="navbar navbar-expand-md bg-body-tertiary border-bottom" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= escape(app_url('welcome.php')) ?>">
            <i class="bi bi-music-note-beamed"></i> Decibel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php // Left side: links only logged-in users see. Dashboard goes to the admin or listener home depending on role. ?>
            <ul class="navbar-nav me-auto">
                <?php if (!empty($user)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= escape(app_url(dashboard_path($user['role_type']))) ?>">Dashboard</a></li>
                    <?php if ($user['role_type'] === 'listener'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= escape(app_url('listener/browse.php')) ?>">Browse</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= escape(app_url('listener/favorites.php')) ?>">Favorites</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= escape(app_url('profile.php')) ?>">My account</a></li>
                <?php endif; ?>
            </ul>
            <?php // Right side: greeting + logout for logged-in users, otherwise log in / sign up. ?>
            <ul class="navbar-nav align-items-md-center">
                <li class="nav-item">
                    <?php // Dark/light switch. assets/theme.js sets the icon and handles the click. ?>
                        <button type="button" id="theme-toggle" class="btn btn-link nav-link px-2 me-md-2"
                            aria-label="Switch to light mode" title="Switch to light mode">
                        <i class="bi bi-sun-fill"></i>
                    </button>
                </li>
                <?php if (!empty($user)): ?>
                    <li class="nav-item"><span class="navbar-text me-md-3">Hi, <?= escape($user['username']) ?></span></li>
                    <li class="nav-item">
                        <!-- Logout is a POST form with a CSRF token, so a link or image can't log someone out -->
                        <form method="post" action="<?= escape(app_url('logout.php')) ?>" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Log out</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= escape(app_url('login.php')) ?>">Log in</a></li>
                    <li class="nav-item"><a class="btn btn-success btn-sm ms-md-2" href="<?= escape(app_url('register.php')) ?>">Create account</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php // Everything a page prints goes inside <main>. footer.php closes it. ?>
<main class="container py-4 flex-grow-1">
