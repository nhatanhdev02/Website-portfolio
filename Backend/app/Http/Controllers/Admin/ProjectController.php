<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Services\Admin\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/projects",
     *     tags={"Projects"},
     *     summary="Get all projects",
     *     description="Retrieve paginated list of projects with filtering options",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by project category",
     *         required=false,
     *         @OA\Schema(type="string", example="web")
     *     ),
     *     @OA\Parameter(
     *         name="featured",
     *         in="query",
     *         description="Filter by featured status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in project titles and descriptions",
     *         required=false,
     *         @OA\Schema(type="string", example="Laravel")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Projects retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title_vi", type="string", example="Ứng dụng E-commerce"),
     *                         @OA\Property(property="title_en", type="string", example="E-commerce Application"),
     *                         @OA\Property(property="description_vi", type="string", example="Ứng dụng thương mại điện tử hiện đại"),
     *                         @OA\Property(property="description_en", type="string", example="Modern e-commerce application"),
     *                         @OA\Property(property="image", type="string", example="https://example.com/project1.jpg"),
     *                         @OA\Property(property="link", type="string", example="https://project1.com"),
     *                         @OA\Property(property="technologies", type="array", @OA\Items(type="string", example="Laravel")),
     *                         @OA\Property(property="category", type="string", example="web"),
     *                         @OA\Property(property="featured", type="boolean", example=true),
     *                         @OA\Property(property="order", type="integer", example=1)
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'category' => $request->get('category'),
                'featured' => $request->get('featured'),
                'search' => $request->get('search')
            ];

            $perPage = $request->get('per_page', 15);
            $projects = $this->projectService->getAllProjects($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $projects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created project
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $project = $this->projectService->createProject($data);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified project
     */
    public function show(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->getProjectById($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $project
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified project
     */
    public function update(ProjectRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $project = $this->projectService->updateProject($id, $data);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified project
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->projectService->deleteProject($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle featured status of a project
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->toggleFeatured($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project featured status updated successfully',
                'data' => $project
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured projects
     */
    public function featured(): JsonResponse
    {
        try {
            $projects = $this->projectService->getFeaturedProjects();

            return response()->json([
                'success' => true,
                'data' => $projects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
