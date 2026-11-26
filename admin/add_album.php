<?php
/**
 * admin/add_album.php - form to add an album (name, artist, optional genre,
 * release date, track count and cover image).
 *
 * All three admin "add" pages follow the same recipe:
 * 1. require_user('admin') - only admins get past this line.
 * 2. Load the choices for the dropdowns from the database.
 * 3. If the form was submitted (POST): tidy the input, validate it, and
 * either insert the row and redirect, or collect messages in $errors.
 * 4. Print the form. $old refills the fields after an error.
 *
 * If there are no artists yet, the page shows a hint to add one first
 * instead of the form.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/upload.php';
$user = require_user('admin');

// Choices for the two dropdowns.
$artists = $pdo->query('SELECT artist_id, artist_name FROM artists ORDER BY artist_name')->fetchAll();
$genres  = $pdo->query('SELECT genre_id, genre_name FROM genres ORDER BY genre_name')->fetchAll();

// array_column($rows, 'artist_name', 'artist_id') turns the list of rows
// into a simple [id => name] array, e.g. [3 => 'Burna Boy', 7 => 'Adele'].
// We must re-check ids on the server because anyone can edit the HTML of a
// dropdown in their browser and submit an id that does not exist.
// id => name lookups, used to check that submitted ids are real
$artistNames = array_column($artists, 'artist_name', 'artist_id');
$genreNames  = array_column($genres, 'genre_name', 'genre_id');

// $errors = messages to show; $old = what the admin typed (to refill the form).
// The keys of $old are exactly the form's field names.
$errors = [];
$old = ['album_name' => '', 'artist_id' => '', 'genre_id' => '', 'release_date' => '', 'total_tracks' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !valid_csrf_token()) {
    $errors[] = 'Your form has expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read every field named in $old from the submitted form, trimming spaces.
    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if ($old['album_name'] === '') {
        $errors[] = 'Album name is required.';
    }

    // (int) turns the submitted text into a number; isset() checks that an
    // artist with that id really exists.
    if (!isset($artistNames[(int) $old['artist_id']])) {
        $errors[] = 'Choose an artist.';
    }
    if ($old['genre_id'] !== '' && !isset($genreNames[(int) $old['genre_id']])) {
        $errors[] = 'Choose a valid genre.';
    }

    // Optional fields are only validated when something was entered.
    if ($old['release_date'] !== '' && !DateTime::createFromFormat('Y-m-d', $old['release_date'])) {
        $errors[] = 'Release date is not a valid date.';
    }
    if ($old['total_tracks'] !== '' && filter_var($old['total_tracks'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors[] = 'Total tracks must be a whole number of 1 or more.';
    }

    if (!$errors) {
        $coverPath = null;
        try {
            // Store the cover (if any) and get back the path to save. See includes/upload.php.
            $coverPath = save_image('cover_art', 'albums');

            $stmt = $pdo->prepare(
                'INSERT INTO albums (album_name, release_date, artist_id, genre_id, total_tracks, cover_art)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            // The values line up, in order, with the six "?" placeholders above.
            // Empty optional fields are saved as NULL.
            $stmt->execute([
                $old['album_name'],
                $old['release_date'] !== '' ? $old['release_date'] : null,
                (int) $old['artist_id'],
                $old['genre_id'] !== '' ? (int) $old['genre_id'] : null,
                $old['total_tracks'] !== '' ? (int) $old['total_tracks'] : null,
                $coverPath,
            ]);

            // Redirect after saving so a page refresh cannot add the album twice.
            header('Location: add_album.php?added=' . urlencode($old['album_name']));
            exit;
        } catch (PDOException $e) {
            if ($coverPath !== null) {
                @unlink(__DIR__ . '/../' . $coverPath);
            }
            $errors[] = 'Could not save the album. Please try again.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// ---------- HTML starts here ----------
$pageTitle = 'Add Album';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Album</li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><i class="bi bi-disc"></i> Add Album</h1>

                <?php // An album needs an artist, so with zero artists we show a hint instead of the form. ?>
                <?php if (!$artists): ?>
                    <div class="alert alert-info mb-0">
                        No artists yet. <a href="add_artist.php" class="alert-link">Add an artist first.</a>
                    </div>
                <?php else: ?>

                    <?php if (isset($_GET['added'])): ?>
                        <div class="alert alert-success">Added <?= htmlspecialchars($_GET['added']) ?>.</div>
                    <?php endif; ?>

                    <?php require __DIR__ . '/../includes/errors.php'; ?>

                    <?php // enctype="multipart/form-data" is required for the cover image upload. ?>
                    <form method="post" action="add_album.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="album_name" class="form-label">Album name</label>
                                <input type="text" class="form-control" id="album_name" name="album_name"
                                       value="<?= htmlspecialchars($old['album_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="artist_id" class="form-label">Artist</label>
                                <select class="form-select" id="artist_id" name="artist_id" required>
                                    <option value="">-- choose --</option>
                                    <?php // One <option> per artist. "selected" re-picks the previous choice when the form is shown again after an error. ?>
                                    <?php foreach ($artists as $artist): ?>
                                        <option value="<?= $artist['artist_id'] ?>" <?= (int) $old['artist_id'] === (int) $artist['artist_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($artist['artist_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="genre_id" class="form-label">Genre <span class="text-body-secondary">(optional)</span></label>
                                <select class="form-select" id="genre_id" name="genre_id">
                                    <option value="">-- none --</option>
                                    <?php foreach ($genres as $genre): ?>
                                        <option value="<?= $genre['genre_id'] ?>" <?= (int) $old['genre_id'] === (int) $genre['genre_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($genre['genre_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="release_date" class="form-label">Release date <span class="text-body-secondary">(optional)</span></label>
                                <input type="date" class="form-control" id="release_date" name="release_date"
                                       value="<?= htmlspecialchars($old['release_date']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="total_tracks" class="form-label">Total tracks <span class="text-body-secondary">(optional)</span></label>
                                <input type="number" class="form-control" id="total_tracks" name="total_tracks" min="1"
                                       value="<?= htmlspecialchars($old['total_tracks']) ?>">
                            </div>
                            <div class="col-12">
                                <label for="cover_art" class="form-label">Cover art <span class="text-body-secondary">(optional)</span></label>
                                <input type="file" class="form-control" id="cover_art" name="cover_art" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Album</button>
                            </div>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
