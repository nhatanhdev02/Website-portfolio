<?php

namespace App\Exceptions\Admin;

use Exception;

class BusinessLogicException extends Exception
{
    /**
     * Create exception for business rule violation
     *
     * @param string $rule
     * @param string $message
     * @return static
     */
    public static function ruleViolation(string $rule, string $message): self
    {
        return new self("Business rule violation ({$rule}): {$message}", 422);
    }

    /**
     * Create exception for duplicate service order
     *
     * @param int $order
     * @return static
     */
    public static function duplicateServiceOrder(int $order): self
    {
        return new self("Service order {$order} is already in use", 422);
    }

    /**
     * Create exception for invalid service reorder
     *
     * @return static
     */
    public static function invalidServiceReorder(): self
    {
        return new self('Invalid service reorder operation - missing or duplicate positions', 422);
    }

    /**
     * Create exception for maximum featured projects exceeded
     *
     * @param int $maxFeatured
     * @return static
     */
    public static function maxFeaturedProjectsExceeded(int $maxFeatured): self
    {
        return new self("Cannot feature more than {$maxFeatured} projects", 422);
    }

    /**
     * Create exception for blog post already published
     *
     * @param int $id
     * @return static
     */
    public static function blogPostAlreadyPublished(int $id): self
    {
        return new self("Blog post {$id} is already published", 422);
    }

    /**
     * Create exception for cannot unpublish blog post
     *
     * @param int $id
     * @return static
     */
    public static function cannotUnpublishBlogPost(int $id): self
    {
        return new self("Cannot unpublish blog post {$id} - use draft status instead", 422);
    }

    /**
     * Create exception for invalid project category
     *
     * @param string $category
     * @param array $validCategories
     * @return static
     */
    public static function invalidProjectCategory(string $category, array $validCategories): self
    {
        $valid = implode(', ', $validCategories);
        return new self("Invalid project category '{$category}'. Valid categories: {$valid}", 422);
    }

    /**
     * Create exception for contact message already read
     *
     * @param int $id
     * @return static
     */
    public static function contactMessageAlreadyRead(int $id): self
    {
        return new self("Contact message {$id} is already marked as read", 422);
    }

    /**
     * Create exception for system in maintenance mode
     *
     * @return static
     */
    public static function systemInMaintenance(): self
    {
        return new self('System is currently in maintenance mode', 503);
    }

    /**
     * Create exception for invalid language setting
     *
     * @param string $language
     * @param array $supportedLanguages
     * @return static
     */
    public static function invalidLanguageSetting(string $language, array $supportedLanguages): self
    {
        $supported = implode(', ', $supportedLanguages);
        return new self("Invalid language '{$language}'. Supported languages: {$supported}", 422);
    }

    /**
     * Create exception for concurrent modification
     *
     * @param string $resource
     * @return static
     */
    public static function concurrentModification(string $resource): self
    {
        return new self("The {$resource} was modified by another user. Please refresh and try again", 409);
    }
}
