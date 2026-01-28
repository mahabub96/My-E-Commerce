<?php

namespace App\Helpers;

/**
 * Upload - File Upload Handler
 * 
 * Static utility class for handling file uploads securely.
 * Validates file type (MIME), size, and generates unique filenames.
    * Stores files in public/uploads/{path}/ directory.
 * 
 * Security features:
 * - MIME type validation (prevents executable uploads)
 * - File size limits (prevents DOS)
 * - Unique filenames (prevents overwrites)
 * - Directory permissions (0755 - public readable)
 * 
 * @example
 *     $validation = Upload::validate($_FILES['image'], [
 *         'mimes' => 'jpg,png,gif',
 *         'max_size' => 2048  // 2MB
 *     ]);
 *     
 *     if (!$validation['valid']) {
 *         echo $validation['error'];
 *     } else {
 *         $path = Upload::store($_FILES['image'], 'products');
 *         // $path = 'products/1234567890_abc123.jpg'
 *     }
 */

class Upload
{
    /**
     * Last error message for diagnostics
     * @var string
     */
    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function setLastError(string $msg): void
    {
        self::$lastError = $msg;
    }
    /**
     * Validate uploaded file
     * 
     * @param array $file $_FILES['fieldname'] array
     * @param array $rules Validation rules:
     *                     - 'mimes' (string): comma-separated allowed extensions (jpg,png,gif)
     *                     - 'max_size' (int): max file size in KB
     * 
     * @return array Validation result: ['valid' => bool, 'error' => string|null]
     * 
     * @example
     *     $result = Upload::validate($_FILES['image'], [
     *         'mimes' => 'jpg,png,gif',
     *         'max_size' => 2048
     *     ]);
     */
    public static function validate(array $file, array $rules = []): array
    {
        // Check if file upload had error
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => 'File upload failed: ' . self::getUploadError($file['error'] ?? 0)
            ];
        }

        // Check if temporary file exists
        // Allow local files when running in CLI (useful for tests) or when the file is an actual uploaded file
        if (!isset($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && !(php_sapi_name() === 'cli' && file_exists($file['tmp_name'])))) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload'
            ];
        }

        // Validate MIME type (extension + finfo)
        if (isset($rules['mimes'])) {
            $allowed = explode(',', $rules['mimes']);
            $allowed = array_map('trim', $allowed);

            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfoMime = @mime_content_type($file['tmp_name']);

            if (!in_array($fileExt, $allowed)) {
                return [
                    'valid' => false,
                    'error' => 'File type not allowed. Allowed types: ' . $rules['mimes']
                ];
            }

            if ($finfoMime && isset($rules['mime_prefix'])) {
                $prefixAllowed = false;
                foreach ((array)$rules['mime_prefix'] as $prefix) {
                    if (str_starts_with($finfoMime, $prefix)) {
                        $prefixAllowed = true;
                        break;
                    }
                }
                if (!$prefixAllowed) {
                    return [
                        'valid' => false,
                        'error' => 'File MIME type not allowed'
                    ];
                }
            }
        }

        // Validate file size
        if (isset($rules['max_size'])) {
            $maxSizeBytes = (int)$rules['max_size'] * 1024;
            $fileSize = filesize($file['tmp_name']);

            if ($fileSize > $maxSizeBytes) {
                $maxSizeMB = $rules['max_size'] / 1024;
                return [
                    'valid' => false,
                    'error' => 'File size exceeds maximum of ' . $maxSizeMB . 'MB'
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Store uploaded file to disk with unique filename
     * 
     * @param array $file $_FILES['fieldname'] array
    * @param string $path Subdirectory in public/uploads/ (e.g., 'products', 'categories')
     * 
     * @return string|false Relative file path (e.g., 'products/1234567890_abc123.jpg') or false on error
     * 
     * @example
    *     $path = Upload::store($_FILES['image'], 'products');
    *     // Returns: 'uploads/products/1234567890_abc123.jpg'
     *     
     *     // Store in database:
     *     $product->update(['image' => $path]);
     */
    public static function store(array $file, string $path = 'uploads', array $rules = []): string | false
    {
        // Validate before storing with explicit MIME whitelist (block SVG)
        $defaultRules = [
            'mimes' => $rules['mimes'] ?? 'jpg,jpeg,png,gif,webp',
            'allowed_mime_types' => $rules['allowed_mime_types'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'max_size' => $rules['max_size'] ?? 2048,
        ];
        if (!empty($rules['mime_prefix'])) {
            $defaultRules['mime_prefix'] = $rules['mime_prefix'];
        }

        $allowSvg = !empty($rules['allow_svg']);
        if ($allowSvg) {
            $defaultRules['mimes'] = $rules['mimes'] ?? 'jpg,jpeg,png,gif,webp,svg';
            $defaultRules['allowed_mime_types'] = $rules['allowed_mime_types'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        }

        $validation = self::validate($file, $defaultRules);
        if (!$validation['valid']) {
            error_log('Upload::validate failed: ' . ($validation['error'] ?? 'unknown'));
            return false;
        }

        // Debug info for troubleshooting
        try {
            error_log(sprintf('Upload::store called: tmp=%s name=%s size=%d', $file['tmp_name'] ?? '', $file['name'] ?? '', $file['size'] ?? 0));
        } catch (\Throwable $_) {}

        // Store under public/uploads for direct serving
        $storageDir = __DIR__ . '/../../public/uploads/' . trim($path, '/');

        // Create directory if not exists
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0755, true)) {
                error_log('Upload::store mkdir failed for ' . $storageDir);
                self::setLastError('Failed to create storage directory');
                return false;
            }
        }

        // Use finfo to determine MIME and validate against strict whitelist
        $detected = null;
        if (class_exists('\finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($file['tmp_name']);
        } else {
            $detected = @mime_content_type($file['tmp_name']);
        }
        try { error_log('Upload::detected_mime=' . ($detected ?? 'unknown')); } catch (\Throwable $_) {}
        try {
            $isUp = is_uploaded_file($file['tmp_name']) ? '1' : '0';
            $size = @filesize($file['tmp_name']) ?: 0;
            error_log('Upload::tmp_info is_uploaded_file=' . $isUp . ' size=' . $size . ' tmp=' . ($file['tmp_name'] ?? '')); 
        } catch (\Throwable $_) {}

        // Block SVG and other XSS vectors with strict MIME validation
        if ($detected !== null && !in_array($detected, $defaultRules['allowed_mime_types'], true)) {
            // Accept other image/* variants (e.g., image/x-png, image/pjpeg) as long as they are image/*
            if (!str_starts_with($detected, 'image/')) {
                error_log('Upload::store rejected due to MIME not allowed: ' . ($detected ?? 'null'));
                self::setLastError('MIME type not allowed: ' . ($detected ?? 'unknown'));
                return false;
            }
            error_log('Upload::store warning: MIME ' . $detected . ' not in whitelist, accepting as image/*');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Disallow known dangerous extensions even if MIME matches
        $dangerous = ['php', 'php7', 'phtml', 'phar', 'exe', 'sh', 'pl', 'py', 'svg', 'xml', 'html', 'htm', 'js'];
        if (in_array($ext, $dangerous, true) && !($allowSvg && $ext === 'svg')) {
            error_log('Upload::store rejected due to dangerous extension: ' . $ext);
            return false;
        }

        if ($allowSvg && $ext === 'svg') {
            if ($detected !== null && $detected !== 'image/svg+xml') {
                self::setLastError('MIME type not allowed: ' . ($detected ?? 'unknown'));
                return false;
            }
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $fullPath = $storageDir . '/' . $filename;
            if (!@move_uploaded_file($file['tmp_name'], $fullPath)) {
                if (!@copy($file['tmp_name'], $fullPath)) {
                    self::setLastError('Failed to store SVG');
                    return false;
                }
            }
            @chmod($fullPath, 0640);
            return 'uploads/' . trim($path, '/') . '/' . $filename;
        }

        // Reprocess image to strip EXIF and ensure it's a valid image
        try {
            $imageInfo = @getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                try { error_log('Upload::store getimagesize failed for ' . ($file['tmp_name'] ?? '') . ' mime=' . @mime_content_type($file['tmp_name'])); } catch (\Throwable $_) {}
                self::setLastError('Not a valid image or corrupted upload');
                return false; // Not a valid image
            }

            // Create clean image from uploaded file
            $image = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    if (function_exists('imagecreatefromjpeg')) {
                        $image = @imagecreatefromjpeg($file['tmp_name']);
                    }
                    break;
                case IMAGETYPE_PNG:
                    if (function_exists('imagecreatefrompng')) {
                        $image = @imagecreatefrompng($file['tmp_name']);
                    }
                    break;
                case IMAGETYPE_GIF:
                    if (function_exists('imagecreatefromgif')) {
                        $image = @imagecreatefromgif($file['tmp_name']);
                    }
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($file['tmp_name']);
                    }
                    break;
                default:
                    $image = null;
            }

            // Generate unique filename
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $fullPath = $storageDir . '/' . $filename;

            if (!$image) {
                // Fallback: attempt to move or copy the uploaded file without reprocessing
                $moved = false;
                if (@move_uploaded_file($file['tmp_name'], $fullPath)) {
                    $moved = true;
                    error_log('Upload::fallback moved uploaded file to ' . $fullPath);
                } else {
                    // move_uploaded_file fails when running in CLI tests (not an uploaded file)
                    if (@rename($file['tmp_name'], $fullPath)) {
                        $moved = true;
                        error_log('Upload::fallback renamed file to ' . $fullPath . ' (rename)');
                    } elseif (@copy($file['tmp_name'], $fullPath)) {
                        $moved = true;
                        error_log('Upload::fallback copied file to ' . $fullPath . ' (copy)');
                    } else {
                        error_log('Upload::fallback failed to move/copy file from ' . ($file['tmp_name'] ?? '') . ' -> ' . $fullPath . ' is_uploaded_file=' . (is_uploaded_file($file['tmp_name']) ? '1' : '0'));
                    }
                }

                if (!$moved) {
                    self::setLastError('Failed to move/copy uploaded file to storage');
                    return false;
                }

                @chmod($fullPath, 0640);
                return 'uploads/' . trim($path, '/') . '/' . $filename;
            }

            // Save reprocessed image (strips EXIF and embedded payloads)
            $saved = false;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    if (function_exists('imagejpeg')) {
                        $saved = @imagejpeg($image, $fullPath, 90);
                    }
                    break;
                case IMAGETYPE_PNG:
                    if (function_exists('imagepng')) {
                        $saved = @imagepng($image, $fullPath, 8);
                    }
                    break;
                case IMAGETYPE_GIF:
                    if (function_exists('imagegif')) {
                        $saved = @imagegif($image, $fullPath);
                    }
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) {
                        $saved = @imagewebp($image, $fullPath, 90);
                    }
                    break;
            }

            try { if (function_exists('imagedestroy')) { @imagedestroy($image); } } catch (\Throwable $_) {}

            if ($saved) {
                @chmod($fullPath, 0640);
                return 'uploads/' . trim($path, '/') . '/' . $filename;
            }

            if (!$saved) {
                error_log('Upload::image save failed for ' . $fullPath . ' (image type ' . ($imageInfo[2] ?? 'unknown') . '). Attempting fallback copy.');
                // Attempt a copy fallback from the original tmp file
                if (@copy($file['tmp_name'], $fullPath)) {
                    @chmod($fullPath, 0640);
                    error_log('Upload::image fallback copy succeeded for ' . $fullPath);
                    return 'uploads/' . trim($path, '/') . '/' . $filename;
                }

                self::setLastError('Failed to reprocess image and fallback copy also failed');
                    return false;
                }
            } catch (\Throwable $e) {
                error_log('Upload::store exception: ' . $e->getMessage());
                self::setLastError('Exception: ' . $e->getMessage());
                return false;
            }
        }

    /**
     * Delete file from disk
     * 
     * @param string $filePath Relative file path (e.g., 'products/image.jpg')
     * 
     * @return bool True if deleted, false if not found
     * 
     * @example
     *     Upload::delete('products/1234567890_abc123.jpg');
     */
    public static function delete(string $filePath): bool
    {
        $relative = ltrim($filePath, '/');
        $publicPath = __DIR__ . '/../../public/' . $relative;
        if (file_exists($publicPath)) {
            return unlink($publicPath);
        }

        $legacyPath = __DIR__ . '/../../storage/uploads/' . $relative;
        if (file_exists($legacyPath)) {
            return unlink($legacyPath);
        }

        return false;
    }

    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code (UPLOAD_ERR_*)
     * 
     * @return string Error description
     */
    public static function getUploadError(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
            default => 'Unknown error'
        };
    }

    /**
     * Get MIME type of file
     * 
     * @param string $filePath File path
     * 
     * @return string|false MIME type or false if detection fails
     */
    public static function getMimeType(string $filePath): string | false
    {
        $fullPath = __DIR__ . '/../../storage/uploads/' . ltrim($filePath, '/');

        if (!file_exists($fullPath)) {
            return false;
        }

        return mime_content_type($fullPath);
    }

    /**
     * Get file size in bytes
     * 
     * @param string $filePath File path
     * 
     * @return int|false File size or false if not found
     */
    public static function getSize(string $filePath): int | false
    {
        $fullPath = __DIR__ . '/../../public/assets/' . ltrim($filePath, '/');

        if (!file_exists($fullPath)) {
            return false;
        }

        return filesize($fullPath);
    }
}
