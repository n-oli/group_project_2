<?php
// Saves an uploaded image into uploads/<folder>/ and returns its path for the database,
// e.g. "uploads/artists/3f9a1c0b7d2e4a51.jpg". Returns null when no file was chosen.

function save_image(string $field, string $folder): ?string
{
    $file = $_FILES[$field] ?? null;
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The upload failed. Try a smaller image.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Images must be 2 MB or smaller.');
    }

    // Check the file's contents, not its name: renaming notes.txt to notes.jpg won't pass
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

    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    // Random name so two uploads called "cover.jpg" never overwrite each other
    $name = bin2hex(random_bytes(8)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Could not save the image.');
    }

    return "uploads/$folder/$name";
}
