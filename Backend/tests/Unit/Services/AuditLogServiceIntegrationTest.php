<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\AuditLogService;
use App\Models\AuditLog;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class AuditLogServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogService = app(AuditLogService::class);
    }

    /** @test */
    public function it_can_log_auth_events(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logAuthEvent('login', [
            'username' => $admin->username,
            'ip' => '192.168.1.1'
        ], $admin->id);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'auth',
            'event' => 'login',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'auth')->first();
        $this->assertEquals('login', $auditLog->event);
        $this->assertEquals($admin->id, $auditLog->admin_id);
        $this->assertArrayHasKey('username', $auditLog->data);
        $this->assertEquals($admin->username, $auditLog->data['username']);
    }

    /** @test */
    public function it_can_log_crud_operations(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logCrudOperation('project', 'create', [
            'title' => 'New Project',
            'id' => 123
        ], $admin->id);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'crud',
            'event' => 'project_create',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'crud')->first();
        $this->assertEquals('project_create', $auditLog->event);
        $this->assertArrayHasKey('title', $auditLog->data);
        $this->assertEquals('New Project', $auditLog->data['title']);
    }

    /** @test */
    public function it_can_log_file_operations(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logFileOperation('upload', [
            'filename' => 'test.jpg',
            'size' => 1024,
            'type' => 'image/jpeg'
        ], $admin->id);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'file',
            'event' => 'upload',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'file')->first();
        $this->assertEquals('upload', $auditLog->event);
        $this->assertArrayHasKey('filename', $auditLog->data);
        $this->assertEquals('test.jpg', $auditLog->data['filename']);
    }

    /** @test */
    public function it_can_log_security_events(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logSecurityEvent('failed_login', [
            'username' => 'invalid_user',
            'ip' => '192.168.1.100',
            'attempts' => 3
        ], $admin->id);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'security',
            'event' => 'failed_login',
            'level' => 'warning',
        ]);

        $auditLog = AuditLog::where('category', 'security')->first();
        $this->assertEquals('failed_login', $auditLog->event);
        $this->assertEquals('warning', $auditLog->level);
        $this->assertArrayHasKey('attempts', $auditLog->data);
        $this->assertEquals(3, $auditLog->data['attempts']);
    }

    /** @test */
    public function it_can_log_config_changes(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logConfigChange(
            'maintenance_mode',
            false,
            true,
            $admin->id
        );

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'config',
            'event' => 'setting_changed',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'config')->first();
        $this->assertEquals('setting_changed', $auditLog->event);
        $this->assertArrayHasKey('setting', $auditLog->data);
        $this->assertEquals('maintenance_mode', $auditLog->data['setting']);
        $this->assertEquals(false, $auditLog->data['old_value']);
        $this->assertEquals(true, $auditLog->data['new_value']);
    }

    /** @test */
    public function it_can_log_api_requests(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logApiRequest(
            'POST',
            '/api/admin/projects',
            ['title' => 'Test Project'],
            201,
            $admin->id
        );

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'api',
            'event' => 'request',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'api')->first();
        $this->assertEquals('request', $auditLog->event);
        $this->assertArrayHasKey('method', $auditLog->data);
        $this->assertEquals('POST', $auditLog->data['method']);
        $this->assertArrayHasKey('status_code', $auditLog->data);
        $this->assertEquals(201, $auditLog->data['status_code']);
    }

    /** @test */
    public function it_can_log_bulk_operations(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Act
        $this->auditLogService->logBulkOperation(
            'delete',
            'contact_messages',
            5,
            ['ids' => [1, 2, 3, 4, 5]],
            $admin->id
        );

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'bulk',
            'event' => 'contact_messages_delete',
            'admin_id' => $admin->id,
        ]);

        $auditLog = AuditLog::where('category', 'bulk')->first();
        $this->assertEquals('contact_messages_delete', $auditLog->event);
        $this->assertArrayHasKey('count', $auditLog->data);
        $this->assertEquals(5, $auditLog->data['count']);
    }

    /** @test */
    public function it_can_get_audit_statistics(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Create various audit log entries
        AuditLog::create([
            'category' => 'auth',
            'event' => 'login',
            'admin_id' => $admin->id,
            'data' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
        ]);

        AuditLog::create([
            'category' => 'crud',
            'event' => 'project_create',
            'admin_id' => $admin->id,
            'data' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
        ]);

        AuditLog::create([
            'category' => 'security',
            'event' => 'failed_login',
            'admin_id' => $admin->id,
            'data' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'level' => 'warning',
        ]);

        // Act
        $stats = $this->auditLogService->getAuditStatistics('24h');

        // Assert
        $this->assertEquals(3, $stats['total_events']);
        $this->assertEquals(1, $stats['auth_events']);
        $this->assertEquals(1, $stats['crud_operations']);
        $this->assertEquals(1, $stats['security_events']);
        $this->assertEquals(0, $stats['error_events']);
        $this->assertEquals(1, $stats['unique_admins']);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertArrayHasKey('levels', $stats);
    }

    /** @test */
    public function it_can_search_audit_logs(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        AuditLog::create([
            'category' => 'auth',
            'event' => 'login',
            'admin_id' => $admin->id,
            'data' => ['username' => 'testuser'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
        ]);

        AuditLog::create([
            'category' => 'crud',
            'event' => 'project_create',
            'admin_id' => $admin->id,
            'data' => ['title' => 'Test Project'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
        ]);

        // Act
        $results = $this->auditLogService->searchAuditLogs([
            'category' => 'auth',
            'admin_id' => $admin->id
        ], 10);

        // Assert
        $this->assertEquals(1, $results['total']);
        $this->assertCount(1, $results['logs']);
        $this->assertEquals('auth', $results['logs'][0]['category']);
        $this->assertEquals('login', $results['logs'][0]['event']);
        $this->assertEquals($admin->id, $results['logs'][0]['admin']['id']);
    }

    /** @test */
    public function it_can_clean_old_logs(): void
    {
        // Arrange
        $admin = Admin::factory()->create();

        // Create old log entry
        $oldLog = AuditLog::create([
            'category' => 'auth',
            'event' => 'login',
            'admin_id' => $admin->id,
            'data' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        // Create recent log entry
        $recentLog = AuditLog::create([
            'category' => 'auth',
            'event' => 'logout',
            'admin_id' => $admin->id,
            'data' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'level' => 'info',
        ]);

        // Act
        $deletedCount = $this->auditLogService->cleanOldLogs(90);

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recentLog->id]);
    }

    /** @test */
    public function it_handles_database_errors_gracefully(): void
    {
        // This test would require mocking the database to simulate failures
        // For now, we'll just ensure the service doesn't throw exceptions

        // Act & Assert - should not throw exceptions
        $this->auditLogService->logAuthEvent('test', ['data' => 'test']);
        $stats = $this->auditLogService->getAuditStatistics('1h');
        $results = $this->auditLogService->searchAuditLogs(['invalid' => 'filter']);

        $this->assertIsArray($stats);
        $this->assertIsArray($results);
    }
}
