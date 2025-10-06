<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Services\Admin\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Display all system settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAllSettings();

            return response()->json([
                'success' => true,
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific setting by key
     */
    public function show(string $key): JsonResponse
    {
        try {
            $setting = $this->settingsService->getSetting($key);

            if ($setting === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $setting
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system settings
     */
    public function update(SettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $settings = $this->settingsService->updateSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific setting
     */
    public function updateSetting(Request $request, string $key): JsonResponse
    {
        try {
            $request->validate([
                'value' => 'required'
            ]);

            $value = $request->input('value');
            $setting = $this->settingsService->updateSetting($key, $value);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => $setting
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get language settings
     */
    public function language(): JsonResponse
    {
        try {
            $languageSettings = $this->settingsService->getLanguageSettings();

            return response()->json([
                'success' => true,
                'data' => $languageSettings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update language settings
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'default_language' => 'required|in:vi,en',
                'available_languages' => 'required|array',
                'available_languages.*' => 'in:vi,en'
            ]);

            $data = $request->only(['default_language', 'available_languages']);
            $settings = $this->settingsService->updateLanguageSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Language settings updated successfully',
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get theme settings
     */
    public function theme(): JsonResponse
    {
        try {
            $themeSettings = $this->settingsService->getThemeSettings();

            return response()->json([
                'success' => true,
                'data' => $themeSettings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update theme settings
     */
    public function updateTheme(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'background_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'text_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'dark_mode' => 'nullable|boolean'
            ]);

            $data = $request->only([
                'primary_color', 'secondary_color', 'accent_color',
                'background_color', 'text_color', 'dark_mode'
            ]);

            $settings = $this->settingsService->updateThemeSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Theme settings updated successfully',
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(): JsonResponse
    {
        try {
            $result = $this->settingsService->toggleMaintenanceMode();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode toggled successfully',
                'data' => [
                    'maintenance_mode' => $result
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset settings to default
     */
    public function reset(): JsonResponse
    {
        try {
            $settings = $this->settingsService->resetToDefaults();

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults successfully',
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
