<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\BlogService;
use App\Services\Admin\FileUploadService;
use App\Repositories\Contracts\BlogRepositoryInterface;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;

class BlogServiceTest extends TestCase
{
    private BlogService $blogService;
    private BlogRepositoryInterface $blogRepository;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blogRepository = Mockery::mock(BlogRepositoryInterface::class);
        $this->fileUploadService = Mockery::mock(FileUploadService::class);
        $this->blogService = new BlogService($this->blogRepository, $this->fileUploadService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_posts_with_pagination(): void
    {
        // Arrange
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->blogRepository
            ->shouldReceive('getPaginated')
            ->once()
            ->with(15, [])
            ->andReturn($paginator);

        // Act
        $result = $this->blogService->getAllPosts();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_can_get_published_posts(): void
    {
        // Arrange
        $posts = new Collection([
            new BlogPost(['id' => 1, 'title_en' => 'Published Post', 'status' => 'published'])
        ]);

        $this->blogRepository
            ->shouldReceive('findPublished')
            ->once()
            ->andReturn($posts);

        // Act
        $result = $this->blogService->getPublishedPosts();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_draft_posts(): void
    {
        // Arrange
        $posts = new Collection([
            new BlogPost(['id' => 1, 'title_en' => 'Draft Post', 'status' => 'draft'])
        ]);

        $this->blogRepository
            ->shouldReceive('findDrafts')
            ->once()
            ->andReturn($posts);

        // Act
        $result = $this->blogService->getDraftPosts();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_posts_by_status(): void
    {
        // Arrange
        $posts = new Collection([
            new BlogPost(['id' => 1, 'title_en' => 'Test Post', 'status' => 'published'])
        ]);

        $this->blogRepository
            ->shouldReceive('findByStatus')
            ->once()
            ->with('published')
            ->andReturn($posts);

        // Act
        $result = $this->blogService->getPostsByStatus('published');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_post_by_id(): void
    {
        // Arrange
        $post = new BlogPost(['id' => 1, 'title_en' => 'Test Post']);

        $this->blogRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($post);

        // Act
        $result = $this->blogService->getPostById(1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('Test Post', $result->title_en);
    }

    /** @test */
    public function it_can_create_post_without_thumbnail(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung bài viết',
            'content_en' => 'Post content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt'
        ];

        $post = new BlogPost(array_merge($data, ['id' => 1, 'status' => 'draft']));

        $this->blogRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['status' => 'draft']))
            ->andReturn($post);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->createPost($data, null, 1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('Test Post', $result->title_en);
        $this->assertEquals('draft', $result->status);
    }

    /** @test */
    public function it_can_create_post_with_thumbnail(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung bài viết',
            'content_en' => 'Post content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt'
        ];

        $thumbnail = Mockery::mock(UploadedFile::class);
        $thumbnailPath = '/storage/blog/thumbnail.jpg';

        $this->fileUploadService
            ->shouldReceive('uploadImage')
            ->once()
            ->with($thumbnail, 'blog')
            ->andReturn($thumbnailPath);

        $post = new BlogPost(array_merge($data, ['id' => 1, 'thumbnail' => $thumbnailPath, 'status' => 'draft']));

        $this->blogRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['thumbnail' => $thumbnailPath, 'status' => 'draft']))
            ->andReturn($post);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->createPost($data, $thumbnail, 1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals($thumbnailPath, $result->thumbnail);
    }

    /** @test */
    public function it_can_update_post(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Bài viết cập nhật',
            'title_en' => 'Updated Post',
            'content_vi' => 'Nội dung cập nhật',
            'content_en' => 'Updated content',
            'excerpt_vi' => 'Tóm tắt cập nhật',
            'excerpt_en' => 'Updated excerpt'
        ];

        $post = new BlogPost(array_merge($data, ['id' => 1, 'status' => 'draft']));

        $this->blogRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn($post);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->updatePost(1, $data, null, 1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('Updated Post', $result->title_en);
    }

    /** @test */
    public function it_can_delete_post(): void
    {
        // Arrange
        $post = new BlogPost(['id' => 1, 'title_en' => 'Test Post']);

        $this->blogRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->blogRepository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->deletePost(1, 1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_post(): void
    {
        // Arrange
        $this->blogRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act
        $result = $this->blogService->deletePost(999, 1);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_publish_post(): void
    {
        // Arrange
        $post = new BlogPost([
            'id' => 1,
            'title_vi' => 'Bài viết',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung',
            'content_en' => 'Content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt',
            'status' => 'draft'
        ]);

        $publishedPost = new BlogPost([
            'id' => 1,
            'title_vi' => 'Bài viết',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung',
            'content_en' => 'Content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt',
            'status' => 'published',
            'published_at' => now()
        ]);

        $this->blogRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->blogRepository
            ->shouldReceive('publish')
            ->once()
            ->with(1)
            ->andReturn($publishedPost);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->publishPost(1, 1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('published', $result->status);
    }

    /** @test */
    public function it_throws_exception_when_publishing_non_existent_post(): void
    {
        // Arrange
        $this->blogRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->blogService->publishPost(999, 1);
    }

    /** @test */
    public function it_can_unpublish_post(): void
    {
        // Arrange
        $post = new BlogPost(['id' => 1, 'status' => 'draft']);

        $this->blogRepository
            ->shouldReceive('unpublish')
            ->once()
            ->with(1)
            ->andReturn($post);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->blogService->unpublishPost(1, 1);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('draft', $result->status);
    }

    /** @test */
    public function it_validates_post_data(): void
    {
        // Arrange
        $validData = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung bài viết',
            'content_en' => 'Post content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt',
            'status' => 'draft',
            'tags' => ['tag1', 'tag2']
        ];

        // Act & Assert - Should not throw exception
        $this->blogService->validatePostData($validData);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => '', // Required field missing
            'title_en' => 'Test Post',
            'content_vi' => 'Content',
            'content_en' => 'Content'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->blogService->validatePostData($invalidData);
    }

    /** @test */
    public function it_validates_status_values(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'status' => 'invalid-status' // Invalid status
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->blogService->validatePostData($invalidData);
    }

    /** @test */
    public function it_validates_post_for_publishing(): void
    {
        // Arrange
        $validPost = new BlogPost([
            'title_vi' => 'Bài viết',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung',
            'content_en' => 'Content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt'
        ]);

        // Act & Assert - Should not throw exception
        $this->blogService->validatePostForPublishing($validPost);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_post_for_publishing_with_missing_fields(): void
    {
        // Arrange
        $invalidPost = new BlogPost([
            'title_vi' => '', // Missing Vietnamese title
            'title_en' => 'Test Post',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'excerpt_vi' => 'Excerpt',
            'excerpt_en' => 'Excerpt'
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->blogService->validatePostForPublishing($invalidPost);
    }

    /** @test */
    public function it_logs_blog_actions(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'excerpt_vi' => 'Excerpt',
            'excerpt_en' => 'Excerpt'
        ];

        $post = new BlogPost(array_merge($data, ['id' => 1, 'status' => 'draft']));

        $this->blogRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($post);

        Log::shouldReceive('info')
            ->once()
            ->with('Blog service action', Mockery::type('array'));

        // Act
        $this->blogService->createPost($data, null, 1);

        // Assert - Log expectation is verified by Mockery
        $this->assertTrue(true);
    }
}
