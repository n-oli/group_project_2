<?php
/**
 * admin/index.php - the admin's home page (dashboard).
 *
 * Shows three things:
 * 1. Cards with how many artists, albums and songs exist, each with an "Add" button.
 * 2. A table of the 10 most recently added songs.
 * 3. A table of every registered user (the "account directory").
 *
 * Only admins can open it; everyone else gets "Access denied".
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('admin');

// Authorization runs before these queries or any page output.
// One round-trip to the database that returns a single row like
// ['artists' => 4, 'albums' => 7, 'songs' => 52].
// query() (instead of prepare/execute) is fine here because no user input
// goes into the SQL.
$counts = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM artists) AS artists,
            (SELECT COUNT(*) FROM albums)  AS albums,
            (SELECT COUNT(*) FROM songs)   AS songs'
)->fetch();

// Newest songs first (highest song_id = added last).
// JOIN      = the song must have an artist (it always does).
// LEFT JOIN = album and genre are optional; keep the song even when they are
// empty (the column then comes back as NULL).
$recentSongs = $pdo->query(
    'SELECT s.song_title, a.artist_name, al.album_name, g.genre_name, s.song_duration
     FROM songs s
     JOIN artists a      ON a.artist_id = s.artist_id
     LEFT JOIN albums al ON al.album_id = s.album_id
     LEFT JOIN genres g  ON g.genre_id  = s.genre_id
     ORDER BY s.song_id DESC
     LIMIT 10'
)->fetchAll();

// Every account, built from the four "people" tables.
$users = $pdo->query(
    'SELECT u.user_id, ct.first_name, ct.last_name, ct.email, cr.username, r.role_type
     FROM users u
     JOIN contacts ct ON ct.contact_id = u.contact_id
     JOIN credentials cr ON cr.user_id = u.user_id
     JOIN roles r ON r.role_id = u.role_id
     ORDER BY u.user_id'
)->fetchAll();

// One card per thing an admin can add
$sections = [
    ['label' => 'Artists', 'count' => $counts['artists'], 'icon' => 'bi-mic',        'link' => 'add_artist.php', 'action' => 'Add artist'],
    ['label' => 'Albums',  'count' => $counts['albums'],  'icon' => 'bi-disc',       'link' => 'add_album.php',  'action' => 'Add album'],
    ['label' => 'Songs',   'count' => $counts['songs'],   'icon' => 'bi-music-note', 'link' => 'add_song.php',   'action' => 'Add song'],
];

// ---------- HTML starts here ----------
$pageTitle = 'Admin dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1 class="h2 mb-1">Admin dashboard</h1>
<p class="text-body-secondary mb-4">Welcome, <?= escape($user['first_name']) ?>!</p>

<?php // Count cards: one per entry in $sections. ?>
<div class="row g-3 mb-5">
    <?php foreach ($sections as $section): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 card-title"><i class="bi <?= $section['icon'] ?>"></i> <?= $section['label'] ?></h2>
                    <p class="display-6 mb-3"><?= $section['count'] ?></p>
                    <a href="<?= escape(app_url('admin/' . $section['link'])) ?>" class="btn btn-success">
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
                        <td><?= escape($song['song_title']) ?></td>
                        <td><?= escape($song['artist_name']) ?></td>
                        <td><?= escape($song['album_name'] ?? '—') ?></td>
                        <td><?= escape($song['genre_name'] ?? '—') ?></td>
                        <?php // song_duration is stored in seconds. 215 -> 3:35 (intdiv gives whole minutes, % gives the leftover seconds). ?>
                        <td><?= sprintf('%d:%02d', intdiv($song['song_duration'], 60), $song['song_duration'] % 60) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php // Account directory. Admin-only information: do not copy this table to non-admin pages. ?>
<h2 class="h4 mt-5 mb-3">All users <span class="badge text-bg-secondary"><?= count($users) ?></span></h2>
<div class="table-responsive" role="region" aria-label="Registered users" tabindex="0">
    <table class="table table-striped table-hover align-middle">
        <caption>Account directory &middot; visible to administrators</caption>
        <thead>
            <tr><th scope="col">ID</th><th scope="col">First name</th><th scope="col">Last name</th><th scope="col">Email</th><th scope="col">Username</th><th scope="col">Role</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $account): ?>
                <tr>
                    <th scope="row"><?= escape($account['user_id']) ?></th>
                    <td><?= escape($account['first_name']) ?></td>
                    <td><?= escape($account['last_name']) ?></td>
                    <td><?= escape($account['email']) ?></td>
                    <td><?= escape($account['username']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= escape($account['role_type']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
