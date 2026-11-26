<?php
/**
 * listener/favorite.php - adds a song to, or removes it from, the listener's
 * favorites. It has no page of its own: the heart buttons post here.
 *
 * - Pressing the heart on a song that is not a favorite adds it.
 * - Pressing it again removes it (a "toggle").
 *
 * assets/favorites.js sends the form in the background and asks for JSON,
 * so the music keeps playing. Without JavaScript the form is sent normally
 * and we redirect back to the page it came from.
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

// Did the request come from favorites.js? It sends "Accept: application/json".
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// Only POST with our CSRF token may change data.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf_token()) {
    http_response_code(400);
    exit($wantsJson ? json_encode(['error' => 'Your page has expired. Please reload.']) : 'Bad request');
}

// FILTER_VALIDATE_INT gives a whole number, or false for anything else.
$songId = filter_var($_POST['song_id'] ?? '', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT 1 FROM songs WHERE song_id = ?');
$stmt->execute([$songId ?: 0]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit($wantsJson ? json_encode(['error' => 'Song not found.']) : 'Song not found');
}

// Toggle: try to remove it first. If nothing was removed, it was not a
// favorite yet, so add it. (rowCount() = how many rows the DELETE removed.)
$stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND song_id = ?');
$stmt->execute([$user['user_id'], $songId]);
$favorited = false;
if ($stmt->rowCount() === 0) {
    // INSERT IGNORE: if a double-click already added it, skip quietly instead
    // of failing on the UNIQUE (user_id, song_id) rule.
    $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, song_id) VALUES (?, ?)');
    $stmt->execute([$user['user_id'], $songId]);
    $favorited = true;
}

if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode(['favorited' => $favorited]);
    exit;
}

// No JavaScript: go back to the page the heart was on. Only these two known
// pages are allowed, so nobody can use this form to redirect to another site.
if (($_POST['return'] ?? '') === 'favorites') {
    redirect_to('listener/favorites.php');
}
$q = trim($_POST['q'] ?? '');
redirect_to('listener/browse.php' . ($q !== '' ? '?' . http_build_query(['q' => $q]) : ''));