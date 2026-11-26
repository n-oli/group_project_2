<?php
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

$list = $pdo->prepare(
    'SELECT p.playlist_id, p.playlist_name, p.creation_date,
            COUNT(ps.song_id) AS track_count
     FROM playlists p
     LEFT JOIN playlist_songs ps ON ps.playlist_id = p.playlist_id
     WHERE p.user_id = ?
     GROUP BY p.playlist_id, p.playlist_name, p.creation_date
     ORDER BY p.creation_date DESC, p.playlist_id DESC'
);
$list->execute([$user['user_id']]);
$playlists = $list->fetchAll();

$pageTitle = 'My Playlists';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1 class="h2 mb-4"><i class="bi bi-music-note-list"></i> My Playlists</h1>

<form method="post" action="<?= escape(app_url('listener/playlist_action.php')) ?>" class="row g-2 mb-4">
    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <div class="col-sm-8 col-md-6">
        <label for="playlist_name" class="form-label">New playlist name</label>
        <input type="text" class="form-control" id="playlist_name" name="playlist_name" maxlength="100" required>
    </div>
    <div class="col-sm-auto align-self-end">
        <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Create playlist</button>
    </div>
</form>

<?php if (!$playlists): ?>
    <div class="alert alert-info">No playlists yet. Create one to save songs in your chosen order.</div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($playlists as $playlist): ?>
            <a href="<?= escape(app_url('listener/playlist.php?id=' . $playlist['playlist_id'])) ?>"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><?= escape($playlist['playlist_name']) ?></span>
                <span class="badge text-bg-secondary"><?= (int) $playlist['track_count'] ?> tracks</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
