<?php
/**
 * admin/add_artist.php - form to add an artist (name, optional bio and photo).
 *
 * All three admin "add" pages follow the same recipe:
 * 1. require_user('admin') - only admins get past this line.
 * 2. Load the choices for the dropdowns from the database.
 * 3. If the form was submitted (POST): tidy the input, validate it, and
 * either insert the row and redirect, or collect messages in $errors.
 * 4. Print the form. $old refills the fields after an error.
 *
 * Artists must exist before albums or songs can be added, because both of
 * those need an artist to point to.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/upload.php';
$user = require_user('admin');

// $errors = messages to show; $old = what the admin typed (to refill the form).
$errors = [];
$old = ['artist_name' => '', 'bio' => ''];

// Only runs when the form was submitted. On a normal page visit (GET) we
// skip straight to printing the empty form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['artist_name'] = trim($_POST['artist_name'] ?? '');
    $old['bio']         = trim($_POST['bio'] ?? '');

    if ($old['artist_name'] === '') {
        $errors[] = 'Artist name is required.';
    }

    if (!$errors) {
        try {
            // save_image() (includes/upload.php) checks and stores the photo. It returns
            // the path to save, null if no photo was chosen, or throws a RuntimeException
            // with a message for the user if the file is not acceptable.
            $imagePath = save_image('profile_image', 'artists');

            // An empty bio is stored as NULL rather than an empty string.
            $stmt = $pdo->prepare('INSERT INTO artists (artist_name, profile_image, bio) VALUES (?, ?, ?)');
            $stmt->execute([$old['artist_name'], $imagePath, $old['bio'] !== '' ? $old['bio'] : null]);

            // Redirect after saving ("Post/Redirect/Get"): if the admin refreshes the
            // page now, the browser reloads the form instead of adding the artist twice.
            // The name travels in the URL so the page can say "Added <name>."
            header('Location: add_artist.php?added=' . urlencode($old['artist_name']));
            exit;
        } catch (PDOException $e) {
            // Database problem: show a general message, never the raw SQL error.
            $errors[] = 'Could not save the artist. Please try again.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage(); // upload problems from save_image()
        }
    }
}

// ---------- HTML starts here ----------
$pageTitle = 'Add Artist';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Artist</li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><i class="bi bi-mic"></i> Add Artist</h1>

                <?php // Green confirmation after a successful save (see the redirect above). ?>
                <?php if (isset($_GET['added'])): ?>
                    <div class="alert alert-success">Added <?= htmlspecialchars($_GET['added']) ?>.</div>
                <?php endif; ?>

                <?php require __DIR__ . '/../includes/errors.php'; ?>

                <?php // enctype="multipart/form-data" is required for file uploads; without it the image never arrives. ?>
                <form method="post" action="add_artist.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="artist_name" class="form-label">Artist name</label>
                        <input type="text" class="form-control" id="artist_name" name="artist_name"
                               value="<?= htmlspecialchars($old['artist_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio <span class="text-body-secondary">(optional)</span></label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($old['bio']) ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="profile_image" class="form-label">Profile image <span class="text-body-secondary">(optional)</span></label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        <div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Artist</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
