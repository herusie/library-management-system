<?php
declare(strict_types=1);

// Cover images.
//  - On AWS: S3_BUCKET is set in the PHP-FPM settings, the picture is saved in the
//    private S3 bucket (folder covers/) using the server's IAM role (no keys in code).
//  - On a laptop (XAMPP): S3_BUCKET is not set, so the picture is saved in uploads/.

function s3_enabled(): bool
{
    return (getenv('S3_BUCKET') ?: '') !== '' && is_file(__DIR__ . '/vendor/autoload.php');
}

function s3_client(): Aws\S3\S3Client
{
    static $client = null;
    if ($client === null) {
        require_once __DIR__ . '/vendor/autoload.php';
        $client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => getenv('S3_REGION') ?: 'ap-southeast-1',
        ]);
    }
    return $client;
}

// Saves an uploaded cover image. Returns the saved file name,
// or null if no file was chosen. Throws RuntimeException on a bad file.
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

    if (s3_enabled()) {
        try {
            s3_client()->putObject([
                'Bucket'      => getenv('S3_BUCKET'),
                'Key'         => 'covers/' . $name,
                'SourceFile'  => $file['tmp_name'],
                'ContentType' => $mime,
            ]);
        } catch (Throwable $ex) {
            error_log('S3 upload failed: ' . $ex->getMessage());
            throw new RuntimeException('The image could not be saved.');
        }
        return $name;
    }

    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return $name;
}

// Address of a saved cover for an <img> tag.
// S3: a temporary link (valid 15 minutes) because the bucket is private.
function cover_url(?string $name): string
{
    if ($name === null || $name === '') {
        return '';
    }
    if (s3_enabled()) {
        try {
            $cmd = s3_client()->getCommand('GetObject', [
                'Bucket' => getenv('S3_BUCKET'),
                'Key'    => 'covers/' . $name,
            ]);
            return (string) s3_client()->createPresignedRequest($cmd, '+15 minutes')->getUri();
        } catch (Throwable $ex) {
            error_log('S3 link failed: ' . $ex->getMessage());
            return '';
        }
    }
    return 'uploads/' . rawurlencode($name);
}
