<?php
// Database connection shared by every page.
// XAMPP defaults: user "root", empty password.

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=music_player;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['column' => value]
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
    ]
);