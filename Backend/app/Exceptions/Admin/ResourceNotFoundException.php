<?php

namespace App\Exceptions\Admin;

use Exception;

class ResourceNotFoundException extends Exception
{
    /**
     * Create exception for resource not found
     *
     * @param string $resource
     * @param mixed $identifier
     * @return static
     */
    public static function make(string $resource, mixed $identifier): self
    {
        return new self("The requested {$resource} with identifier '{$identifier}' was not found", 404);
    }

    /**
     * Create exception for hero content not found
     *
     * @return static
     */
    public static function heroNotFound(): self
    {
        return new self('Hero content not found', 404);
    }

    /**
     * Create exception for about content not found
     *
     * @return static
     */
    public static function aboutNotFound(): self
    {
        return new self('About content not found', 404);
    }

    /**
     * Create exception for service not found
     *
     * @param int $id
     * @return static
     */
    public static function serviceNotFound(int $id): self
    {
        return new self("Service with ID {$id} not found", 404);
    }

    /**
     * Create exception for project not found
     *
     * @param int $id
     * @return static
     */
    public static function projectNotFound(int $id): self
    {
        return new self("Project with ID {$id} not found", 404);
    }

    /**
     * Create exception for blog post not found
     *
     * @param int $id
     * @return static
     */
    public static function blogPostNotFound(int $id): self
    {
        return new self("Blog post with ID {$id} not found", 404);
    }

    /**
     * Create exception for contact message not found
     *
     * @param int $id
     * @return static
     */
    public static function contactMessageNotFound(int $id): self
    {
        return new self("Contact message with ID {$id} not found", 404);
    }

    /**
     * Create exception for contact info not found
     *
     * @return static
     */
    public static function contactInfoNotFound(): self
    {
        return new self('Contact information not found', 404);
    }

    /**
     * Create exception for system settings not found
     *
     * @param string $key
     * @return static
     */
    public static function settingNotFound(string $key): self
    {
        return new self("System setting '{$key}' not found", 404);
    }

    /**
     * Create exception for admin not found
     *
     * @param int $id
     * @return static
     */
    public static function adminNotFound(int $id): self
    {
        return new self("Admin with ID {$id} not found", 404);
    }
}
