<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\FileUploadService;
use App\Exceptions\Admin\FileUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Mockery;

class FileUploadServiceTest extends TestCase
{
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileUploadService = new FileUploadService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_upload_valid_image(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(1024 * 1024); // 1MB
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
        $file->shouldReceive('getMimeType')->andReturn('image/jpeg');
        $file->shouldReceive('getPathname')->andReturn('/tmp/test.jpg');
        $file->shouldReceive('getClientOriginalName')->andReturn('test.jpg');
        $file->shouldReceive('storeAs')->with('public/test', Mockery::type('string'))->andReturn('public/test/image.jpg');

        Storage::shouldReceive('url')->with('public/test/image.jpg')->andReturn('/storage/test/image.jpg');
        Log::shouldReceive('info')->once();

        // Mock getimagesize function
        $this->mockGlobalFunction('getimagesize', [800, 600, IMAGETYPE_JPEG, 'mime' => 'image/jpeg']);

        // Act
        $result = $this->fileUploadService->uploadImage($file, 'test');

        // Assert
        $this->assertEquals('/storage/test/image.jpg', $result);
    }

    /** @test */
    public function it_throws_exception_for_oversized_image(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(15 * 1024 * 1024); // 15MB (over 10MB limit)

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Image size exceeds maximum allowed size');
        $this->fileUploadService->uploadImage($file, 'test');
    }

    /** @test */
    public function it_throws_exception_for_invalid_image_extension(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(1024 * 1024);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('exe'); // Invalid extension

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Invalid image type');
        $this->fileUploadService->uploadImage($file, 'test');
    }

    /** @test */
    public function it_throws_exception_for_invalid_mime_type(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(1024 * 1024);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
        $file->shouldReceive('getMimeType')->andReturn('application/octet-stream'); // Invalid MIME type

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Invalid file content');
        $this->fileUploadService->uploadImage($file, 'test');
    }

    /** @test */
    public function it_throws_exception_for_corrupted_image(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(1024 * 1024);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
        $file->shouldReceive('getMimeType')->andReturn('image/jpeg');
        $file->shouldReceive('getPathname')->andReturn('/tmp/corrupted.jpg');

        // Mock getimagesize to return false (corrupted image)
        $this->mockGlobalFunction('getimagesize', false);

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('File appears to be corrupted');
        $this->fileUploadService->uploadImage($file, 'test');
    }

    /** @test */
    public function it_can_upload_valid_document(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(5 * 1024 * 1024); // 5MB
        $file->shouldReceive('getClientOriginalExtension')->andReturn('pdf');
        $file->shouldReceive('getClientOriginalName')->andReturn('document.pdf');
        $file->shouldReceive('storeAs')->with('public/documents', Mockery::type('string'))->andReturn('public/documents/doc.pdf');

        Storage::shouldReceive('url')->with('public/documents/doc.pdf')->andReturn('/storage/documents/doc.pdf');
        Log::shouldReceive('info')->once();

        // Act
        $result = $this->fileUploadService->uploadDocument($file, 'documents');

        // Assert
        $this->assertEquals('/storage/documents/doc.pdf', $result);
    }

    /** @test */
    public function it_throws_exception_for_oversized_document(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(25 * 1024 * 1024); // 25MB (over 20MB limit)

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Document size exceeds maximum allowed size');
        $this->fileUploadService->uploadDocument($file, 'documents');
    }

    /** @test */
    public function it_throws_exception_for_executable_file(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getSize')->andReturn(1024 * 1024);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('exe'); // Executable file

        // Act & Assert
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Executable files are not allowed');
        $this->fileUploadService->uploadDocument($file, 'documents');
    }

    /** @test */
    public function it_can_delete_existing_file(): void
    {
        // Arrange
        $path = '/storage/test/image.jpg';
        $actualPath = 'public/test/image.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andReturn(true);
        Storage::shouldReceive('delete')->with($actualPath)->andReturn(true);
        Log::shouldReceive('info')->once();

        // Act
        $result = $this->fileUploadService->deleteFile($path);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_true_when_deleting_non_existent_file(): void
    {
        // Arrange
        $path = '/storage/test/nonexistent.jpg';
        $actualPath = 'public/test/nonexistent.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andReturn(false);

        // Act
        $result = $this->fileUploadService->deleteFile($path);

        // Assert
        $this->assertTrue($result); // Should return true for non-existent files
    }

    /** @test */
    public function it_handles_file_deletion_errors(): void
    {
        // Arrange
        $path = '/storage/test/image.jpg';
        $actualPath = 'public/test/image.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andThrow(new \Exception('Storage error'));
        Log::shouldReceive('error')->once();

        // Act
        $result = $this->fileUploadService->deleteFile($path);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_file_info(): void
    {
        // Arrange
        $path = '/storage/test/image.jpg';
        $actualPath = 'public/test/image.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andReturn(true);
        Storage::shouldReceive('size')->with($actualPath)->andReturn(1024);
        Storage::shouldReceive('lastModified')->with($actualPath)->andReturn(1234567890);
        Storage::shouldReceive('mimeType')->with($actualPath)->andReturn('image/jpeg');

        // Act
        $result = $this->fileUploadService->getFileInfo($path);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($path, $result['path']);
        $this->assertEquals(1024, $result['size']);
        $this->assertEquals(1234567890, $result['last_modified']);
        $this->assertEquals('image/jpeg', $result['mime_type']);
        $this->assertTrue($result['exists']);
    }

    /** @test */
    public function it_returns_null_for_non_existent_file_info(): void
    {
        // Arrange
        $path = '/storage/test/nonexistent.jpg';
        $actualPath = 'public/test/nonexistent.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andReturn(false);

        // Act
        $result = $this->fileUploadService->getFileInfo($path);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_file_info_errors(): void
    {
        // Arrange
        $path = '/storage/test/image.jpg';
        $actualPath = 'public/test/image.jpg';

        Storage::shouldReceive('exists')->with($actualPath)->andThrow(new \Exception('Storage error'));
        Log::shouldReceive('error')->once();

        // Act
        $result = $this->fileUploadService->getFileInfo($path);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_generates_secure_filename(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->fileUploadService);
        $method = $reflection->getMethod('generateSecureFilename');
        $method->setAccessible(true);

        // Act
        $filename = $method->invoke($this->fileUploadService, $file);

        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-zA-Z0-9]{8}\.jpg$/', $filename);
    }

    /**
     * Mock a global function for testing
     */
    private function mockGlobalFunction(string $functionName, $returnValue): void
    {
        if (!function_exists($functionName . '_original')) {
            eval("function {$functionName}_original() { return call_user_func_array('{$functionName}', func_get_args()); }");
        }

        $mock = Mockery::mock('alias:' . $functionName);
        $mock->shouldReceive($functionName)->andReturn($returnValue);
    }
}
