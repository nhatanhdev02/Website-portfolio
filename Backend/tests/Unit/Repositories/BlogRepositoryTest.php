<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\BlogRepository;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class BlogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BlogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BlogRepository(new BlogPost());
    }

    /** @test */
    public function it_can_find_published_posts(): void
    {
        // Arrange
        $publishedPost1 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(2)
        ]);
        $publishedPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(1)
        ]);
        $draftPost = BlogPost::factory()->create(['status' => 'draft']);

        // Act
        $result = $this->repository->findPublished();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($publishedPost1));
        $this->assertTrue($result->contains($publishedPost2));
        $this->assertFalse($result->contains($draftPost));

        // Verify ordering by published_at desc
        $this->assertEquals($publishedPost2->id, $result->first()->id);
        $this->assertEquals($publishedPost1->id, $result->last()->id);
    }

    /** @test */
    public function it_can_find_draft_posts(): void
    {
        // Arrange
        $draftPost1 = BlogPost::factory()->create([
            'status' => 'draft',
            'updated_at' => Carbon::now()->subHours(2)
        ]);
        $draftPost2 = BlogPost::factory()->create([
            'status' => 'draft',
            'updated_at' => Carbon::now()->subHours(1)
        ]);
        $publishedPost = BlogPost::factory()->create(['status' => 'published']);

        // Act
        $result = $this->repository->findDrafts();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($draftPost1));
        $this->assertTrue($result->contains($draftPost2));
        $this->assertFalse($result->contains($publishedPost));

        // Verify ordering by updated_at desc
        $this->assertEquals($draftPost2->id, $result->first()->id);
        $this->assertEquals($draftPost1->id, $result->last()->id);
    }

    /** @test */
    public function it_can_find_posts_by_status(): void
    {
        // Arrange
        $publishedPost1 = BlogPost::factory()->create(['status' => 'published']);
        $publishedPost2 = BlogPost::factory()->create(['status' => 'published']);
        $draftPost = BlogPost::factory()->create(['status' => 'draft']);

        // Act
        $publishedResult = $this->repository->findByStatus('published');
        $draftResult = $this->repository->findByStatus('draft');

        // Assert
        $this->assertInstanceOf(Collection::class, $publishedResult);
        $this->assertCount(2, $publishedResult);
        $this->assertTrue($publishedResult->contains($publishedPost1));
        $this->assertTrue($publishedResult->contains($publishedPost2));

        $this->assertInstanceOf(Collection::class, $draftResult);
        $this->assertCount(1, $draftResult);
        $this->assertTrue($draftResult->contains($draftPost));
    }

    /** @test */
    public function it_can_publish_post(): void
    {
        // Arrange
        $post = BlogPost::factory()->create([
            'status' => 'draft',
            'published_at' => null
        ]);

        // Act
        $result = $this->repository->publish($post->id);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('published', $result->status);
        $this->assertNotNull($result->published_at);

        // Verify in database
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => 'published'
        ]);

        $updatedPost = BlogPost::find($post->id);
        $this->assertNotNull($updatedPost->published_at);
    }

    /** @test */
    public function it_can_unpublish_post(): void
    {
        // Arrange
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => Carbon::now()
        ]);

        // Act
        $result = $this->repository->unpublish($post->id);

        // Assert
        $this->assertInstanceOf(BlogPost::class, $result);
        $this->assertEquals('draft', $result->status);
        $this->assertNull($result->published_at);

        // Verify in database
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => 'draft',
            'published_at' => null
        ]);
    }

    /** @test */
    public function it_can_get_paginated_posts(): void
    {
        // Arrange
        BlogPost::factory()->count(20)->create(['status' => 'draft']);

        // Act
        $result = $this->repository->getPaginated();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
        $this->assertCount(15, $result->items());
    }

    /** @test */
    public function it_can_get_paginated_posts_with_custom_per_page(): void
    {
        // Arrange
        BlogPost::factory()->count(20)->create(['status' => 'draft']);

        // Act
        $result = $this->repository->getPaginated(10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(20, $result->total());
        $this->assertCount(10, $result->items());
    }

    /** @test */
    public function it_applies_status_filter(): void
    {
        // Arrange
        BlogPost::factory()->count(5)->create(['status' => 'published']);
        BlogPost::factory()->count(3)->create(['status' => 'draft']);

        $filters = ['status' => 'published'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());

        // Verify all items are published
        foreach ($result->items() as $post) {
            $this->assertEquals('published', $post->status);
        }
    }

    /** @test */
    public function it_applies_search_filter(): void
    {
        // Arrange
        $matchingPost1 = BlogPost::factory()->create([
            'title_en' => 'Laravel Testing Guide',
            'content_en' => 'This is about testing'
        ]);
        $matchingPost2 = BlogPost::factory()->create([
            'title_vi' => 'Hướng dẫn test',
            'content_vi' => 'Nội dung về testing'
        ]);
        $nonMatchingPost = BlogPost::factory()->create([
            'title_en' => 'Vue.js Components',
            'content_en' => 'About Vue components'
        ]);

        $filters = ['search' => 'test'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingPost1->id, $resultIds);
        $this->assertContains($matchingPost2->id, $resultIds);
        $this->assertNotContains($nonMatchingPost->id, $resultIds);
    }

    /** @test */
    public function it_applies_tags_filter_with_array(): void
    {
        // Arrange
        $matchingPost = BlogPost::factory()->create(['tags' => ['laravel', 'php', 'testing']]);
        $partialMatchPost = BlogPost::factory()->create(['tags' => ['laravel', 'vue']]);
        $nonMatchingPost = BlogPost::factory()->create(['tags' => ['javascript', 'react']]);

        $filters = ['tags' => ['laravel', 'php']];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total()); // Only posts with both tags

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingPost->id, $resultIds);
        $this->assertNotContains($partialMatchPost->id, $resultIds);
        $this->assertNotContains($nonMatchingPost->id, $resultIds);
    }

    /** @test */
    public function it_applies_tags_filter_with_string(): void
    {
        // Arrange
        $matchingPost1 = BlogPost::factory()->create(['tags' => ['laravel', 'php']]);
        $matchingPost2 = BlogPost::factory()->create(['tags' => ['laravel', 'vue']]);
        $nonMatchingPost = BlogPost::factory()->create(['tags' => ['javascript', 'react']]);

        $filters = ['tags' => 'laravel'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingPost1->id, $resultIds);
        $this->assertContains($matchingPost2->id, $resultIds);
        $this->assertNotContains($nonMatchingPost->id, $resultIds);
    }

    /** @test */
    public function it_applies_date_from_filter(): void
    {
        // Arrange
        $oldPost = BlogPost::factory()->create(['created_at' => Carbon::parse('2022-12-31')]);
        $newPost = BlogPost::factory()->create(['created_at' => Carbon::parse('2023-01-15')]);

        $filters = ['date_from' => '2023-01-01'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($newPost->id, $resultIds);
        $this->assertNotContains($oldPost->id, $resultIds);
    }

    /** @test */
    public function it_applies_date_to_filter(): void
    {
        // Arrange
        $oldPost = BlogPost::factory()->create(['created_at' => Carbon::parse('2023-06-15')]);
        $newPost = BlogPost::factory()->create(['created_at' => Carbon::parse('2024-01-15')]);

        $filters = ['date_to' => '2023-12-31'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($oldPost->id, $resultIds);
        $this->assertNotContains($newPost->id, $resultIds);
    }

    /** @test */
    public function it_uses_published_at_ordering_for_published_posts(): void
    {
        // Arrange
        $post1 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(2)
        ]);
        $post2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(1)
        ]);

        $filters = ['status' => 'published'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        // Verify ordering - most recent published_at first
        $items = $result->items();
        $this->assertEquals($post2->id, $items[0]->id);
        $this->assertEquals($post1->id, $items[1]->id);
    }

    /** @test */
    public function it_uses_updated_at_ordering_for_non_published_posts(): void
    {
        // Arrange
        $post1 = BlogPost::factory()->create([
            'status' => 'draft',
            'updated_at' => Carbon::now()->subHours(2)
        ]);
        $post2 = BlogPost::factory()->create([
            'status' => 'draft',
            'updated_at' => Carbon::now()->subHours(1)
        ]);

        $filters = ['status' => 'draft'];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        // Verify ordering - most recent updated_at first
        $items = $result->items();
        $this->assertEquals($post2->id, $items[0]->id);
        $this->assertEquals($post1->id, $items[1]->id);
    }

    /** @test */
    public function it_applies_multiple_filters(): void
    {
        // Arrange
        $matchingPost = BlogPost::factory()->create([
            'status' => 'published',
            'title_en' => 'Laravel Testing Guide',
            'tags' => ['laravel', 'testing'],
            'created_at' => Carbon::parse('2023-06-15'),
            'published_at' => Carbon::parse('2023-06-15')
        ]);

        $nonMatchingPost1 = BlogPost::factory()->create([
            'status' => 'draft', // Wrong status
            'title_en' => 'Laravel Testing Guide',
            'tags' => ['laravel', 'testing'],
            'created_at' => Carbon::parse('2023-06-15')
        ]);

        $nonMatchingPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'title_en' => 'Vue.js Guide', // Wrong search term
            'tags' => ['laravel', 'testing'],
            'created_at' => Carbon::parse('2023-06-15'),
            'published_at' => Carbon::parse('2023-06-15')
        ]);

        $filters = [
            'status' => 'published',
            'search' => 'test',
            'tags' => ['laravel'],
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31'
        ];

        // Act
        $result = $this->repository->getPaginated(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingPost->id, $resultIds);
        $this->assertNotContains($nonMatchingPost1->id, $resultIds);
        $this->assertNotContains($nonMatchingPost2->id, $resultIds);
    }

    /** @test */
    public function it_tests_base_repository_crud_operations(): void
    {
        // Test findById
        $post = BlogPost::factory()->create();
        $found = $this->repository->findById($post->id);
        $this->assertInstanceOf(BlogPost::class, $found);
        $this->assertEquals($post->id, $found->id);

        // Test findById with non-existent ID
        $notFound = $this->repository->findById(999);
        $this->assertNull($notFound);

        // Test update
        $updateData = ['title_en' => 'Updated Title'];
        $updated = $this->repository->update($post->id, $updateData);
        $this->assertEquals('Updated Title', $updated->title_en);

        // Test delete
        $deleted = $this->repository->delete($post->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    /** @test */
    public function it_tests_model_scopes(): void
    {
        // Arrange
        $publishedPost = BlogPost::factory()->create(['status' => 'published']);
        $draftPost = BlogPost::factory()->create(['status' => 'draft']);

        // Test published scope through repository methods
        $publishedPosts = $this->repository->findPublished();
        $this->assertCount(1, $publishedPosts);
        $this->assertTrue($publishedPosts->contains($publishedPost));
        $this->assertFalse($publishedPosts->contains($draftPost));

        // Test draft scope through repository methods
        $draftPosts = $this->repository->findDrafts();
        $this->assertCount(1, $draftPosts);
        $this->assertTrue($draftPosts->contains($draftPost));
        $this->assertFalse($draftPosts->contains($publishedPost));
    }
}
