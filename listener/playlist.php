<?php
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

$playlistId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$playlistQuery = $pdo->prepare('SELECT playlist_id, playlist_name FROM playlists WHERE playlist_id = ? AND user_id = ?');
$playlistQuery->execute([$playlistId ?: 0, $user['user_id']]);
$playlist = $playlistQuery->fetch();
if (!$playlist) {
    http_response_code(404);
    exit('Playlist not found.');
}

$songQuery = $pdo->prepare(
    'SELECT s.song_id, s.song_title, s.song_duration, s.audio_file,
            a.artist_name, al.album_name
     FROM playlist_songs ps
     JOIN songs s ON s.song_id = ps.song_id
     JOIN artists a ON a.artist_id = s.artist_id
     LEFT JOIN albums al ON al.album_id = s.album_id
     WHERE ps.playlist_id = ?
     ORDER BY ps.position, ps.song_id'
);
$songQuery->execute([$playlistId]);
$songs = $songQuery->fetchAll();

$availableQuery = $pdo->prepare(
    'SELECT s.song_id, s.song_title, a.artist_name
     FROM songs s
     JOIN artists a ON a.artist_id = s.artist_id
     LEFT JOIN playlist_songs ps ON ps.playlist_id = ? AND ps.song_id = s.song_id
     WHERE s.audio_file IS NOT NULL AND ps.song_id IS NULL
     ORDER BY a.artist_name, s.song_title'
);
$availableQuery->execute([$playlistId]);
$available = $availableQuery->fetchAll();

$pageTitle = $playlist['playlist_name'];
require dirname(__DIR__) . '/includes/header.php';
?>
<p><a href="<?= escape(app_url('listener/playlists.php')) ?>">&larr; My Playlists</a></p>
<h1 class="h2 mb-4"><?= escape($playlist['playlist_name']) ?></h1>

<?php if (!$songs): ?>
    <div class="alert alert-info">This playlist is empty. Add a song below.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th>Play</th><th>Title</th><th>Artist</th><th>Album</th><th>Length</th><th>Remove</th></tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <td>
                            <?php if ($song['audio_file']): ?>
                                <button type="button" class="btn btn-sm btn-success play-btn"
                                        data-src="<?= escape(app_url($song['audio_file'])) ?>"
                                        data-title="<?= escape($song['song_title']) ?>"
                                        data-artist="<?= escape($song['artist_name']) ?>"
                                        aria-label="Play <?= escape($song['song_title']) ?>">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-body-secondary small">Unavailable</span>
                            <?php endif; ?>
                        </td>
                        <td><?= escape($song['song_title']) ?></td>
                        <td><?= escape($song['artist_name']) ?></td>
                        <td><?= escape($song['album_name'] ?? '—') ?></td>
                        <td><?= sprintf('%d:%02d', intdiv($song['song_duration'], 60), $song['song_duration'] % 60) ?></td>
                        <td>
                            <form method="post" action="<?= escape(app_url('listener/playlist_action.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                <input type="hidden" name="action" value="remove_song">
                                <input type="hidden" name="playlist_id" value="<?= (int) $playlistId ?>">
                                <input type="hidden" name="song_id" value="<?= (int) $song['song_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($available): ?>
    <form method="post" action="<?= escape(app_url('listener/playlist_action.php')) ?>" class="row g-2 mb-4">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_song">
        <input type="hidden" name="playlist_id" value="<?= (int) $playlistId ?>">
        <div class="col-sm-8 col-md-6">
            <label for="song_id" class="form-label">Add a song</label>
            <select class="form-select" id="song_id" name="song_id" required>
                <option value="">Choose a song</option>
                <?php foreach ($available as $song): ?>
                    <option value="<?= (int) $song['song_id'] ?>"><?= escape($song['artist_name'] . ' — ' . $song['song_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-auto align-self-end">
            <button type="submit" class="btn btn-success">Add song</button>
        </div>
    </form>
<?php elseif (!$songs): ?>
    <p class="text-body-secondary">No playable songs are available yet.</p>
<?php endif; ?>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <form method="post" action="<?= escape(app_url('listener/playlist_action.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="playlist_id" value="<?= (int) $playlistId ?>">
            <label for="rename_playlist" class="form-label">Rename playlist</label>
            <div class="input-group">
                <input type="text" class="form-control" id="rename_playlist" name="playlist_name"
                       value="<?= escape($playlist['playlist_name']) ?>" maxlength="100" required>
                <button type="submit" class="btn btn-outline-secondary">Save</button>
            </div>
        </form>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <form method="post" action="<?= escape(app_url('listener/playlist_action.php')) ?>" onsubmit="return confirm('Delete this playlist?')">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="playlist_id" value="<?= (int) $playlistId ?>">
            <button type="submit" class="btn btn-outline-danger">Delete playlist</button>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/player.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
