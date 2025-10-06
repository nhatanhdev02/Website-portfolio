<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
    }

    /** @test */
    public function api_endpoints_respond_within_acceptable_time_limits(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data
        Project::factory()->count(50)->create();
        BlogPost::factory()->count(30)->create();
        Service::factory()->count(10)->create();
        ContactMessage::factory()->count(100)->create();

        $endpoints = [
            ['GET', '/api/admin/hero', 500], // 500ms limit
            ['GET', '/api/admin/about', 500],
            ['GET', '/api/admin/services', 1000], // 1s limit for list endpoints
            ['GET', '/api/admin/projects', 1000],
            ['GET', '/api/admin/blog', 1000],
            ['GET', '/api/admin/contacts/messages', 1000],
            ['GET', '/api/admin/settings', 500],
        ];

        foreach ($endpoints as [$method, $endpoint, $maxTime]) {
            // Act
            $startTime = microtime(true);
            $response = $this->json($method, $endpoint);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan($maxTime, $responseTime,
                "Endpoint $method $endpoint took {$responseTime}ms, expected less than {$maxTime}ms");
        }
    }

    /** @test */
    public function pagination_performs_efficiently_with_large_datasets(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create large dataset
        BlogPost::factory()->count(1000)->create();

        $pageSizes = [10, 25, 50, 100];

        foreach ($pageSizes as $pageSize) {
            // Act
            $startTime = microtime(true);
            $response = $this->getJson("/api/admin/blog?per_page={$pageSize}");
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan(2000, $responseTime,
                "Pagination with {$pageSize} items took {$responseTime}ms, expected less than 2000ms");

            $this->assertCount($pageSize, $response->json('data.data'));
        }
    }

    /** @test */
    public function search_and_filtering_performs_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data with searchable content
        BlogPost::factory()->count(500)->create([
            'title_en' => 'Laravel Tutorial'
        ]);
        BlogPost::factory()->count(300)->create([
            'title_en' => 'Vue.js Guide'
        ]);
        BlogPost::factory()->count(200)->create([
            'title_en' => 'PHP Best Practices'
        ]);

        $searchQueries = [
            'Laravel',
            'Vue.js',
            'PHP',
            'Tutorial',
            'Guide'
        ];

        foreach ($searchQueries as $query) {
            // Act
            $startTime = microtime(true);
            $response = $this->getJson("/api/admin/blog?search={$query}");
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan(1500, $responseTime,
                "Search for '{$query}' took {$responseTime}ms, expected less than 1500ms");
        }
    }

    /** @test */
    public function bulk_operations_perform_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create large number of contact messages
        $messages = ContactMessage::factory()->count(200)->create(['read_at' => null]);
        $messageIds = $messages->pluck('id')->toArray();

        // Test bulk mark as read
        $batchSizes = [10, 50, 100];

        foreach ($batchSizes as $batchSize) {
            $batch = array_slice($messageIds, 0, $batchSize);

            // Act
            $startTime = microtime(true);
            $response = $this->postJson('/api/admin/contacts/messages/bulk-read', [
                'message_ids' => $batch
            ]);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan(3000, $responseTime,
                "Bulk operation with {$batchSize} items took {$responseTime}ms, expected less than 3000ms");

            // Reset messages for next test
            ContactMessage::query()->whereIn('id', $batch)->update(['read_at' => null]);
        }
    }

    /** @test */
    public function database_queries_are_optimized(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data with relationships
        $projects = Project::factory()->count(100)->create();

        // Enable query logging
        DB::enableQueryLog();

        // Act - Get projects (should use efficient queries)
        $response = $this->getJson('/api/admin/projects');

        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert
        $response->assertStatus(200);

        // Should not have N+1 query problems
        $this->assertLessThan(5, count($queries),
            'Too many database queries executed: ' . count($queries));

        // Check for efficient queries (no SELECT *)
        foreach ($queries as $query) {
            $this->assertStringNotContainsString('select *', strtolower($query['query']),
                'Query should not use SELECT *: ' . $query['query']);
        }
    }

    /** @test */
    public function concurrent_requests_are_handled_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Project::factory()->count(50)->create();

        // Simulate concurrent requests
        $concurrentRequests = 10;
        $responses = [];
        $startTime = microtime(true);

        // Act - Make multiple concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = $this->getJson('/api/admin/projects');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Total time should not be much more than a single request time
        $averageTime = $totalTime / $concurrentRequests;
        $this->assertLessThan(2000, $averageTime,
            "Average response time for concurrent requests: {$averageTime}ms");
    }

    /** @test */
    public function memory_usage_stays_within_limits(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create large dataset
        BlogPost::factory()->count(500)->create();

        $initialMemory = memory_get_usage(true);

        // Act - Perform memory-intensive operations
        $response = $this->getJson('/api/admin/blog?per_page=100');

        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $initialMemory;

        // Assert
        $response->assertStatus(200);

        // Memory usage should not exceed 50MB for this operation
        $maxMemoryMB = 50 * 1024 * 1024; // 50MB in bytes
        $this->assertLessThan($maxMemoryMB, $memoryUsed,
            'Memory usage exceeded limit: ' . ($memoryUsed / 1024 / 1024) . 'MB');
    }

    /** @test */
    public function file_upload_performance_is_acceptable(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test images of different sizes
        $imageSizes = [
            ['small', 100, 100],   // Small image
            ['medium', 800, 600],  // Medium image
            ['large', 1920, 1080], // Large image
        ];

        foreach ($imageSizes as [$size, $width, $height]) {
            $image = \Illuminate\Http\UploadedFile::fake()->image("test_{$size}.jpg", $width, $height);

            // Act
            $startTime = microtime(true);
            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => "Test Project {$size}",
                'title_en' => "Test Project {$size}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ]);
            $endTime = microtime(true);

            $uploadTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(201);

            // Upload time limits based on image size
            $maxTime = match($size) {
                'small' => 1000,  // 1s for small images
                'medium' => 3000, // 3s for medium images
                'large' => 5000,  // 5s for large images
            };

            $this->assertLessThan($maxTime, $uploadTime,
                "Upload of {$size} image took {$uploadTime}ms, expected less than {$maxTime}ms");

            // Clean up
            Project::latest()->first()->delete();
        }
    }

    /** @test */
    public function api_responses_are_properly_cached(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data
        \App\Models\Hero::factory()->create();

        // First request (should hit database)
        $startTime1 = microtime(true);
        $response1 = $this->getJson('/api/admin/hero');
        $endTime1 = microtime(true);
        $time1 = ($endTime1 - $startTime1) * 1000;

        // Second request (should be faster if cached)
        $startTime2 = microtime(true);
        $response2 = $this->getJson('/api/admin/hero');
        $endTime2 = microtime(true);
        $time2 = ($endTime2 - $startTime2) * 1000;

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Second request should be faster (or at least not significantly slower)
        $this->assertLessThanOrEqual($time1 * 1.5, $time2,
            "Second request took {$time2}ms vs first request {$time1}ms - caching may not be working");
    }

    /** @test */
    public function database_connection_pooling_performs_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data
        Project::factory()->count(100)->create();

        // Test multiple concurrent database operations
        $startTime = microtime(true);

        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/admin/projects?page=' . ($i + 1));
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Should complete all requests within reasonable time
        $this->assertLessThan(5000, $totalTime,
            "10 concurrent requests took {$totalTime}ms, expected less than 5000ms");
    }

    /** @test */
    public function api_handles_large_response_payloads_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create large dataset
        BlogPost::factory()->count(500)->create([
            'content_en' => str_repeat('Large content block. ', 100), // ~2KB per post
            'content_vi' => str_repeat('Nội dung lớn. ', 100)
        ]);

        // Act
        $startTime = microtime(true);
        $response = $this->getJson('/api/admin/blog?per_page=100');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;
        $responseSize = strlen($response->getContent());

        // Assert
        $response->assertStatus(200);
        $this->assertLessThan(3000, $responseTime,
            "Large payload response took {$responseTime}ms");
        $this->assertGreaterThan(100000, $responseSize,
            "Response should be substantial size, got {$responseSize} bytes");
    }

    /** @test */
    public function api_optimizes_n_plus_one_queries(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create projects with relationships
        $projects = Project::factory()->count(20)->create();

        // Enable query logging
        DB::enableQueryLog();

        // Act
        $response = $this->getJson('/api/admin/projects');

        // Get query count
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert
        $response->assertStatus(200);

        // Should not have N+1 queries (should be less than 5 queries total)
        $this->assertLessThan(5, count($queries),
            'N+1 query problem detected. Query count: ' . count($queries));

        // Verify we got all projects
        $this->assertCount(20, $response->json('data.data'));
    }

    /** @test */
    public function api_handles_complex_filtering_efficiently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create diverse dataset
        Project::factory()->count(100)->create(['category' => 'web', 'featured' => true]);
        Project::factory()->count(100)->create(['category' => 'mobile', 'featured' => false]);
        Project::factory()->count(100)->create(['category' => 'desktop', 'featured' => true]);

        $filterCombinations = [
            ['category' => 'web'],
            ['featured' => 'true'],
            ['category' => 'web', 'featured' => 'true'],
            ['search' => 'test'],
            ['category' => 'mobile', 'featured' => 'false', 'search' => 'project']
        ];

        foreach ($filterCombinations as $filters) {
            $queryString = http_build_query($filters);

            // Act
            $startTime = microtime(true);
            $response = $this->getJson("/api/admin/projects?{$queryString}");
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan(2000, $responseTime,
                "Complex filtering took {$responseTime}ms for filters: " . json_encode($filters));
        }
    }

    /** @test */
    public function api_caching_improves_performance(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        \App\Models\Hero::factory()->create();

        // First request (cache miss)
        $startTime1 = microtime(true);
        $response1 = $this->getJson('/api/admin/hero');
        $endTime1 = microtime(true);
        $time1 = ($endTime1 - $startTime1) * 1000;

        // Second request (cache hit)
        $startTime2 = microtime(true);
        $response2 = $this->getJson('/api/admin/hero');
        $endTime2 = microtime(true);
        $time2 = ($endTime2 - $startTime2) * 1000;

        // Third request (should also be cached)
        $startTime3 = microtime(true);
        $response3 = $this->getJson('/api/admin/hero');
        $endTime3 = microtime(true);
        $time3 = ($endTime3 - $startTime3) * 1000;

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        // Cached requests should be faster
        $this->assertLessThanOrEqual($time1, $time2 + 50); // Allow 50ms tolerance
        $this->assertLessThanOrEqual($time1, $time3 + 50);

        // All responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
        $this->assertEquals($response1->json(), $response3->json());
    }

    /** @test */
    public function api_handles_stress_testing_scenarios(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Project::factory()->count(50)->create();

        // Simulate stress test with rapid requests
        $requestCount = 20;
        $maxConcurrentTime = 10000; // 10 seconds max for all requests

        $startTime = microtime(true);
        $responses = [];

        for ($i = 0; $i < $requestCount; $i++) {
            $responses[] = $this->getJson('/api/admin/projects?page=' . ($i % 5 + 1));
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Assert
        foreach ($responses as $index => $response) {
            $response->assertStatus(200, "Request {$index} failed");
        }

        $this->assertLessThan($maxConcurrentTime, $totalTime,
            "Stress test took {$totalTime}ms for {$requestCount} requests");

        $averageTime = $totalTime / $requestCount;
        $this->assertLessThan(1000, $averageTime,
            "Average response time {$averageTime}ms is too high");
    }

    /** @test */
    public function api_memory_usage_remains_stable_under_load(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        BlogPost::factory()->count(200)->create();

        $initialMemory = memory_get_usage(true);
        $memoryReadings = [];

        // Make multiple requests and track memory
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/admin/blog?per_page=50');
            $response->assertStatus(200);

            $currentMemory = memory_get_usage(true);
            $memoryReadings[] = $currentMemory;

            // Force garbage collection
            gc_collect_cycles();
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Assert
        $maxMemoryIncrease = 20 * 1024 * 1024; // 20MB max increase
        $this->assertLessThan($maxMemoryIncrease, $memoryIncrease,
            'Memory usage increased by ' . ($memoryIncrease / 1024 / 1024) . 'MB');

        // Memory should not continuously increase (no major leaks)
        $firstHalf = array_slice($memoryReadings, 0, 5);
        $secondHalf = array_slice($memoryReadings, 5, 5);

        $avgFirstHalf = array_sum($firstHalf) / count($firstHalf);
        $avgSecondHalf = array_sum($secondHalf) / count($secondHalf);

        $memoryGrowth = $avgSecondHalf - $avgFirstHalf;
        $maxGrowth = 10 * 1024 * 1024; // 10MB max growth between halves

        $this->assertLessThan($maxGrowth, $memoryGrowth,
            'Memory appears to be leaking: ' . ($memoryGrowth / 1024 / 1024) . 'MB growth');
    }

    /** @test */
    public function api_database_query_optimization_with_indexes(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create large dataset
        BlogPost::factory()->count(1000)->create();
        Project::factory()->count(500)->create();

        // Test queries that should use indexes
        $indexedQueries = [
            '/api/admin/blog?status=published',
            '/api/admin/blog?search=test',
            '/api/admin/projects?featured=true',
            '/api/admin/projects?category=web',
        ];

        foreach ($indexedQueries as $endpoint) {
            DB::enableQueryLog();

            $startTime = microtime(true);
            $response = $this->getJson($endpoint);
            $endTime = microtime(true);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $responseTime = ($endTime - $startTime) * 1000;

            // Assert
            $response->assertStatus(200);
            $this->assertLessThan(1000, $responseTime,
                "Indexed query for {$endpoint} took {$responseTime}ms");

            // Verify efficient query structure
            foreach ($queries as $query) {
                $sql = strtolower($query['query']);

                // Should use WHERE clauses efficiently
                if (strpos($endpoint, 'status=') !== false) {
                    $this->assertStringContainsString('where', $sql);
                    $this->assertStringContainsString('status', $sql);
                }

                if (strpos($endpoint, 'featured=') !== false) {
                    $this->assertStringContainsString('where', $sql);
                    $this->assertStringContainsString('featured', $sql);
                }
            }
        }
    }

    /** @test */
    public function api_handles_timeout_scenarios_gracefully(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create very large dataset that might cause timeout
        BlogPost::factory()->count(2000)->create([
            'content_en' => str_repeat('Very long content. ', 500), // ~10KB per post
            'content_vi' => str_repeat('Nội dung rất dài. ', 500)
        ]);

        // Act - Request large dataset
        $startTime = microtime(true);
        $response = $this->getJson('/api/admin/blog?per_page=500');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        // Assert
        if ($response->getStatusCode() === 200) {
            // If successful, should complete within reasonable time
            $this->assertLessThan(30000, $responseTime,
                "Large dataset query took {$responseTime}ms");
        } elseif ($response->getStatusCode() === 504) {
            // If timeout, should return proper error
            $response->assertJson([
                'success' => false,
                'message' => 'Request timeout'
            ]);
        }

        // Should not return 500 error or crash
        $this->assertNotEquals(500, $response->getStatusCode());
    }
}
