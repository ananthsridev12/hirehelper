<?php

namespace App\Core;

class Upload
{
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Validates and stores an uploaded image, returning the path to store
     * in the database (relative to public/assets/), or null if no file
     * was sent. Throws on an invalid file so the caller can show a
     * specific error instead of silently dropping a bad upload.
     */
    public static function image(?array $file, string $subdir): ?string
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed. Please try a different file.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Image must be smaller than 5 MB.');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Only JPG, PNG or WEBP images are allowed.');
        }

        $dir = APP_ROOT . '/public/assets/uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        return 'uploads/' . trim($subdir, '/') . '/' . $filename;
    }
}
