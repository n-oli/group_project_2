<?php
/**
 * listener/browse.php - lists every song that has an audio file, with a Play
 * button on each row. The player bar at the bottom (includes/player.php)
 * plays them. Only listeners can open this page.
 *
 * SEARCH
 * browse.php?q=burna shows only songs whose title, artist, album or genre
 * contains "burna". Each search is saved in the searches table, and the
 * listener's last 5 different searches are shown as buttons.
 */
require dirname(__DIR__) . '/includes/auth.php';
$user = require_user('listener');
require_once dirname(__DIR__) . '/includes/db.php';

// --- Clear recent searches (POST form at the bottom of the search card) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && valid_csrf_token()
    && ($_POST['action'] ?? '') === 'clear_searches') {
    $stmt = $pdo->prepare('DELETE FROM searches WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    redirect_to('listener/browse.php');   // Post/Redirect/Get
}

// --- What did they search for? -----------------------------------------
// GET is right for search: the URL can be bookmarked and refreshed.
// mb_substr keeps it inside the VARCHAR(255) column.
$q = trim(mb_substr($_GET['q'] ?? '', 0, 100));

// --- Find the songs ---------------------------------------------------------
// Same query as before; when there is a search term, add a LIKE filter.
// LIKE '%burna%' means "contains burna" (not case-sensitive in our database).
$sql = 'SELECT s.song_id, s.song_title, s.song_duration, s.audio_file,
               a.artist_name, al.album_name
        FROM songs s
        JOIN artists a      ON a.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        LEFT JOIN genres g  ON g.genre_id  = s.genre_id
        WHERE s.audio_file IS NOT NULL';
$params = [];

if ($q !== '') {
    // % and _ are wildcards in LIKE. addcslashes() puts a \ in front of them so
    // a search for "100%" looks for a real % sign instead of matching everything.
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $sql .= ' AND (s.song_title LIKE ? OR a.artist_name LIKE ?
                   OR al.album_name LIKE ? OR g.genre_name LIKE ?)';
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY a.artist_name, s.song_title';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll();

// --- Save the search -------------------------------------------------------
// Skip it when it is the same as their last search, so refreshing the page
// does not save the same thing again and again.
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT search_query FROM searches WHERE user_id = ? ORDER BY search_id DESC LIMIT 1');
    $stmt->execute([$user['user_id']]);
    if ($stmt->fetchColumn() !== $q) {
        $stmt = $pdo->prepare('INSERT INTO searches (user_id, search_query) VALUES (?, ?)');
        $stmt->execute([$user['user_id'], $q]);
    }
}

// --- Recent searches: last 5 different ones, newest first ----------------
// GROUP BY merges repeats; MAX(search_id) is when each was last searched.
$stmt = $pdo->prepare('SELECT search_query FROM searches WHERE user_id = ?
                       GROUP BY search_query ORDER BY MAX(search_id) DESC LIMIT 5');
$stmt->execute([$user['user_id']]);
$recent = $stmt->fetchAll(PDO::FETCH_COLUMN);

// --- Which songs has this listener hearted? ---------------------------------
// array_flip turns [3, 7] into [3 => 0, 7 => 1], so includes/song_table.php can
// check isset($favoriteIds[$id]) instead of searching the whole list each row.
$stmt = $pdo->prepare('SELECT song_id FROM favorites WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$favoriteIds = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
$returnTo = 'browse';

$pageTitle = 'Browse';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1 class="h2 mb-4"><i class="bi bi-collection-play"></i> Browse music</h1>

<?php // Search box. method="get" puts the search in the URL: browse.php?q=... ?>
<form method="get" action="<?= escape(app_url('listener/browse.php')) ?>" class="mb-3" role="search">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" name="q" value="<?= escape($q) ?>"
               placeholder="Search songs, artists, albums or genres" maxlength="100" aria-label="Search music">
        <button type="submit" class="btn btn-success">Search</button>
        <?php if ($q !== ''): ?>
            <a href="<?= escape(app_url('listener/browse.php')) ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($recent): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <span class="small text-body-secondary">Recent:</span>
        <?php foreach ($recent as $term): ?>
            <?php // http_build_query makes a safe link like ?q=burna+boy ?>
            <a href="<?= escape(app_url('listener/browse.php') . '?' . http_build_query(['q' => $term])) ?>"
               class="badge rounded-pill text-bg-secondary text-decoration-none"><?= escape($term) ?></a>
        <?php endforeach; ?>
        <form method="post" action="<?= escape(app_url('listener/browse.php')) ?>" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <input type="hidden" name="action" value="clear_searches">
            <button type="submit" class="btn btn-link btn-sm p-0 text-body-secondary">Clear history</button>
        </form>
    </div>
<?php endif; ?>

<?php if (!$songs && $q !== ''): ?>
    <div class="alert alert-warning">No songs match "<?= escape($q) ?>".</div>
<?php elseif (!$songs): ?>
    <div class="alert alert-info">No songs yet. Ask an admin to add some.</div>
<?php else: ?>
    <?php if ($q !== ''): ?>
        <p class="text-body-secondary"><?= count($songs) ?> result<?= count($songs) === 1 ? '' : 's' ?> for "<?= escape($q) ?>"</p>
    <?php endif; ?>
    <?php require dirname(__DIR__) . '/includes/song_table.php'; ?>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/player.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
