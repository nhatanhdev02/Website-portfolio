<?php

namespace App\Exceptions\Admin;

use Exception;

class FileUploadException extends Exception
{
    /**
     * Create exception for file size exceeded
     *
     * @param int $maxSize
     * @return static
     */
    public static function fileSizeExceeded(int $maxSize): self
    {
        $maxSizeMB = $maxSize / 1024 / 1024;
        return new self("File size exceeds maximum allowed size of {$maxSizeMB}MB", 413);
    }

    /**
     * Create exception for invalid file type
     *
     * @param array $allowedTypes
     * @return static
     */
    public static function invalidFileType(array $allowedTypes): self
    {
        $types = implode(', ', $allowedTypes);
        return new self("Invalid file type. Allowed types: {$types}", 422);
    }

    /**
     * Create exception for corrupted file
     *
     * @return static
     */
    public static function corruptedFile(): self
    {
        return new self('File appears to be corrupted or invalid', 422);
    }

    /**
     * Create exception for storage failure
     *
     * @return static
     */
    public static function storageFailed(): self
    {
        return new self('Failed to store file on server', 500);
    }

    /**
     * Create exception for security violation
     *
     * @return static
     */
    public static function securityViolation(): self
    {
        return new self('File upload blocked for security reasons', 403);
    }

    /**
     * Create exception for missing file
     *
     * @return static
     */
    public static function missingFile(): self
    {
        return new self('No file was uploaded', 400);
    }
}
