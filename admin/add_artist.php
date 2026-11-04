<?php
require_once __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/upload.php';
require_admin();

$errors = [];
$old = ['artist_name' => '', 'bio' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['artist_name'] = trim($_POST['artist_name'] ?? '');
    $old['bio']         = trim($_POST['bio'] ?? '');

    if ($old['artist_name'] === '') {
        $errors[] = 'Artist name is required.';
    }

    if (!$errors) {
        try {
            $imagePath = save_image('profile_image', 'artists');

            $stmt = $pdo->prepare('INSERT INTO artists (artist_name, profile_image, bio) VALUES (?, ?, ?)');
            $stmt->execute([$old['artist_name'], $imagePath, $old['bio'] !== '' ? $old['bio'] : null]);

            header('Location: add_artist.php?added=' . urlencode($old['artist_name']));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not save the artist. Please try again.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage(); // upload problems from save_image()
        }
    }
}

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

                <?php if (isset($_GET['added'])): ?>
                    <div class="alert alert-success">Added <?= htmlspecialchars($_GET['added']) ?>.</div>
                <?php endif; ?>

                <?php require __DIR__ . '/../includes/errors.php'; ?>

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
