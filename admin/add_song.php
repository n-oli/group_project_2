<?php
/**
 * admin/add_song.php - form to add a song (title, artist, optional album and
 * genre, release date, and length).
 *
 * All three admin "add" pages follow the same recipe:
 * 1. require_user('admin') - only admins get past this line.
 * 2. Load the choices for the dropdowns from the database.
 * 3. If the form was submitted (POST): tidy the input, validate it, and
 * either insert the row and redirect, or collect messages in $errors.
 * 4. Print the form. $old refills the fields after an error.
 *
 * Things that are special about songs:
 * - The length is typed as minutes + seconds but stored as total seconds.
 * - If an album is chosen it must belong to the chosen artist.
 * - Audio is required and saved under uploads/songs.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/upload.php';
$user = require_user('admin');

// Choices for the three dropdowns. Albums also fetch their artist so the
// dropdown can read "Album - Artist" and so we can check they match.
$artists = $pdo->query('SELECT artist_id, artist_name FROM artists ORDER BY artist_name')->fetchAll();
$genres  = $pdo->query('SELECT genre_id, genre_name FROM genres ORDER BY genre_name')->fetchAll();
$albums  = $pdo->query(
    'SELECT al.album_id, al.album_name, al.artist_id, a.artist_name
     FROM albums al
     JOIN artists a ON a.artist_id = al.artist_id
     ORDER BY a.artist_name, al.album_name'
)->fetchAll();

// [id => value] lookups for checking that submitted ids are real.
// Anyone can edit a dropdown in their browser, so the server must re-check.
$artistNames  = array_column($artists, 'artist_name', 'artist_id');
$genreNames   = array_column($genres, 'genre_name', 'genre_id');
$albumArtists = array_column($albums, 'artist_id', 'album_id'); // album_id => artist who made it

// $errors = messages to show; $old = what the admin typed (to refill the form).
// The keys of $old are exactly the form's field names.
$errors = [];
$old = ['song_title' => '', 'artist_id' => '', 'album_id' => '', 'genre_id' => '',
        'release_date' => '', 'minutes' => '', 'seconds' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !valid_csrf_token()) {
    $errors[] = 'Your form has expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read every field named in $old from the submitted form, trimming spaces.
    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if ($old['song_title'] === '') {
        $errors[] = 'Song title is required.';
    }
    if (!isset($artistNames[(int) $old['artist_id']])) {
        $errors[] = 'Choose an artist.';
    }

    // Album is optional (a single has none). When one is chosen it must exist
    // and must be by the same artist as the song.
    if ($old['album_id'] !== '') {
        if (!isset($albumArtists[(int) $old['album_id']])) {
            $errors[] = 'Choose a valid album.';
        } elseif ((int) $albumArtists[(int) $old['album_id']] !== (int) $old['artist_id']) {
            $errors[] = 'That album belongs to a different artist.';
        }
    }
    if ($old['genre_id'] !== '' && !isset($genreNames[(int) $old['genre_id']])) {
        $errors[] = 'Choose a valid genre.';
    }
    if ($old['release_date'] !== '' && !DateTime::createFromFormat('Y-m-d', $old['release_date'])) {
        $errors[] = 'Release date is not a valid date.';
    }

    // filter_var(..., FILTER_VALIDATE_INT) returns the whole number, or false if
    // the text is not a whole number in the allowed range. Note "=== false":
    // 0 is a valid answer (0 minutes 45 seconds) and must not count as a failure.
    $minutes = filter_var($old['minutes'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $seconds = filter_var($old['seconds'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 59]]);
    if ($minutes === false || $seconds === false || $minutes * 60 + $seconds === 0) {
        $errors[] = 'Enter a length like 3 minutes 35 seconds (seconds 0-59).';
    }

    if (!$errors) {
        $audioPath = null;
        try {
            // Stores the audio file first
            $audioPath = save_audio('audio_file', 'songs');
            if ($audioPath === null) {
                throw new RuntimeException('Choose an audio file.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO songs (song_title, artist_id, album_id, genre_id, release_date, song_duration, audio_file)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $old['song_title'],
                (int) $old['artist_id'],
                $old['album_id'] !== '' ? (int) $old['album_id'] : null,
                $old['genre_id'] !== '' ? (int) $old['genre_id'] : null,
                $old['release_date'] !== '' ? $old['release_date'] : null,
                $minutes * 60 + $seconds,
                $audioPath,
            ]);

            header('Location: add_song.php?added=' . urlencode($old['song_title']));
            exit;
        } catch (PDOException $e) {
            // Keep a new upload only when its song row was saved.
            if ($audioPath !== null) {
                @unlink(__DIR__ . '/../' . $audioPath);
            }
            $errors[] = 'Could not save the song. Please try again.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage(); // upload problems from save_audio()
        }
    }
}

// ---------- HTML starts here ----------
$pageTitle = 'Add Song';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Song</li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><i class="bi bi-music-note"></i> Add Song</h1>

                <?php // A song needs an artist, so with zero artists we show a hint instead of the form. ?>
                <?php if (!$artists): ?>
                    <div class="alert alert-info mb-0">
                        No artists yet. <a href="add_artist.php" class="alert-link">Add an artist first.</a>
                    </div>
                <?php else: ?>

                    <?php if (isset($_GET['added'])): ?>
                        <div class="alert alert-success">Added <?= htmlspecialchars($_GET['added']) ?>.</div>
                    <?php endif; ?>

                    <?php require __DIR__ . '/../includes/errors.php'; ?>

                    <form method="post" action="add_song.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="audio_file" class="form-label">Audio file</label>
                                <input type="file" class="form-control" id="audio_file" name="audio_file" accept="audio/*" required>
                                <div class="form-text">MP3, OGG or WAV, up to 20 MB.</div>
                            </div>
                            <div class="col-12">
                                <label for="song_title" class="form-label">Song title</label>
                                <input type="text" class="form-control" id="song_title" name="song_title"
                                       value="<?= htmlspecialchars($old['song_title']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="artist_id" class="form-label">Artist</label>
                                <select class="form-select" id="artist_id" name="artist_id" required>
                                    <option value="">-- choose --</option>
                                    <?php foreach ($artists as $artist): ?>
                                        <option value="<?= $artist['artist_id'] ?>" <?= (int) $old['artist_id'] === (int) $artist['artist_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($artist['artist_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="album_id" class="form-label">Album <span class="text-body-secondary">(optional)</span></label>
                                <select class="form-select" id="album_id" name="album_id">
                                    <option value="">-- single / no album --</option>
                                    <?php // Lists every album of every artist; the server rejects a mismatch (see the album check above). ?>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?= $album['album_id'] ?>" <?= (int) $old['album_id'] === (int) $album['album_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($album['album_name'] . ' — ' . $album['artist_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Must be by the same artist.</div>
                            </div>
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label for="release_date" class="form-label">Release date <span class="text-body-secondary">(optional)</span></label>
                                <input type="date" class="form-control" id="release_date" name="release_date"
                                       value="<?= htmlspecialchars($old['release_date']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="minutes" class="form-label">Length</label>
                                <?php // Two number boxes side by side: minutes and seconds. ?>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="minutes" name="minutes" min="0"
                                           value="<?= htmlspecialchars($old['minutes']) ?>" required aria-label="Minutes">
                                    <span class="input-group-text">min</span>
                                    <input type="number" class="form-control" name="seconds" min="0" max="59"
                                           value="<?= htmlspecialchars($old['seconds']) ?>" required aria-label="Seconds">
                                    <span class="input-group-text">sec</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Song</button>
                            </div>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
// this is helper javascirpt to extract length in seconds of uploaded audio file   
// When a file is chosen, read its length and fill in the minutes/seconds boxes.
document.getElementById('audio_file').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const audio = new Audio();
    audio.preload = 'metadata';
    audio.src = URL.createObjectURL(file);

    audio.addEventListener('loadedmetadata', function () {
        const total = Math.round(audio.duration);
        document.getElementById('minutes').value = Math.floor(total / 60);
        document.querySelector('input[name="seconds"]').value = total % 60;
        URL.revokeObjectURL(audio.src);
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
