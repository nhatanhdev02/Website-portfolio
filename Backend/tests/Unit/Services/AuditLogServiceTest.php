<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogService = new AuditLogService();
        Log::spy();
    }

    /** @test */
    public function it_can_log_auth_events(): void
    {
        // Arrange
        $event = 'login_success';
        $data = ['username' => 'admin'];
        $adminId = 1;

        // Act
        $this->auditLogService->logAuthEvent($event, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: auth.login_success', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_crud_operations(): void
    {
        // Arrange
        $entity = 'project';
        $operation = 'create';
        $data = ['title' => 'New Project'];
        $adminId = 1;

        // Act
        $this->auditLogService->logCrudOperation($entity, $operation, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: crud.project_create', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_file_operations(): void
    {
        // Arrange
        $operation = 'upload';
        $data = ['filename' => 'test.jpg', 'size' => 1024];
        $adminId = 1;

        // Act
        $this->auditLogService->logFileOperation($operation, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: file.upload', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_security_events(): void
    {
        // Arrange
        $event = 'unauthorized_access';
        $data = ['ip' => '192.168.1.1'];
        $adminId = null;

        // Act
        $this->auditLogService->logSecurityEvent($event, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('warning', 'Admin audit: security.unauthorized_access', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_config_changes(): void
    {
        // Arrange
        $setting = 'maintenance_mode';
        $oldValue = false;
        $newValue = true;
        $adminId = 1;

        // Act
        $this->auditLogService->logConfigChange($setting, $oldValue, $newValue, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: config.setting_changed', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_errors(): void
    {
        // Arrange
        $error = 'database_connection_failed';
        $context = ['host' => 'localhost', 'port' => 3306];
        $adminId = 1;

        // Act
        $this->auditLogService->logError($error, $context, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('error', 'Admin audit: error.database_connection_failed', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_api_requests(): void
    {
        // Arrange
        $method = 'POST';
        $endpoint = '/api/admin/projects';
        $data = ['title' => 'New Project'];
        $statusCode = 201;
        $adminId = 1;

        // Act
        $this->auditLogService->logApiRequest($method, $endpoint, $data, $statusCode, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: api.request', \Mockery::type('array'));
    }

    /** @test */
    public function it_logs_api_requests_as_warning_for_error_status_codes(): void
    {
        // Arrange
        $method = 'POST';
        $endpoint = '/api/admin/projects';
        $data = ['title' => 'Invalid Project'];
        $statusCode = 422;
        $adminId = 1;

        // Act
        $this->auditLogService->logApiRequest($method, $endpoint, $data, $statusCode, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('warning', 'Admin audit: api.request', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_slow_queries(): void
    {
        // Arrange
        $query = 'SELECT * FROM projects WHERE title LIKE ?';
        $executionTime = 1500.0; // 1.5 seconds
        $bindings = ['%test%'];

        // Act
        $this->auditLogService->logSlowQuery($query, $executionTime, $bindings);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('warning', 'Admin audit: performance.slow_query', \Mockery::type('array'));
    }

    /** @test */
    public function it_does_not_log_fast_queries(): void
    {
        // Arrange
        $query = 'SELECT * FROM projects WHERE id = ?';
        $executionTime = 50.0; // 50ms
        $bindings = [1];

        // Act
        $this->auditLogService->logSlowQuery($query, $executionTime, $bindings);

        // Assert
        Log::shouldNotHaveReceived('log');
    }

    /** @test */
    public function it_can_log_bulk_operations(): void
    {
        // Arrange
        $operation = 'delete';
        $entity = 'messages';
        $count = 5;
        $data = ['ids' => [1, 2, 3, 4, 5]];
        $adminId = 1;

        // Act
        $this->auditLogService->logBulkOperation($operation, $entity, $count, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: bulk.messages_delete', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_log_maintenance_events(): void
    {
        // Arrange
        $event = 'maintenance_mode_enabled';
        $data = ['message' => 'System maintenance in progress'];
        $adminId = 1;

        // Act
        $this->auditLogService->logMaintenanceEvent($event, $data, $adminId);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: maintenance.maintenance_mode_enabled', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_get_audit_statistics(): void
    {
        // Act
        $result = $this->auditLogService->getAuditStatistics('24h');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('auth_events', $result);
        $this->assertArrayHasKey('crud_operations', $result);
        $this->assertArrayHasKey('start_time', $result);
        $this->assertArrayHasKey('end_time', $result);
        $this->assertEquals('24h', $result['period']);
    }

    /** @test */
    public function it_can_search_audit_logs(): void
    {
        // Arrange
        $filters = ['category' => 'auth', 'admin_id' => 1];
        $limit = 50;

        // Act
        $result = $this->auditLogService->searchAuditLogs($filters, $limit);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('logs', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('filters', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertEquals($filters, $result['filters']);
        $this->assertEquals($limit, $result['limit']);
    }

    /** @test */
    public function it_can_clean_old_logs(): void
    {
        // Arrange
        $daysToKeep = 30;

        // Act
        $result = $this->auditLogService->cleanOldLogs($daysToKeep);

        // Assert
        $this->assertIsInt($result);
        $this->assertEquals(0, $result); // Mock implementation returns 0

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Audit log cleanup would delete logs older than 30 days');
    }

    /** @test */
    public function it_includes_request_context_in_logs(): void
    {
        // Arrange
        $this->withoutMiddleware();

        // Act
        $this->auditLogService->logAuthEvent('test_event', ['test' => 'data']);

        // Assert
        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Admin audit: auth.test_event', \Mockery::on(function ($data) {
                return isset($data['ip_address']) &&
                    isset($data['user_agent']) &&
                    isset($data['timestamp']) &&
                    isset($data['memory_usage']);
            }));
    }
}
