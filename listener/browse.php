<?php
/**
 * listener/browse.php - lists every song that has an audio file, with a Play
 * button on each row. The player bar at the bottom (includes/player.php)
 * plays them. Only listeners can open this page.
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

// Retriving the songs from database
// All playable songs. LEFT JOIN because album is optional.
// Songs without an audio_file are skipped: there is nothing to play.
$songs = $pdo->query(
    'SELECT s.song_id, s.song_title, s.song_duration, s.audio_file,
            a.artist_name, al.album_name
     FROM songs s
     JOIN artists a      ON a.artist_id = s.artist_id
     LEFT JOIN albums al ON al.album_id = s.album_id
     WHERE s.audio_file IS NOT NULL
     ORDER BY a.artist_name, s.song_title'
)->fetchAll();

$pageTitle = 'Browse';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1 class="h2 mb-4"><i class="bi bi-collection-play"></i> Browse music</h1>

<?php if (!$songs): ?>
    <div class="alert alert-info">No songs yet. Ask an admin to add some.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th></th><th>Title</th><th>Artist</th><th>Album</th><th>Length</th></tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <td>
                            <?php // The data- attributes tell assets/player.js what to play. ?>
                            <button type="button" class="btn btn-sm btn-success play-btn"
                                    data-src="<?= escape(app_url($song['audio_file'])) ?>"
                                    data-title="<?= escape($song['song_title']) ?>"
                                    data-artist="<?= escape($song['artist_name']) ?>"
                                    aria-label="Play <?= escape($song['song_title']) ?>">
                                <i class="bi bi-play-fill"></i>
                            </button>
                        </td>
                        <td><?= escape($song['song_title']) ?></td>
                        <td><?= escape($song['artist_name']) ?></td>
                        <td><?= escape($song['album_name'] ?? '—') ?></td>
                        <td><?= sprintf('%d:%02d', intdiv($song['song_duration'], 60), $song['song_duration'] % 60) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/player.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>