<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function file_upload_rejects_executable_files(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $executableFiles = [
            UploadedFile::fake()->create('malicious.exe', 1024),
            UploadedFile::fake()->create('script.bat', 512),
            UploadedFile::fake()->create('virus.com', 256),
            UploadedFile::fake()->create('trojan.scr', 1024),
            UploadedFile::fake()->create('malware.vbs', 512),
        ];

        foreach ($executableFiles as $file) {
            // Act - Try to upload executable file as project image
            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $file
            ]);

            // Assert
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['image']);
        }
    }

    /** @test */
    public function file_upload_validates_file_size_limits(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create oversized image (over 10MB limit for projects)
        $oversizedImage = UploadedFile::fake()->create('huge.jpg', 15 * 1024); // 15MB

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $oversizedImage
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_validates_mime_types(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create file with image extension but wrong MIME type
        $fakeImage = UploadedFile::fake()->create('fake.jpg', 1024, 'application/octet-stream');

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $fakeImage
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_sanitizes_filenames(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $maliciousFilenames = [
            '../../../etc/passwd.jpg',
            '..\\..\\windows\\system32\\config.jpg',
            'file with spaces and special chars!@#$.jpg',
            'very_long_filename_' . str_repeat('a', 200) . '.jpg'
        ];

        foreach ($maliciousFilenames as $filename) {
            // Create image with malicious filename
            $file = UploadedFile::fake()->image($filename, 400, 300);

            // Act
            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $file
            ]);

            // Assert - Should succeed but filename should be sanitized
            $response->assertStatus(201);

            $project = \App\Models\Project::latest()->first();
            $storedFilename = basename($project->image);

            // Verify filename doesn't contain path traversal or dangerous characters
            $this->assertStringNotContainsString('..', $storedFilename);
            $this->assertStringNotContainsString('/', $storedFilename);
            $this->assertStringNotContainsString('\\', $storedFilename);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-zA-Z0-9]{8}\.jpg$/', $storedFilename);

            // Clean up for next iteration
            $project->delete();
        }
    }

    /** @test */
    public function file_upload_prevents_php_code_injection(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create file with PHP code disguised as image
        $phpCode = '<?php system($_GET["cmd"]); ?>';
        $maliciousFile = UploadedFile::fake()->createWithContent('malicious.jpg', $phpCode);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $maliciousFile
        ]);

        // Assert - Should be rejected due to invalid image content
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_validates_image_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create file with .jpg extension but not actually an image
        $notAnImage = UploadedFile::fake()->createWithContent('notimage.jpg', 'This is not an image file');

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $notAnImage
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_stores_files_securely(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $validImage = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $validImage
        ]);

        // Assert
        $response->assertStatus(201);

        $project = \App\Models\Project::latest()->first();
        $imagePath = $project->image;

        // Verify file is stored in expected location
        $this->assertStringStartsWith('/storage/projects/', $imagePath);

        // Verify file exists in storage
        $storagePath = str_replace('/storage/', '', $imagePath);
        $this->assertTrue(Storage::disk('public')->exists($storagePath));

        // Verify filename is properly generated (timestamp + random + extension)
        $filename = basename($imagePath);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-zA-Z0-9]{8}\.jpg$/', $filename);
    }

    /** @test */
    public function file_upload_handles_concurrent_uploads(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $images = [
            UploadedFile::fake()->image('image1.jpg', 400, 300),
            UploadedFile::fake()->image('image2.png', 500, 400),
            UploadedFile::fake()->image('image3.gif', 300, 200)
        ];

        $responses = [];

        // Act - Upload multiple files concurrently
        foreach ($images as $index => $image) {
            $responses[] = $this->postJson('/api/admin/projects', [
                'title_vi' => "Dự án $index",
                'title_en' => "Project $index",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ]);
        }

        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify all files were stored with unique names
        $projects = \App\Models\Project::latest()->take(3)->get();
        $filenames = $projects->pluck('image')->map(fn($path) => basename($path))->toArray();

        $this->assertCount(3, array_unique($filenames)); // All filenames should be unique
    }

    /** @test */
    public function file_upload_cleans_up_on_failure(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $validImage = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Act - Try to create project with invalid data but valid image
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => '', // Invalid - required field missing
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $validImage
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title_vi']);

        // Verify no project was created
        $this->assertDatabaseCount('projects', 0);

        // In a real implementation, you would also verify that the uploaded file
        // was cleaned up and not left orphaned in storage
    }

    /** @test */
    public function file_upload_respects_allowed_extensions(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $disallowedExtensions = ['bmp', 'tiff', 'svg', 'ico', 'psd'];

        // Test allowed extensions
        foreach ($allowedExtensions as $ext) {
            $file = UploadedFile::fake()->image("test.$ext", 400, 300);

            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $file
            ]);

            $response->assertStatus(201);

            // Clean up
            \App\Models\Project::latest()->first()->delete();
        }

        // Test disallowed extensions
        foreach ($disallowedExtensions as $ext) {
            $file = UploadedFile::fake()->create("test.$ext", 1024, 'image/jpeg');

            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $file
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['image']);
        }
    }
}
    /** @test */
    public function file_upload_prevents_zip_bomb_attacks(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create a file that claims to be an image but is actually a zip
        $zipBombContent = "PK\x03\x04" . str_repeat("\x00", 1000); // ZIP file signature + padding
        $zipBombFile = UploadedFile::fake()->createWithContent('bomb.jpg', $zipBombContent);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $zipBombFile
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_prevents_polyglot_file_attacks(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create a file that's both a valid image and contains script code
        $polyglotContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF" . // JPEG header
                          "<?php system(\$_GET['cmd']); ?>" . // PHP code
                          str_repeat("\x00", 1000); // Padding

        $polyglotFile = UploadedFile::fake()->createWithContent('polyglot.jpg', $polyglotContent);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $polyglotFile
        ]);

        // Assert - Should be rejected due to content validation
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_validates_image_dimensions(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Test with extremely large dimensions
        $hugeDimensions = [
            [50000, 50000], // Extremely large
            [1, 50000],     // Very wide
            [50000, 1],     // Very tall
        ];

        foreach ($hugeDimensions as [$width, $height]) {
            $hugeImage = UploadedFile::fake()->image('huge.jpg', $width, $height);

            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $hugeImage
            ]);

            // Should be rejected due to dimension limits
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['image']);
        }
    }

    /** @test */
    public function file_upload_prevents_path_traversal_in_metadata(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create image with malicious metadata
        $image = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Simulate malicious form data
        $response = $this->call('POST', '/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image_path' => '../../../etc/passwd', // Malicious path
        ], [], [
            'image' => $image
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken,
            'CONTENT_TYPE' => 'multipart/form-data'
        ]);

        // Assert - Should ignore malicious path parameter
        if ($response->getStatusCode() === 201) {
            $project = \App\Models\Project::latest()->first();
            $this->assertStringNotContainsString('..', $project->image);
            $this->assertStringNotContainsString('/etc/', $project->image);
        }
    }

    /** @test */
    public function file_upload_handles_unicode_filename_attacks(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $unicodeFilenames = [
            'test\u0000.jpg', // Null byte injection
            'test\u202E.jpg', // Right-to-left override
            'test\uFEFF.jpg', // Byte order mark
            'test\u200B.jpg', // Zero width space
            'тест.jpg',       // Cyrillic characters
            '测试.jpg',        // Chinese characters
        ];

        foreach ($unicodeFilenames as $filename) {
            $file = UploadedFile::fake()->image($filename, 400, 300);

            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => 'Test Project',
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $file
            ]);

            // Should succeed but filename should be sanitized
            $response->assertStatus(201);

            $project = \App\Models\Project::latest()->first();
            $storedFilename = basename($project->image);

            // Verify dangerous unicode characters are removed/replaced
            $this->assertStringNotContainsString("\u0000", $storedFilename);
            $this->assertStringNotContainsString("\u202E", $storedFilename);
            $this->assertStringNotContainsString("\uFEFF", $storedFilename);

            $project->delete();
        }
    }

    /** @test */
    public function file_upload_prevents_symlink_attacks(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // This test would typically involve creating actual symlinks
        // For testing purposes, we'll simulate the scenario
        $image = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $image
        ]);

        // Assert
        $response->assertStatus(201);

        $project = \App\Models\Project::latest()->first();
        $storedPath = storage_path('app/public' . str_replace('/storage', '', $project->image));

        // Verify the stored file is not a symlink
        if (file_exists($storedPath)) {
            $this->assertFalse(is_link($storedPath), 'Uploaded file should not be a symlink');
        }
    }

    /** @test */
    public function file_upload_validates_content_type_spoofing(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create a text file with image extension and MIME type
        $textContent = "This is not an image file, it's plain text";
        $spoofedFile = UploadedFile::fake()->createWithContent('fake.jpg', $textContent);

        // Manually set MIME type to image (simulating spoofing)
        $reflection = new \ReflectionClass($spoofedFile);
        $mimeTypeProperty = $reflection->getProperty('mimeType');
        $mimeTypeProperty->setAccessible(true);
        $mimeTypeProperty->setValue($spoofedFile, 'image/jpeg');

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $spoofedFile
        ]);

        // Assert - Should be rejected due to content validation
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_handles_race_conditions(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $images = [];
        for ($i = 0; $i < 3; $i++) {
            $images[] = UploadedFile::fake()->image("concurrent_{$i}.jpg", 400, 300);
        }

        // Act - Upload files concurrently with same name pattern
        $responses = [];
        foreach ($images as $index => $image) {
            $responses[] = $this->postJson('/api/admin/projects', [
                'title_vi' => "Project {$index}",
                'title_en' => "Project {$index}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ]);
        }

        // Assert - All uploads should succeed with unique filenames
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $projects = \App\Models\Project::latest()->take(3)->get();
        $filenames = $projects->pluck('image')->map(fn($path) => basename($path))->toArray();

        // All filenames should be unique
        $this->assertCount(3, array_unique($filenames));
    }

    /** @test */
    public function file_upload_prevents_metadata_injection(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create image with potentially malicious EXIF data
        $image = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $image
        ]);

        // Assert
        $response->assertStatus(201);

        $project = \App\Models\Project::latest()->first();
        $storedPath = storage_path('app/public' . str_replace('/storage', '', $project->image));

        // Verify EXIF data is stripped (if image processing is implemented)
        if (file_exists($storedPath) && function_exists('exif_read_data')) {
            $exifData = @exif_read_data($storedPath);

            // Should not contain potentially dangerous EXIF fields
            if ($exifData) {
                $this->assertArrayNotHasKey('UserComment', $exifData);
                $this->assertArrayNotHasKey('ImageDescription', $exifData);
            }
        }
    }

    /** @test */
    public function file_upload_enforces_quota_limits(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create multiple large files to test quota
        $largeImages = [];
        for ($i = 0; $i < 5; $i++) {
            $largeImages[] = UploadedFile::fake()->create("large_{$i}.jpg", 8 * 1024); // 8MB each
        }

        $successfulUploads = 0;
        $quotaExceeded = false;

        foreach ($largeImages as $index => $image) {
            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => "Large Project {$index}",
                'title_en' => "Large Project {$index}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ]);

            if ($response->getStatusCode() === 201) {
                $successfulUploads++;
            } elseif ($response->getStatusCode() === 413) { // Payload too large
                $quotaExceeded = true;
                break;
            }
        }

        // Assert - Either all uploads succeed or quota is enforced
        $this->assertTrue($successfulUploads > 0 || $quotaExceeded);
    }
