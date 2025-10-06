<?php

namespace App\Repositories\Contracts;

use App\Models\SystemSettings;

interface SettingsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all settings as key-value pairs
     *
     * @return array
     */
    public function getAllSettings(): array;

    /**
     * Get setting by key
     *
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key): mixed;

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @return SystemSettings
     */
    public function setSetting(string $key, mixed $value): SystemSettings;

    /**
     * Update multiple settings
     *
     * @param array $settings
     * @return bool
     */
    public function updateSettings(array $settings): bool;

    /**
     * Delete setting by key
     *
     * @param string $key
     * @return bool
     */
    public function deleteSetting(string $key): bool;
}
