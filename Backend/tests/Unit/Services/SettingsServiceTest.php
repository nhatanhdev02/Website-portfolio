<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\SettingsService;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Models\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settingsService;
    private SettingsRepositoryInterface $settingsRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsRepository = Mockery::mock(SettingsRepositoryInterface::class);
        $this->settingsService = new SettingsService($this->settingsRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_settings(): void
    {
        // Arrange
        $settings = [
            'default_language' => 'en',
            'primary_color' => '#3B82F6'
        ];

        $this->settingsRepository
            ->shouldReceive('getAllSettings')
            ->once()
            ->andReturn($settings);

        // Act
        $result = $this->settingsService->getAllSettings();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('en', $result['default_language']);
    }

    /** @test */
    public function it_can_get_single_setting(): void
    {
        // Arrange
        $key = 'default_language';
        $value = 'en';

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->once()
            ->with($key)
            ->andReturn($value);

        // Act
        $result = $this->settingsService->getSetting($key);

        // Assert
        $this->assertEquals('en', $result);
    }

    /** @test */
    public function it_can_set_single_setting(): void
    {
        // Arrange
        $key = 'default_language';
        $value = 'vi';
        $adminId = 1;
        $setting = new SystemSettings(['key' => $key, 'value' => $value]);

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->once()
            ->with($key)
            ->andReturn('en'); // old value

        $this->settingsRepository
            ->shouldReceive('setSetting')
            ->once()
            ->with($key, $value)
            ->andReturn($setting);

        // Act
        $result = $this->settingsService->setSetting($key, $value, $adminId);

        // Assert
        $this->assertInstanceOf(SystemSettings::class, $result);
    }

    /** @test */
    public function it_validates_setting_key(): void
    {
        // Arrange
        $invalidKey = 'invalid-key-with-dashes';
        $value = 'test';

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->settingsService->setSetting($invalidKey, $value);
    }

    /** @test */
    public function it_validates_color_setting_values(): void
    {
        // Arrange
        $key = 'primary_color';
        $invalidValue = 'not-a-color';

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->settingsService->setSetting($key, $invalidValue);
    }

    /** @test */
    public function it_validates_language_setting_values(): void
    {
        // Arrange
        $key = 'default_language';
        $invalidValue = 'invalid_language';

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->settingsService->setSetting($key, $invalidValue);
    }

    /** @test */
    public function it_can_update_multiple_settings(): void
    {
        // Arrange
        $settings = [
            'default_language' => 'vi',
            'primary_color' => '#FF0000'
        ];
        $adminId = 1;

        // Mock getting old values
        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('default_language')
            ->andReturn('en');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('primary_color')
            ->andReturn('#3B82F6');

        $this->settingsRepository
            ->shouldReceive('updateSettings')
            ->once()
            ->with($settings)
            ->andReturn(true);

        // Act
        $result = $this->settingsService->updateSettings($settings, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_setting(): void
    {
        // Arrange
        $key = 'custom_setting';
        $adminId = 1;

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->once()
            ->with($key)
            ->andReturn('old_value');

        $this->settingsRepository
            ->shouldReceive('deleteSetting')
            ->once()
            ->with($key)
            ->andReturn(true);

        // Act
        $result = $this->settingsService->deleteSetting($key, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_language_settings(): void
    {
        // Arrange
        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('default_language')
            ->andReturn('en');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('available_languages')
            ->andReturn(['en', 'vi']);

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('fallback_language')
            ->andReturn('en');

        // Act
        $result = $this->settingsService->getLanguageSettings();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('en', $result['default_language']);
        $this->assertEquals(['en', 'vi'], $result['available_languages']);
    }

    /** @test */
    public function it_can_update_language_settings(): void
    {
        // Arrange
        $data = [
            'default_language' => 'vi',
            'available_languages' => ['en', 'vi']
        ];
        $adminId = 1;

        // Mock getting old values
        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('default_language')
            ->andReturn('en');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('available_languages')
            ->andReturn(['en']);

        $this->settingsRepository
            ->shouldReceive('updateSettings')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->settingsService->updateLanguageSettings($data, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_theme_settings(): void
    {
        // Arrange
        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('primary_color')
            ->andReturn('#3B82F6');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('secondary_color')
            ->andReturn('#64748B');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('accent_color')
            ->andReturn('#F59E0B');

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('dark_mode')
            ->andReturn(false);

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('custom_css')
            ->andReturn('');

        // Act
        $result = $this->settingsService->getThemeSettings();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('#3B82F6', $result['primary_color']);
        $this->assertFalse($result['dark_mode']);
    }

    /** @test */
    public function it_can_toggle_maintenance_mode(): void
    {
        // Arrange
        $enabled = true;
        $message = 'Site under maintenance';
        $adminId = 1;

        // Mock getting old values
        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('maintenance_mode')
            ->andReturn(false);

        $this->settingsRepository
            ->shouldReceive('getSetting')
            ->with('maintenance_message')
            ->andReturn('');

        $this->settingsRepository
            ->shouldReceive('updateSettings')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->settingsService->toggleMaintenanceMode($enabled, $message, null, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_maintenance_message_length(): void
    {
        // Arrange
        $enabled = true;
        $longMessage = str_repeat('a', 1001); // Too long

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->settingsService->toggleMaintenanceMode($enabled, $longMessage);
    }
}
