<?php
require __DIR__ . '/includes/auth.php';
$user = require_user();
$pageTitle = 'My account';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <p class="eyebrow">My account</p>
    <h1>Your account information</h1>
    <?php require __DIR__ . '/includes/profile_details.php'; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
