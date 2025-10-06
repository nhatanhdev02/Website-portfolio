<?php

namespace App\Services\Admin;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class BlogService
{
    public function __construct(
        private BlogRepositoryInterface $blogRepository,
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Get all blog posts with optional filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->blogRepository->getPaginated($perPage, $filters);
    }

    /**
     * Get published posts
     *
     * @return Collection
     */
    public function getPublishedPosts(): Collection
    {
        return $this->blogRepository->findPublished();
    }

    /**
     * Get draft posts
     *
     * @return Collection
     */
    public function getDraftPosts(): Collection
    {
        return $this->blogRepository->findDrafts();
    }

    /**
     * Get posts by status
     *
     * @param string $status
     * @return Collection
     */
    public function getPostsByStatus(string $status): Collection
    {
        return $this->blogRepository->findByStatus($status);
    }

    /**
     * Get blog post by ID
     *
     * @param int $id
     * @return BlogPost|null
     */
    public function getPostById(int $id): ?BlogPost
    {
        return $this->blogRepository->findById($id);
    }

    /**
     * Create new blog post
     *
     * @param array $data
     * @param UploadedFile|null $thumbnail
     * @param int|null $adminId
     * @return BlogPost
     * @throws ValidationException
     */
    public function createPost(array $data, ?UploadedFile $thumbnail = null, ?int $adminId = null): BlogPost
    {
        $this->validatePostData($data);

        // Handle thumbnail upload if provided
        if ($thumbnail) {
            $this->validateThumbnail($thumbnail);
            $data['thumbnail'] = $this->fileUploadService->uploadImage($thumbnail, 'blog');
        }

        // Set default status to draft if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        $post = $this->blogRepository->create($data);

        $this->logAction('blog_post_created', [
            'post_id' => $post->id,
            'status' => $post->status,
            'data' => $data,
            'has_thumbnail' => isset($data['thumbnail'])
        ], $adminId);

        return $post;
    }

    /**
     * Update blog post
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $thumbnail
     * @param int|null $adminId
     * @return BlogPost
     * @throws ValidationException
     */
    public function updatePost(int $id, array $data, ?UploadedFile $thumbnail = null, ?int $adminId = null): BlogPost
    {
        $this->validatePostData($data, $id);

        // Handle thumbnail upload if provided
        if ($thumbnail) {
            $this->validateThumbnail($thumbnail);
            $data['thumbnail'] = $this->fileUploadService->uploadImage($thumbnail, 'blog');
        }

        $post = $this->blogRepository->update($id, $data);

        $this->logAction('blog_post_updated', [
            'post_id' => $id,
            'status' => $post->status,
            'data' => $data,
            'has_new_thumbnail' => isset($data['thumbnail'])
        ], $adminId);

        return $post;
    }

    /**
     * Delete blog post
     *
     * @param int $id
     * @param int|null $adminId
     * @return bool
     */
    public function deletePost(int $id, ?int $adminId = null): bool
    {
        $post = $this->blogRepository->findById($id);

        if (!$post) {
            return false;
        }

        $result = $this->blogRepository->delete($id);

        if ($result) {
            // TODO: Delete associated thumbnail file from storage
            $this->logAction('blog_post_deleted', [
                'post_id' => $id,
                'post_title' => $post->title_en
            ], $adminId);
        }

        return $result;
    }

    /**
     * Publish blog post
     *
     * @param int $id
     * @param int|null $adminId
     * @return BlogPost
     * @throws ValidationException
     */
    public function publishPost(int $id, ?int $adminId = null): BlogPost
    {
        $post = $this->blogRepository->findById($id);

        if (!$post) {
            throw ValidationException::withMessages([
                'post' => ['Blog post not found']
            ]);
        }

        // Validate that post has required fields for publishing
        $this->validatePostForPublishing($post);

        $post = $this->blogRepository->publish($id);

        $this->logAction('blog_post_published', [
            'post_id' => $id,
            'published_at' => $post->published_at
        ], $adminId);

        return $post;
    }

    /**
     * Unpublish blog post (set to draft)
     *
     * @param int $id
     * @param int|null $adminId
     * @return BlogPost
     */
    public function unpublishPost(int $id, ?int $adminId = null): BlogPost
    {
        $post = $this->blogRepository->unpublish($id);

        $this->logAction('blog_post_unpublished', [
            'post_id' => $id
        ], $adminId);

        return $post;
    }

    /**
     * Validate blog post data
     *
     * @param array $data
     * @param int|null $postId For update validation
     * @throws ValidationException
     */
    public function validatePostData(array $data, ?int $postId = null): void
    {
        $validator = Validator::make($data, [
            'title_vi' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_vi' => 'required|string',
            'content_en' => 'required|string',
            'excerpt_vi' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:draft,published',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'published_at' => 'nullable|date'
        ], [
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'content_vi.required' => 'Vietnamese content is required',
            'content_en.required' => 'English content is required',
            'excerpt_vi.max' => 'Vietnamese excerpt must not exceed 500 characters',
            'excerpt_en.max' => 'English excerpt must not exceed 500 characters',
            'status.in' => 'Status must be either draft or published',
            'tags.array' => 'Tags must be an array',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
            'published_at.date' => 'Published date must be a valid date'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate blog post thumbnail
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    public function validateThumbnail(UploadedFile $file): void
    {
        $validator = Validator::make(['thumbnail' => $file], [
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120' // 5MB max
        ], [
            'thumbnail.required' => 'Blog thumbnail is required',
            'thumbnail.image' => 'File must be an image',
            'thumbnail.mimes' => 'Thumbnail must be jpeg, png, jpg, gif, or webp format',
            'thumbnail.max' => 'Thumbnail size must not exceed 5MB'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate post for publishing
     *
     * @param BlogPost $post
     * @throws ValidationException
     */
    public function validatePostForPublishing(BlogPost $post): void
    {
        $errors = [];

        if (empty($post->title_vi) || empty($post->title_en)) {
            $errors['title'] = 'Both Vietnamese and English titles are required for publishing';
        }

        if (empty($post->content_vi) || empty($post->content_en)) {
            $errors['content'] = 'Both Vietnamese and English content are required for publishing';
        }

        if (empty($post->excerpt_vi) || empty($post->excerpt_en)) {
            $errors['excerpt'] = 'Both Vietnamese and English excerpts are required for publishing';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Log admin action
     *
     * @param string $action
     * @param array $data
     * @param int|null $adminId
     */
    private function logAction(string $action, array $data, ?int $adminId = null): void
    {
        Log::info('Blog service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
