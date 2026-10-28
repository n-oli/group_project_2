<?php
session_start();
require __DIR__ . '/includes/db.php';

// Logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT ct.first_name
     FROM users u
     JOIN contacts ct ON ct.contact_id = u.contact_id
     WHERE u.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$firstName = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($firstName) ?>!</h1>
    <p>Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
       (role: <?= htmlspecialchars($_SESSION['role']) ?>)</p>
    <p><a href="logout.php">Log out</a></p>
</body>
</html>