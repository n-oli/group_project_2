<?php
/**
 * test_db.php - quick check that PHP can reach the database.
 * Open it in the browser: if you see a list of genres, the connection in
 * includes/db.php works and sql/schema.sql has been imported.
 * DEBUG ONLY: it needs no login. Delete it before the final submission.
 */
require __DIR__ . '/includes/db.php';

$genres = $pdo->query('SELECT genre_id, genre_name FROM genres ORDER BY genre_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Test</title>
</head>
<body>
    <h1>Database connection works</h1>
    <p>Found <?= count($genres) ?> genres:</p>
    <ul>
        <?php foreach ($genres as $genre): ?>
            <li><?= $genre['genre_id'] ?>: <?= htmlspecialchars($genre['genre_name']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>