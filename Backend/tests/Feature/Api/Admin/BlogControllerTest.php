<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class BlogControllerTest extends TestCase
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
    public function authenticated_admin_can_get_all_blog_posts(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        BlogPost::factory()->count(5)->create();

        // Act
        $response = $this->getJson('/api/admin/blog');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title_vi',
                                'title_en',
                                'content_vi',
                                'content_en',
                                'excerpt_vi',
                                'excerpt_en',
                                'thumbnail',
                                'status',
                                'published_at',
                                'tags',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);
    }

    /** @test */
    public function authenticated_admin_can_filter_posts_by_status(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        BlogPost::factory()->create(['status' => 'published']);
        BlogPost::factory()->create(['status' => 'draft']);
        BlogPost::factory()->create(['status' => 'published']);

        // Act
        $response = $this->getJson('/api/admin/blog?status=published');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    /** @test */
    public function authenticated_admin_can_search_posts(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        BlogPost::factory()->create(['title_en' => 'Laravel Tutorial']);
        BlogPost::factory()->create(['title_en' => 'Vue.js Guide']);
        BlogPost::factory()->create(['title_en' => 'Laravel Best Practices']);

        // Act
        $response = $this->getJson('/api/admin/blog?search=Laravel');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    /** @test */
    public function authenticated_admin_can_get_single_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create([
            'title_en' => 'Test Blog Post',
            'status' => 'draft'
        ]);

        // Act
        $response = $this->getJson("/api/admin/blog/{$post->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title_vi',
                        'title_en',
                        'content_vi',
                        'content_en',
                        'excerpt_vi',
                        'excerpt_en',
                        'thumbnail',
                        'status',
                        'published_at',
                        'tags',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title_en' => 'Test Blog Post',
                        'status' => 'draft'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_create_blog_post_without_thumbnail(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $postData = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Blog Post',
            'content_vi' => 'Nội dung bài viết test',
            'content_en' => 'Test blog post content',
            'excerpt_vi' => 'Tóm tắt bài viết',
            'excerpt_en' => 'Blog post excerpt',
            'status' => 'draft',
            'tags' => ['laravel', 'php', 'web-development']
        ];

        // Act
        $response = $this->postJson('/api/admin/blog', $postData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'title_vi',
                        'title_en',
                        'content_vi',
                        'content_en',
                        'excerpt_vi',
                        'excerpt_en',
                        'status',
                        'tags'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post created successfully',
                    'data' => [
                        'title_en' => 'Test Blog Post',
                        'status' => 'draft',
                        'tags' => ['laravel', 'php', 'web-development']
                    ]
                ]);

        $this->assertDatabaseHas('blog_posts', [
            'title_en' => 'Test Blog Post',
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_create_blog_post_with_thumbnail(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg', 400, 300);

        $postData = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Blog Post',
            'content_vi' => 'Nội dung bài viết test',
            'content_en' => 'Test blog post content',
            'excerpt_vi' => 'Tóm tắt bài viết',
            'excerpt_en' => 'Blog post excerpt',
            'status' => 'draft',
            'tags' => ['laravel', 'php'],
            'thumbnail' => $thumbnail
        ];

        // Act
        $response = $this->postJson('/api/admin/blog', $postData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post created successfully'
                ]);

        $this->assertDatabaseHas('blog_posts', [
            'title_en' => 'Test Blog Post',
            'status' => 'draft'
        ]);

        // Verify thumbnail was stored
        $post = BlogPost::where('title_en', 'Test Blog Post')->first();
        $this->assertNotNull($post->thumbnail);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $post->thumbnail));
    }

    /** @test */
    public function authenticated_admin_can_update_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create([
            'title_en' => 'Original Post',
            'status' => 'draft'
        ]);

        $updateData = [
            'title_vi' => 'Bài viết cập nhật',
            'title_en' => 'Updated Post',
            'content_vi' => 'Nội dung cập nhật',
            'content_en' => 'Updated content',
            'excerpt_vi' => 'Tóm tắt cập nhật',
            'excerpt_en' => 'Updated excerpt',
            'status' => 'draft',
            'tags' => ['updated', 'test']
        ];

        // Act
        $response = $this->putJson("/api/admin/blog/{$post->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post updated successfully',
                    'data' => [
                        'title_en' => 'Updated Post',
                        'tags' => ['updated', 'test']
                    ]
                ]);

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title_en' => 'Updated Post'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_delete_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create();

        // Act
        $response = $this->deleteJson("/api/admin/blog/{$post->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post deleted successfully'
                ]);

        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id
        ]);
    }

    /** @test */
    public function authenticated_admin_can_publish_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create([
            'status' => 'draft',
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Post',
            'content_vi' => 'Nội dung',
            'content_en' => 'Content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt'
        ]);

        // Act
        $response = $this->patchJson("/api/admin/blog/{$post->id}/publish");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post published successfully',
                    'data' => [
                        'status' => 'published'
                    ]
                ]);

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => 'published'
        ]);

        $post->refresh();
        $this->assertNotNull($post->published_at);
    }

    /** @test */
    public function authenticated_admin_can_unpublish_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()
        ]);

        // Act
        $response = $this->patchJson("/api/admin/blog/{$post->id}/unpublish");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post unpublished successfully',
                    'data' => [
                        'status' => 'draft'
                    ]
                ]);

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => 'draft',
            'published_at' => null
        ]);
    }

    /** @test */
    public function blog_post_creation_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/blog', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'title_vi',
                    'title_en',
                    'content_vi',
                    'content_en'
                ]);
    }

    /** @test */
    public function blog_post_creation_validates_status_values(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'status' => 'invalid-status' // Invalid status
        ];

        // Act
        $response = $this->postJson('/api/admin/blog', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function blog_post_creation_validates_thumbnail_file(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024); // Not an image

        $postData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'thumbnail' => $invalidFile
        ];

        // Act
        $response = $this->postJson('/api/admin/blog', $postData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['thumbnail']);
    }

    /** @test */
    public function publishing_validates_required_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $post = BlogPost::factory()->create([
            'status' => 'draft',
            'title_vi' => '', // Missing Vietnamese title
            'title_en' => 'Test Post',
            'content_vi' => 'Content',
            'content_en' => 'Content',
            'excerpt_vi' => 'Excerpt',
            'excerpt_en' => 'Excerpt'
        ]);

        // Act
        $response = $this->patchJson("/api/admin/blog/{$post->id}/publish");

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_blog_posts(): void
    {
        // Act
        $response = $this->getJson('/api/admin/blog');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function returns_404_for_non_existent_blog_post(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/blog/999');

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Blog post not found'
                ]);
    }

    /** @test */
    public function blog_posts_are_paginated(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        BlogPost::factory()->count(20)->create();

        // Act
        $response = $this->getJson('/api/admin/blog?per_page=5');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ]);

        $this->assertCount(5, $response->json('data.data'));
        $this->assertEquals(5, $response->json('data.per_page'));
        $this->assertEquals(20, $response->json('data.total'));
    }
}
