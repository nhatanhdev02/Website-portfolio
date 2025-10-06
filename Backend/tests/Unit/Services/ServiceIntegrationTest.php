<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\ContactService;
use App\Services\Admin\SettingsService;
use App\Services\Admin\FileUploadService;
use App\Services\Admin\AuditLogService;

class ServiceIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_contact_service(): void
    {
        // Act
        $service = app(ContactService::class);

        // Assert
        $this->assertInstanceOf(ContactService::class, $service);
    }

    /** @test */
    public function it_can_instantiate_settings_service(): void
    {
        // Act
        $service = app(SettingsService::class);

        // Assert
        $this->assertInstanceOf(SettingsService::class, $service);
    }

    /** @test */
    public function it_can_instantiate_file_upload_service(): void
    {
        // Act
        $service = app(FileUploadService::class);

        // Assert
        $this->assertInstanceOf(FileUploadService::class, $service);
    }

    /** @test */
    public function it_can_instantiate_audit_log_service(): void
    {
        // Act
        $service = app(AuditLogService::class);

        // Assert
        $this->assertInstanceOf(AuditLogService::class, $service);
    }

    /** @test */
    public function services_have_required_methods(): void
    {
        // Arrange
        $contactService = app(ContactService::class);
        $settingsService = app(SettingsService::class);
        $fileUploadService = app(FileUploadService::class);
        $auditLogService = app(AuditLogService::class);

        // Assert ContactService methods
        $this->assertTrue(method_exists($contactService, 'getAllMessages'));
        $this->assertTrue(method_exists($contactService, 'markAsRead'));
        $this->assertTrue(method_exists($contactService, 'bulkMarkAsRead'));
        $this->assertTrue(method_exists($contactService, 'deleteMessage'));
        $this->assertTrue(method_exists($contactService, 'bulkDeleteMessages'));
        $this->assertTrue(method_exists($contactService, 'getContactInfo'));
        $this->assertTrue(method_exists($contactService, 'updateContactInfo'));
        $this->assertTrue(method_exists($contactService, 'getMessageStatistics'));

        // Assert SettingsService methods
        $this->assertTrue(method_exists($settingsService, 'getAllSettings'));
        $this->assertTrue(method_exists($settingsService, 'getSetting'));
        $this->assertTrue(method_exists($settingsService, 'setSetting'));
        $this->assertTrue(method_exists($settingsService, 'updateSettings'));
        $this->assertTrue(method_exists($settingsService, 'deleteSetting'));
        $this->assertTrue(method_exists($settingsService, 'getLanguageSettings'));
        $this->assertTrue(method_exists($settingsService, 'updateLanguageSettings'));
        $this->assertTrue(method_exists($settingsService, 'getThemeSettings'));
        $this->assertTrue(method_exists($settingsService, 'updateThemeSettings'));
        $this->assertTrue(method_exists($settingsService, 'getMaintenanceSettings'));
        $this->assertTrue(method_exists($settingsService, 'toggleMaintenanceMode'));

        // Assert FileUploadService methods
        $this->assertTrue(method_exists($fileUploadService, 'uploadImage'));
        $this->assertTrue(method_exists($fileUploadService, 'uploadDocument'));
        $this->assertTrue(method_exists($fileUploadService, 'deleteFile'));
        $this->assertTrue(method_exists($fileUploadService, 'getFileInfo'));

        // Assert AuditLogService methods
        $this->assertTrue(method_exists($auditLogService, 'logAuthEvent'));
        $this->assertTrue(method_exists($auditLogService, 'logCrudOperation'));
        $this->assertTrue(method_exists($auditLogService, 'logFileOperation'));
        $this->assertTrue(method_exists($auditLogService, 'logSecurityEvent'));
        $this->assertTrue(method_exists($auditLogService, 'logConfigChange'));
        $this->assertTrue(method_exists($auditLogService, 'logError'));
        $this->assertTrue(method_exists($auditLogService, 'logApiRequest'));
        $this->assertTrue(method_exists($auditLogService, 'logSlowQuery'));
        $this->assertTrue(method_exists($auditLogService, 'logBulkOperation'));
        $this->assertTrue(method_exists($auditLogService, 'logMaintenanceEvent'));
        $this->assertTrue(method_exists($auditLogService, 'getAuditStatistics'));
        $this->assertTrue(method_exists($auditLogService, 'searchAuditLogs'));
        $this->assertTrue(method_exists($auditLogService, 'cleanOldLogs'));
    }
}
