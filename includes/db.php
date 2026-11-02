<?php
// Database connection shared by every page.
// XAMPP defaults: user "root", empty password.
// Optional environment overrides also let tests use an isolated database.
$dbHost = getenv('MUSIC_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('MUSIC_DB_PORT') ?: '3306';
$dbName = getenv('MUSIC_DB_NAME') ?: 'music_player';
$dbUser = getenv('MUSIC_DB_USER') !== false ? getenv('MUSIC_DB_USER') : 'root';
$dbPassword = getenv('MUSIC_DB_PASSWORD') !== false ? getenv('MUSIC_DB_PASSWORD') : '';

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
