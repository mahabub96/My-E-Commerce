<?php

namespace App\Helpers;

/**
 * Upload - File Upload Handler
 * 
 * Static utility class for handling file uploads securely.
 * Validates file type (MIME), size, and generates unique filenames.
 * Stores files in public/assets/{path}/ directory.
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
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
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
     * @param string $path Subdirectory in public/assets/ (e.g., 'products', 'categories')
     * 
     * @return string|false Relative file path (e.g., 'products/1234567890_abc123.jpg') or false on error
     * 
     * @example
     *     $path = Upload::store($_FILES['image'], 'products');
     *     // Returns: 'products/1234567890_abc123.jpg'
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

        $validation = self::validate($file, $defaultRules);
        if (!$validation['valid']) {
            return false;
        }

        // Use storage/uploads outside webroot to avoid direct execution
        $storageDir = __DIR__ . '/../../storage/uploads/' . trim($path, '/');

        // Create directory if not exists
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0750, true)) {
                return false;
            }
        }

        // Use finfo to determine MIME and validate against strict whitelist
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($file['tmp_name']);

        // Block SVG and other XSS vectors with strict MIME validation
        if (!in_array($detected, $defaultRules['allowed_mime_types'], true)) {
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Disallow known dangerous extensions even if MIME matches
        $dangerous = ['php', 'php7', 'phtml', 'phar', 'exe', 'sh', 'pl', 'py', 'svg', 'xml', 'html', 'htm', 'js'];
        if (in_array($ext, $dangerous, true)) {
            return false;
        }

        // Reprocess image to strip EXIF and ensure it's a valid image
        try {
            $imageInfo = @getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return false; // Not a valid image
            }

            // Create clean image from uploaded file
            $image = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $image = @imagecreatefromjpeg($file['tmp_name']);
                    break;
                case IMAGETYPE_PNG:
                    $image = @imagecreatefrompng($file['tmp_name']);
                    break;
                case IMAGETYPE_GIF:
                    $image = @imagecreatefromgif($file['tmp_name']);
                    break;
                case IMAGETYPE_WEBP:
                    $image = @imagecreatefromwebp($file['tmp_name']);
                    break;
                default:
                    return false;
            }

            if (!$image) {
                return false;
            }

            // Generate unique filename
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $fullPath = $storageDir . '/' . $filename;

            // Save reprocessed image (strips EXIF and embedded payloads)
            $saved = false;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $saved = @imagejpeg($image, $fullPath, 90);
                    break;
                case IMAGETYPE_PNG:
                    $saved = @imagepng($image, $fullPath, 8);
                    break;
                case IMAGETYPE_GIF:
                    $saved = @imagegif($image, $fullPath);
                    break;
                case IMAGETYPE_WEBP:
                    $saved = @imagewebp($image, $fullPath, 90);
                    break;
            }

            @imagedestroy($image);

            if (!$saved) {
                return false;
            }

            // Set safe file permissions (owner read/write)
            @chmod($fullPath, 0640);

            // Return relative storage path for DB
            return trim($path, '/') . '/' . $filename;
        } catch (\Throwable $e) {
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
        $fullPath = __DIR__ . '/../../storage/uploads/' . ltrim($filePath, '/');

        if (!file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code (UPLOAD_ERR_*)
     * 
     * @return string Error description
     */
    private static function getUploadError(int $errorCode): string
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
