<?php
// Shared page top: Bootstrap CSS + navbar. Set $pageTitle before including this file.
require_once __DIR__ . '/auth.php'; // starts the session so the navbar knows who's logged in

$loggedIn = isset($_SESSION['user_id']);
$isAdmin  = ($_SESSION['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Music Player') ?> · Music Player</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-md bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/music_player/welcome.php">
            <i class="bi bi-music-note-beamed"></i> Music Player
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php if ($isAdmin): ?>
                    <li class="nav-item"><a class="nav-link" href="/music_player/admin/index.php">Admin</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-md-center">
                <?php if ($loggedIn): ?>
                    <li class="nav-item"><span class="navbar-text me-md-3">Hi, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="/music_player/logout.php">Log out</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/music_player/login.php">Log in</a></li>
                    <li class="nav-item"><a class="btn btn-success btn-sm ms-md-2" href="/music_player/register.php">Sign up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">