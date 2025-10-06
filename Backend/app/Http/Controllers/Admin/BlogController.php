<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogRequest;
use App\Services\Admin\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(
        private BlogService $blogService
    ) {}

    /**
     * Display a listing of blog posts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'tag' => $request->get('tag')
            ];

            $perPage = $request->get('per_page', 15);
            $posts = $this->blogService->getAllPosts($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created blog post
     */
    public function store(BlogRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $post = $this->blogService->createPost($data);

            return response()->json([
                'success' => true,
                'message' => 'Blog post created successfully',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified blog post
     */
    public function show(int $id): JsonResponse
    {
        try {
            $post = $this->blogService->getPostById($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog post not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified blog post
     */
    public function update(BlogRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $post = $this->blogService->updatePost($id, $data);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog post not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blog post updated successfully',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified blog post
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->blogService->deletePost($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog post not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blog post deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a blog post
     */
    public function publish(int $id): JsonResponse
    {
        try {
            $post = $this->blogService->publishPost($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog post not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blog post published successfully',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpublish a blog post (set to draft)
     */
    public function unpublish(int $id): JsonResponse
    {
        try {
            $post = $this->blogService->unpublishPost($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog post not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blog post set to draft successfully',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get published blog posts
     */
    public function published(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $posts = $this->blogService->getPublishedPosts($perPage);

            return response()->json([
                'success' => true,
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get draft blog posts
     */
    public function drafts(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $posts = $this->blogService->getDraftPosts($perPage);

            return response()->json([
                'success' => true,
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
