<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Exceptions\Admin\AdminAuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Authenticate admin and return token
     *
     * @param string $username
     * @param string $password
     * @return array
     * @throws AdminAuthException
     */
    public function authenticate(string $username, string $password): array
    {
        $admin = Admin::where('username', $username)->first();

        if (!$admin || !Hash::check($password, $admin->password)) {
            $this->logFailedLogin($username, request()->ip());
            throw AdminAuthException::invalidCredentials();
        }

        // Update last login timestamp
        $admin->updateLastLogin();

        // Create token
        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        $this->logSuccessfulLogin($admin);

        return [
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'email' => $admin->email,
                'last_login_at' => $admin->last_login_at
            ]
        ];
    }

    /**
     * Logout admin by revoking current token
     *
     * @param Admin $admin
     * @return bool
     */
    public function logout(Admin $admin): bool
    {
        try {
            // Revoke current token
            $admin->currentAccessToken()->delete();

            $this->logLogout($admin);

            return true;
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Refresh token for admin
     *
     * @param Admin $admin
     * @return array
     */
    public function refreshToken(Admin $admin): array
    {
        // Revoke current token
        $admin->currentAccessToken()->delete();

        // Create new token
        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        $this->logTokenRefresh($admin);

        return [
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'email' => $admin->email,
                'last_login_at' => $admin->last_login_at
            ]
        ];
    }

    /**
     * Get current authenticated admin
     *
     * @param string $token
     * @return Admin
     * @throws AdminAuthException
     */
    public function getCurrentUser(string $token): Admin
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable instanceof Admin) {
            throw AdminAuthException::unauthorized();
        }

        return $accessToken->tokenable;
    }

    /**
     * Validate password strength
     *
     * @param string $password
     * @return bool
     * @throws ValidationException
     */
    public function validatePassword(string $password): bool
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages(['password' => $errors]);
        }

        return true;
    }

    /**
     * Check if admin session is valid
     *
     * @param Admin $admin
     * @param int $timeoutHours
     * @return bool
     */
    public function isSessionValid(Admin $admin, int $timeoutHours = 24): bool
    {
        return $admin->hasRecentLogin($timeoutHours);
    }

    /**
     * Revoke all tokens for admin
     *
     * @param Admin $admin
     * @return bool
     */
    public function revokeAllTokens(Admin $admin): bool
    {
        try {
            $admin->tokens()->delete();

            Log::info('All tokens revoked for admin', [
                'admin_id' => $admin->id,
                'username' => $admin->username
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke all tokens', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log successful login
     *
     * @param Admin $admin
     */
    private function logSuccessfulLogin(Admin $admin): void
    {
        Log::info('Admin login successful', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log failed login attempt
     *
     * @param string $username
     * @param string $ipAddress
     */
    private function logFailedLogin(string $username, string $ipAddress): void
    {
        Log::warning('Admin login failed', [
            'username' => $username,
            'ip_address' => $ipAddress,
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log logout
     *
     * @param Admin $admin
     */
    private function logLogout(Admin $admin): void
    {
        Log::info('Admin logout', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log token refresh
     *
     * @param Admin $admin
     */
    private function logTokenRefresh(Admin $admin): void
    {
        Log::info('Admin token refreshed', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
