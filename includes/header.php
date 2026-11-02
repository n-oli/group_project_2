<?php // Callers provide $pageTitle and, when signed in, $user. ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle) ?> | Decibel</title>
    <link rel="stylesheet" href="<?= escape(app_url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <nav class="container navigation" aria-label="Main navigation">
        <a class="brand" href="<?= escape(app_url('welcome.php')) ?>">Decibel<span>Music Player</span></a>
        <div class="nav-links">
        <?php if (!empty($user)): ?>
            <a href="<?= escape(app_url(dashboard_path($user['role_type']))) ?>">Dashboard</a>
            <a href="<?= escape(app_url('profile.php')) ?>">My account</a>
            <form method="post" action="<?= escape(app_url('logout.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <button type="submit" class="button secondary">Log out</button>
            </form>
        <?php else: ?>
            <a href="<?= escape(app_url('login.php')) ?>">Log in</a>
            <a class="button" href="<?= escape(app_url('register.php')) ?>">Create account</a>
        <?php endif; ?>
        </div>
    </nav>
</header>
<main class="container main-content">
