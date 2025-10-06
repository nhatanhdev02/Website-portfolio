<?php

namespace App\Repositories\Eloquent;

use App\Models\SystemSettings;
use App\Repositories\Contracts\SettingsRepositoryInterface;

class SettingsRepository extends BaseRepository implements SettingsRepositoryInterface
{
    public function __construct(SystemSettings $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all settings as key-value pairs
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        return $this->model->pluck('value', 'key')->toArray();
    }

    /**
     * Get setting by key
     *
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key): mixed
    {
        $setting = $this->model->where('key', $key)->first();

        if (!$setting) {
            return null;
        }

        // Handle different data types
        return $this->castValue($setting->value, $setting->type ?? 'string');
    }

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @return SystemSettings
     */
    public function setSetting(string $key, mixed $value): SystemSettings
    {
        $type = $this->determineType($value);
        $serializedValue = $this->serializeValue($value, $type);

        return $this->model->updateOrCreate(
            ['key' => $key],
            [
                'value' => $serializedValue,
                'type' => $type
            ]
        );
    }

    /**
     * Update multiple settings
     *
     * @param array $settings
     * @return bool
     */
    public function updateSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                $this->setSetting($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete setting by key
     *
     * @param string $key
     * @return bool
     */
    public function deleteSetting(string $key): bool
    {
        return $this->model->where('key', $key)->delete() > 0;
    }

    /**
     * Cast value to appropriate type
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'object' => json_decode($value, true),
            'json' => json_decode($value),
            default => $value,
        };
    }

    /**
     * Determine the type of a value
     *
     * @param mixed $value
     * @return string
     */
    private function determineType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            is_object($value) => 'object',
            default => 'string',
        };
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private function serializeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'array', 'object' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Apply filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['key'])) {
            $query->where('key', 'like', "%{$filters['key']}%");
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderBy('key');

        return $query;
    }
}
