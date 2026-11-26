<?php
/**
 * listener/favorites.php - "My favorites": every song the listener has hearted,
 * newest first, with the same Play and heart buttons as the Browse page.
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

// Start from favorites (only this user's rows) and join the song details.
$stmt = $pdo->prepare(
    'SELECT s.song_id, s.song_title, s.song_duration, s.audio_file,
            a.artist_name, al.album_name
     FROM favorites f
     JOIN songs s        ON s.song_id   = f.song_id
     JOIN artists a      ON a.artist_id = s.artist_id
     LEFT JOIN albums al ON al.album_id = s.album_id
     WHERE f.user_id = ? AND s.audio_file IS NOT NULL
     ORDER BY f.date_added DESC, f.favorite_id DESC'
);
$stmt->execute([$user['user_id']]);
$songs = $stmt->fetchAll();

// Every song on this page is a favorite. (Un-hearting one leaves it on the page
// until reload, so it can be hearted again if that was a mistake.)
$favoriteIds = array_flip(array_column($songs, 'song_id'));
$returnTo = 'favorites';

$pageTitle = 'My favorites';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1 class="h2 mb-4"><i class="bi bi-heart-fill text-danger"></i> My favorites</h1>

<?php if (!$songs): ?>
    <div class="alert alert-info">
        No favorites yet. Press the <i class="bi bi-heart"></i> next to a song on the
        <a href="<?= escape(app_url('listener/browse.php')) ?>" class="alert-link">Browse</a> page.
    </div>
<?php else: ?>
    <?php require dirname(__DIR__) . '/includes/song_table.php'; ?>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/player.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>