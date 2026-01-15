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

        // Validate MIME type
        if (isset($rules['mimes'])) {
            $allowed = explode(',', $rules['mimes']);
            $allowed = array_map('trim', $allowed);

            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowed)) {
                return [
                    'valid' => false,
                    'error' => 'File type not allowed. Allowed types: ' . $rules['mimes']
                ];
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
    public static function store(array $file, string $path = 'uploads'): string | false
    {
        // Validate before storing
        $validation = self::validate($file);
        if (!$validation['valid']) {
            return false;
        }

        // Prepare directory
        $directory = __DIR__ . '/../../public/assets/' . trim($path, '/');

        // Create directory if not exists
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return false;
            }
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        // Full path for storage
        $fullPath = $directory . '/' . $filename;

        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return false;
        }

        // Return relative path (for storing in database)
        return trim($path, '/') . '/' . $filename;
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
        $fullPath = __DIR__ . '/../../public/assets/' . ltrim($filePath, '/');

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
        $fullPath = __DIR__ . '/../../public/assets/' . ltrim($filePath, '/');

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
