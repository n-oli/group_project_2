<?php
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use a playlist form to make changes.');
}
if (!valid_csrf_token()) {
    http_response_code(403);
    exit('Your form has expired. Go back, refresh the page, and try again.');
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['create', 'rename', 'delete', 'add_song', 'remove_song'], true)) {
    http_response_code(400);
    exit('Invalid playlist request.');
}

if ($action === 'create' || $action === 'rename') {
    $name = $_POST['playlist_name'] ?? '';
    $name = is_string($name) ? trim($name) : '';
    if ($name === '' || mb_strlen($name) > 100) {
        http_response_code(400);
        exit('Playlist name must be 1 to 100 characters.');
    }
}

if ($action === 'create') {
    $save = $pdo->prepare('INSERT INTO playlists (playlist_name, user_id) VALUES (?, ?)');
    $save->execute([$name, $user['user_id']]);
    redirect_to('listener/playlist.php?id=' . $pdo->lastInsertId());
}

$playlistId = filter_input(INPUT_POST, 'playlist_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$owned = $pdo->prepare('SELECT playlist_id FROM playlists WHERE playlist_id = ? AND user_id = ?');
$owned->execute([$playlistId ?: 0, $user['user_id']]);
if (!$owned->fetch()) {
    http_response_code(404);
    exit('Playlist not found.');
}

if ($action === 'rename') {
    $save = $pdo->prepare('UPDATE playlists SET playlist_name = ? WHERE playlist_id = ? AND user_id = ?');
    $save->execute([$name, $playlistId, $user['user_id']]);
} elseif ($action === 'delete') {
    $remove = $pdo->prepare('DELETE FROM playlists WHERE playlist_id = ? AND user_id = ?');
    $remove->execute([$playlistId, $user['user_id']]);
    redirect_to('listener/playlists.php');
} else {
    $songId = filter_input(INPUT_POST, 'song_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$songId) {
        http_response_code(400);
        exit('Choose a valid song.');
    }
    if ($action === 'add_song') {
        $song = $pdo->prepare('SELECT song_id FROM songs WHERE song_id = ? AND audio_file IS NOT NULL');
        $song->execute([$songId]);
        if (!$song->fetch()) {
            http_response_code(404);
            exit('Song not found.');
        }
        // Existing membership stays unchanged if the Add form is submitted twice.
        $next = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM playlist_songs WHERE playlist_id = ?');
        $next->execute([$playlistId]);
        $save = $pdo->prepare('INSERT IGNORE INTO playlist_songs (playlist_id, song_id, position) VALUES (?, ?, ?)');
        $save->execute([$playlistId, $songId, $next->fetchColumn()]);
    } else {
        $remove = $pdo->prepare('DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?');
        $remove->execute([$playlistId, $songId]);
    }
}

redirect_to('listener/playlist.php?id=' . $playlistId);
