<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Exceptions\Admin\FileUploadException;

class FileUploadService
{
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'txt'];
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_DOCUMENT_SIZE = 20 * 1024 * 1024; // 20MB

    /**
     * Upload image file
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param array $options
     * @return string
     * @throws FileUploadException
     */
    public function uploadImage(UploadedFile $file, string $directory, array $options = []): string
    {
        $this->validateImageFile($file);

        $filename = $this->generateSecureFilename($file);
        $path = $this->storeFile($file, $directory, $filename);

        // Optimize image if requested
        if ($options['optimize'] ?? true) {
            $this->optimizeImage($path);
        }

        $this->logUpload($file, $path, 'image');

        return Storage::url($path);
    }

    /**
     * Upload document file
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string
     * @throws FileUploadException
     */
    public function uploadDocument(UploadedFile $file, string $directory): string
    {
        $this->validateDocumentFile($file);

        $filename = $this->generateSecureFilename($file);
        $path = $this->storeFile($file, $directory, $filename);

        $this->logUpload($file, $path, 'document');

        return Storage::url($path);
    }

    /**
     * Delete file from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        try {
            // Remove the storage URL prefix to get the actual path
            $actualPath = str_replace('/storage/', 'public/', $path);

            if (Storage::exists($actualPath)) {
                $result = Storage::delete($actualPath);

                if ($result) {
                    Log::info('File deleted', [
                        'path' => $path,
                        'actual_path' => $actualPath
                    ]);
                }

                return $result;
            }

            return true; // File doesn't exist, consider it deleted
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get file info
     *
     * @param string $path
     * @return array|null
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            $actualPath = str_replace('/storage/', 'public/', $path);

            if (!Storage::exists($actualPath)) {
                return null;
            }

            return [
                'path' => $path,
                'size' => Storage::size($actualPath),
                'last_modified' => Storage::lastModified($actualPath),
                'mime_type' => Storage::mimeType($actualPath),
                'exists' => true
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get file info', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @throws FileUploadException
     */
    private function validateImageFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            throw new FileUploadException('Image size exceeds maximum allowed size of ' . (self::MAX_IMAGE_SIZE / 1024 / 1024) . 'MB');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_IMAGE_TYPES)) {
            throw new FileUploadException('Invalid image type. Allowed types: ' . implode(', ', self::ALLOWED_IMAGE_TYPES));
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            throw new FileUploadException('Invalid file content. Only images are allowed');
        }

        // Additional security check - verify it's actually an image
        if (!$this->isValidImage($file)) {
            throw new FileUploadException('File appears to be corrupted or is not a valid image');
        }
    }

    /**
     * Validate document file
     *
     * @param UploadedFile $file
     * @throws FileUploadException
     */
    private function validateDocumentFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_DOCUMENT_SIZE) {
            throw new FileUploadException('Document size exceeds maximum allowed size of ' . (self::MAX_DOCUMENT_SIZE / 1024 / 1024) . 'MB');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_DOCUMENT_TYPES)) {
            throw new FileUploadException('Invalid document type. Allowed types: ' . implode(', ', self::ALLOWED_DOCUMENT_TYPES));
        }

        // Check for executable files (security)
        if ($this->isExecutableFile($file)) {
            throw new FileUploadException('Executable files are not allowed');
        }
    }

    /**
     * Generate secure filename
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Store file to disk
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @return string
     * @throws FileUploadException
     */
    private function storeFile(UploadedFile $file, string $directory, string $filename): string
    {
        try {
            $path = $file->storeAs("public/{$directory}", $filename);

            if (!$path) {
                throw new FileUploadException('Failed to store file');
            }

            return $path;
        } catch (\Exception $e) {
            Log::error('File storage failed', [
                'directory' => $directory,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw new FileUploadException('Failed to store file: ' . $e->getMessage());
        }
    }

    /**
     * Optimize image (basic optimization)
     *
     * @param string $path
     */
    private function optimizeImage(string $path): void
    {
        try {
            $fullPath = Storage::path($path);

            // Basic optimization - you can extend this with more sophisticated image processing
            if (extension_loaded('gd')) {
                $this->optimizeWithGD($fullPath);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::warning('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Optimize image using GD library
     *
     * @param string $fullPath
     */
    private function optimizeWithGD(string $fullPath): void
    {
        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return;
        }

        $mimeType = $imageInfo['mime'];
        $image = null;

        // Create image resource based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($fullPath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($fullPath);
                }
                break;
        }

        if ($image) {
            // Save with compression
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($image, $fullPath, 85); // 85% quality
                    break;
                case 'image/png':
                    imagepng($image, $fullPath, 6); // Compression level 6
                    break;
                case 'image/webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($image, $fullPath, 85);
                    }
                    break;
            }

            imagedestroy($image);
        }
    }

    /**
     * Check if file is a valid image
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isValidImage(UploadedFile $file): bool
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if file is executable
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isExecutableFile(UploadedFile $file): bool
    {
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'sh'];
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, $dangerousExtensions);
    }

    /**
     * Log file upload
     *
     * @param UploadedFile $file
     * @param string $path
     * @param string $type
     */
    private function logUpload(UploadedFile $file, string $path, string $type): void
    {
        Log::info('File uploaded', [
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
