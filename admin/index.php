<?php
require_once __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require_admin();

$counts = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM artists) AS artists,
            (SELECT COUNT(*) FROM albums)  AS albums,
            (SELECT COUNT(*) FROM songs)   AS songs'
)->fetch();

$recentSongs = $pdo->query(
    'SELECT s.song_title, a.artist_name, al.album_name, g.genre_name, s.song_duration
     FROM songs s
     JOIN artists a      ON a.artist_id = s.artist_id
     LEFT JOIN albums al ON al.album_id = s.album_id
     LEFT JOIN genres g  ON g.genre_id  = s.genre_id
     ORDER BY s.song_id DESC
     LIMIT 10'
)->fetchAll();

// One card per thing an admin can add
$sections = [
    ['label' => 'Artists', 'count' => $counts['artists'], 'icon' => 'bi-mic',        'link' => 'add_artist.php', 'action' => 'Add artist'],
    ['label' => 'Albums',  'count' => $counts['albums'],  'icon' => 'bi-disc',       'link' => 'add_album.php',  'action' => 'Add album'],
    ['label' => 'Songs',   'count' => $counts['songs'],   'icon' => 'bi-music-note', 'link' => 'add_song.php',   'action' => 'Add song'],
];

$pageTitle = 'Admin';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h2 mb-4">Admin</h1>

<div class="row g-3 mb-5">
    <?php foreach ($sections as $section): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 card-title"><i class="bi <?= $section['icon'] ?>"></i> <?= $section['label'] ?></h2>
                    <p class="display-6 mb-3"><?= $section['count'] ?></p>
                    <a href="<?= $section['link'] ?>" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i> <?= $section['action'] ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="h4 mb-3">Latest songs</h2>
<?php if (!$recentSongs): ?>
    <div class="alert alert-info">No songs yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr><th>Title</th><th>Artist</th><th>Album</th><th>Genre</th><th>Length</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentSongs as $song): ?>
                    <tr>
                        <td><?= htmlspecialchars($song['song_title']) ?></td>
                        <td><?= htmlspecialchars($song['artist_name']) ?></td>
                        <td><?= htmlspecialchars($song['album_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($song['genre_name'] ?? '—') ?></td>
                        <td><?= sprintf('%d:%02d', intdiv($song['song_duration'], 60), $song['song_duration'] % 60) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
