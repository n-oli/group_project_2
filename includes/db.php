<?php
/**
 * includes/db.php - opens the one database connection the whole app shares.
 *
 * After this file is included you have $pdo, a PDO object. Typical use:
 * $stmt = $pdo->prepare('SELECT * FROM songs WHERE song_id = ?');
 * $stmt->execute([$id]);
 * $song = $stmt->fetch();      // one row, or false
 * $rows = $stmt->fetchAll();   // all rows
 *
 * ALWAYS use prepare() + execute() when a value comes from a user. Never glue
 * user input into the SQL string.
 *
 * If MySQL is not running (start it in XAMPP) this file throws an error.
 */

// Database connection shared by every page.
// XAMPP defaults: user "root", empty password.
// Optional environment overrides also let tests use an isolated database.
// Connection settings. Each one first looks for an environment variable and
// falls back to the XAMPP default, so it works out of the box on XAMPP while
// the tests (and a real server later) can point at a different database
// without editing this file.
// "?:" means "use the left side unless it is empty".
$dbHost = getenv('MUSIC_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('MUSIC_DB_PORT') ?: '3306';
$dbName = getenv('MUSIC_DB_NAME') ?: 'music_player';
$dbUser = getenv('MUSIC_DB_USER') !== false ? getenv('MUSIC_DB_USER') : 'root';
$dbPassword = getenv('MUSIC_DB_PASSWORD') !== false ? getenv('MUSIC_DB_PASSWORD') : '';

// charset=utf8mb4 lets us store any character, including emoji and accents.
// The three options are explained on each line.
$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['column' => value]
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
    ]
);
