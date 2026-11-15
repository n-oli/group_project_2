<?php
/**
 * includes/upload.php - safely stores an image uploaded through a form.
 *
 * HOW TO USE IT
 * - The form needs enctype="multipart/form-data" and an <input type="file">.
 * - Call save_image('<input name>', '<sub-folder>') inside a try block.
 * - It returns the path to save in the database, or null if no file was chosen.
 * - If the file is bad it throws a RuntimeException whose message can be
 * shown to the user (see admin/add_artist.php for an example).
 *
 * Used for artist photos (uploads/artists/) and album covers (uploads/albums/).
 */

// Saves an uploaded image into uploads/<folder>/ and returns its path for the database,
// e.g. "uploads/artists/3f9a1c0b7d2e4a51.jpg". Returns null when no file was chosen.

// $field  = the "name" of the file input in the form, e.g. 'cover_art'
// $folder = sub-folder of uploads/ to save into, e.g. 'albums'
// returns = e.g. "uploads/albums/3f9a1c0b7d2e4a51.jpg", or null (no file chosen)
function save_image(string $field, string $folder): ?string
{
    // PHP puts uploaded files in $_FILES. Images are optional in our forms, so
    // "no file chosen" is not an error; we just return null.
    $file = $_FILES[$field] ?? null;
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Any other error code means the upload itself broke (usually the file is
    // larger than PHP's upload_max_filesize setting).
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The upload failed. Try a smaller image.');
    }

    // Our own limit: 2 MB (2 x 1024 x 1024 bytes).
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Images must be 2 MB or smaller.');
    }

    // Check the file's contents, not its name: renaming notes.txt to notes.jpg won't pass
    // getimagesize() opens the file and reads its real type. $info[2] is a number
    // that says which image type it is. The @ hides PHP's warning for non-images;
    // we handle that case ourselves just below.
    $info = @getimagesize($file['tmp_name']);
    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    if ($info === false || !isset($extensions[$info[2]])) {
        throw new RuntimeException('Only JPG, PNG, GIF or WebP images are allowed.');
    }

    // Create uploads/<folder>/ the first time it is needed.
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    // Random name so two uploads called "cover.jpg" never overwrite each other
    $name = bin2hex(random_bytes(8)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Could not save the image.');
    }

    // This relative path is what gets stored in the database. To show the image
    // later:  <img src="(app_url of that path)">
    return "uploads/$folder/$name";
}

// Saves an uploaded audio file into uploads/<folder>/ and returns its path for the database,
// e.g. "uploads/songs/3f9a1c0b7d2e4a51.mp3". Returns null when no file was chosen.
function save_audio(string $field, string $folder): ?string
{
    $file = $_FILES[$field] ?? null;
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The upload failed. The file might be too large.');
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        throw new RuntimeException('Audio files must me equal to 20 MB or smaller.');
    }

    // Check the file's real contents, not its name
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    // maping of audio detected audio file formats to filename extensions
    $extensions = [
        'audio/mpeg'  => 'mp3',
        'audio/mp3'   => 'mp3',
        'audio/ogg'   => 'ogg',
        'audio/wav'   => 'wav',
        'audio/x-wav' => 'wav',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Only MP3, OGG or WAV audio files are allowed.');
    }
    // checks if our upload directory exist in server else creates new folder names uploads
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    // Using Random name so two uploads with the same filename never overwrite each other
    $name = bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Could not save the audio file.');
    }
    // finnaly returns path of file saved for database
    return "uploads/$folder/$name";
}
