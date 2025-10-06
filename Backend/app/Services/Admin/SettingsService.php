<?php

namespace App\Services\Admin;

use App\Models\SystemSettings;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    public function __construct(
        private SettingsRepositoryInterface $settingsRepository
    ) {}

    /**
     * Get all system settings
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        return $this->settingsRepository->getAllSettings();
    }

    /**
     * Get setting by key
     *
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key): mixed
    {
        return $this->settingsRepository->getSetting($key);
    }

    /**
     * Set single setting
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $adminId
     * @return SystemSettings
     * @throws ValidationException
     */
    public function setSetting(string $key, mixed $value, ?int $adminId = null): SystemSettings
    {
        $this->validateSettingKey($key);
        $this->validateSettingValue($key, $value);

        $oldValue = $this->settingsRepository->getSetting($key);
        $setting = $this->settingsRepository->setSetting($key, $value);

        $this->logAction('setting_updated', [
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $value
        ], $adminId);

        return $setting;
    }

    /**
     * Update multiple settings
     *
     * @param array $settings
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function updateSettings(array $settings, ?int $adminId = null): bool
    {
        $this->validateSettingsData($settings);

        // Get old values for logging
        $oldValues = [];
        foreach ($settings as $key => $value) {
            $oldValues[$key] = $this->settingsRepository->getSetting($key);
        }

        $result = $this->settingsRepository->updateSettings($settings);

        if ($result) {
            $this->logAction('settings_bulk_updated', [
                'settings' => $settings,
                'old_values' => $oldValues,
                'count' => count($settings)
            ], $adminId);
        }

        return $result;
    }

    /**
     * Delete setting
     *
     * @param string $key
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function deleteSetting(string $key, ?int $adminId = null): bool
    {
        $this->validateSettingKey($key);

        $oldValue = $this->settingsRepository->getSetting($key);
        $result = $this->settingsRepository->deleteSetting($key);

        if ($result) {
            $this->logAction('setting_deleted', [
                'key' => $key,
                'old_value' => $oldValue
            ], $adminId);
        }

        return $result;
    }

    /**
     * Get language settings
     *
     * @return array
     */
    public function getLanguageSettings(): array
    {
        return [
            'default_language' => $this->getSetting('default_language') ?? 'en',
            'available_languages' => $this->getSetting('available_languages') ?? ['en', 'vi'],
            'fallback_language' => $this->getSetting('fallback_language') ?? 'en'
        ];
    }

    /**
     * Update language settings
     *
     * @param array $data
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function updateLanguageSettings(array $data, ?int $adminId = null): bool
    {
        $this->validateLanguageSettings($data);

        $settings = [];
        if (isset($data['default_language'])) {
            $settings['default_language'] = $data['default_language'];
        }
        if (isset($data['available_languages'])) {
            $settings['available_languages'] = $data['available_languages'];
        }
        if (isset($data['fallback_language'])) {
            $settings['fallback_language'] = $data['fallback_language'];
        }

        return $this->updateSettings($settings, $adminId);
    }

    /**
     * Get theme settings
     *
     * @return array
     */
    public function getThemeSettings(): array
    {
        return [
            'primary_color' => $this->getSetting('primary_color') ?? '#3B82F6',
            'secondary_color' => $this->getSetting('secondary_color') ?? '#64748B',
            'accent_color' => $this->getSetting('accent_color') ?? '#F59E0B',
            'dark_mode' => $this->getSetting('dark_mode') ?? false,
            'custom_css' => $this->getSetting('custom_css') ?? ''
        ];
    }

    /**
     * Update theme settings
     *
     * @param array $data
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function updateThemeSettings(array $data, ?int $adminId = null): bool
    {
        $this->validateThemeSettings($data);

        $settings = [];
        if (isset($data['primary_color'])) {
            $settings['primary_color'] = $data['primary_color'];
        }
        if (isset($data['secondary_color'])) {
            $settings['secondary_color'] = $data['secondary_color'];
        }
        if (isset($data['accent_color'])) {
            $settings['accent_color'] = $data['accent_color'];
        }
        if (isset($data['dark_mode'])) {
            $settings['dark_mode'] = $data['dark_mode'];
        }
        if (isset($data['custom_css'])) {
            $settings['custom_css'] = $data['custom_css'];
        }

        return $this->updateSettings($settings, $adminId);
    }

    /**
     * Get maintenance settings
     *
     * @return array
     */
    public function getMaintenanceSettings(): array
    {
        return [
            'maintenance_mode' => $this->getSetting('maintenance_mode') ?? false,
            'maintenance_message' => $this->getSetting('maintenance_message') ?? 'Site is under maintenance',
            'maintenance_end_time' => $this->getSetting('maintenance_end_time') ?? null
        ];
    }

    /**
     * Toggle maintenance mode
     *
     * @param bool $enabled
     * @param string|null $message
     * @param string|null $endTime
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function toggleMaintenanceMode(bool $enabled, ?string $message = null, ?string $endTime = null, ?int $adminId = null): bool
    {
        $settings = ['maintenance_mode' => $enabled];

        if ($message !== null) {
            $this->validateMaintenanceMessage($message);
            $settings['maintenance_message'] = $message;
        }

        if ($endTime !== null) {
            $this->validateMaintenanceEndTime($endTime);
            $settings['maintenance_end_time'] = $endTime;
        }

        return $this->updateSettings($settings, $adminId);
    }

    /**
     * Validate setting key
     *
     * @param string $key
     * @throws ValidationException
     */
    private function validateSettingKey(string $key): void
    {
        $validator = Validator::make(['key' => $key], [
            'key' => 'required|string|max:100|regex:/^[a-zA-Z0-9_]+$/'
        ], [
            'key.required' => 'Setting key is required',
            'key.string' => 'Setting key must be a string',
            'key.max' => 'Setting key must not exceed 100 characters',
            'key.regex' => 'Setting key can only contain letters, numbers, and underscores'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate setting value based on key
     *
     * @param string $key
     * @param mixed $value
     * @throws ValidationException
     */
    private function validateSettingValue(string $key, mixed $value): void
    {
        $rules = [];
        $messages = [];

        // Define validation rules based on setting key
        switch ($key) {
            case 'default_language':
            case 'fallback_language':
                $rules['value'] = 'required|string|in:en,vi';
                $messages['value.in'] = 'Language must be either en or vi';
                break;
            case 'available_languages':
                $rules['value'] = 'required|array|min:1';
                $rules['value.*'] = 'string|in:en,vi';
                $messages['value.*.in'] = 'Each language must be either en or vi';
                break;
            case 'primary_color':
            case 'secondary_color':
            case 'accent_color':
                $rules['value'] = 'required|regex:/^#[0-9A-Fa-f]{6}$/';
                $messages['value.regex'] = 'Color must be a valid hex color code';
                break;
            case 'dark_mode':
            case 'maintenance_mode':
                $rules['value'] = 'required|boolean';
                break;
            case 'custom_css':
            case 'maintenance_message':
                $rules['value'] = 'nullable|string|max:10000';
                break;
            case 'maintenance_end_time':
                $rules['value'] = 'nullable|date|after:now';
                $messages['value.after'] = 'Maintenance end time must be in the future';
                break;
            default:
                $rules['value'] = 'required';
                break;
        }

        $validator = Validator::make(['value' => $value], $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate settings data
     *
     * @param array $settings
     * @throws ValidationException
     */
    private function validateSettingsData(array $settings): void
    {
        if (empty($settings)) {
            throw ValidationException::withMessages([
                'settings' => ['At least one setting is required']
            ]);
        }

        foreach ($settings as $key => $value) {
            $this->validateSettingKey($key);
            $this->validateSettingValue($key, $value);
        }
    }

    /**
     * Validate language settings
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateLanguageSettings(array $data): void
    {
        $validator = Validator::make($data, [
            'default_language' => 'nullable|string|in:en,vi',
            'available_languages' => 'nullable|array|min:1',
            'available_languages.*' => 'string|in:en,vi',
            'fallback_language' => 'nullable|string|in:en,vi'
        ], [
            'default_language.in' => 'Default language must be either en or vi',
            'available_languages.min' => 'At least one language must be available',
            'available_languages.*.in' => 'Each available language must be either en or vi',
            'fallback_language.in' => 'Fallback language must be either en or vi'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate theme settings
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateThemeSettings(array $data): void
    {
        $validator = Validator::make($data, [
            'primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'dark_mode' => 'nullable|boolean',
            'custom_css' => 'nullable|string|max:10000'
        ], [
            'primary_color.regex' => 'Primary color must be a valid hex color code',
            'secondary_color.regex' => 'Secondary color must be a valid hex color code',
            'accent_color.regex' => 'Accent color must be a valid hex color code',
            'dark_mode.boolean' => 'Dark mode must be true or false',
            'custom_css.max' => 'Custom CSS must not exceed 10000 characters'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate maintenance message
     *
     * @param string $message
     * @throws ValidationException
     */
    private function validateMaintenanceMessage(string $message): void
    {
        $validator = Validator::make(['message' => $message], [
            'message' => 'required|string|max:1000'
        ], [
            'message.required' => 'Maintenance message is required',
            'message.max' => 'Maintenance message must not exceed 1000 characters'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate maintenance end time
     *
     * @param string $endTime
     * @throws ValidationException
     */
    private function validateMaintenanceEndTime(string $endTime): void
    {
        $validator = Validator::make(['end_time' => $endTime], [
            'end_time' => 'required|date|after:now'
        ], [
            'end_time.required' => 'Maintenance end time is required',
            'end_time.date' => 'Maintenance end time must be a valid date',
            'end_time.after' => 'Maintenance end time must be in the future'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Log admin action
     *
     * @param string $action
     * @param array $data
     * @param int|null $adminId
     */
    private function logAction(string $action, array $data, ?int $adminId = null): void
    {
        Log::info('Settings service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
