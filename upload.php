<?php
declare(strict_types=1);

// Saves an uploaded cover image. Returns the saved file name,
// or null if no file was chosen. Throws RuntimeException on a bad file.
// On AWS, this is the only function to change (send the file to S3 instead).
function save_cover(array $file): ?string
{
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($code !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('The image must be 2 MB or smaller.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return $name;
}