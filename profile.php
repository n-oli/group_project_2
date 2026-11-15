<?php
/**
 * profile.php - "My account": shows the logged-in user's own details.
 * Any logged-in role can open it. The list itself is printed by
 * includes/profile_details.php. It can only ever show the current user,
 * because the data comes from require_user(), not from anything in the URL.
 */
require __DIR__ . '/includes/auth.php';
$user = require_user();
$pageTitle = 'My account';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><i class="bi bi-person-circle"></i> Your account information</h1>
                <?php require __DIR__ . '/includes/profile_details.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
