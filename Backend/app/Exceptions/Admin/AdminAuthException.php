<?php

namespace App\Exceptions\Admin;

use Exception;

class AdminAuthException extends Exception
{
    /**
     * Create exception for invalid credentials
     *
     * @return static
     */
    public static function invalidCredentials(): self
    {
        return new self('Invalid admin credentials provided', 401);
    }

    /**
     * Create exception for expired token
     *
     * @return static
     */
    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired', 401);
    }

    /**
     * Create exception for unauthorized access
     *
     * @return static
     */
    public static function unauthorized(): self
    {
        return new self('Unauthorized access to admin resources', 403);
    }

    /**
     * Create exception for session timeout
     *
     * @return static
     */
    public static function sessionTimeout(): self
    {
        return new self('Admin session has timed out', 401);
    }

    /**
     * Create exception for account locked
     *
     * @return static
     */
    public static function accountLocked(): self
    {
        return new self('Admin account is locked due to multiple failed login attempts', 423);
    }
}
