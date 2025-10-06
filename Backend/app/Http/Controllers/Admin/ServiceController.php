<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Services\Admin\ServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(
        private ServiceManagementService $serviceService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/services",
     *     tags={"Services"},
     *     summary="Get all services",
     *     description="Retrieve paginated list of services ordered by position",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Services retrieved successfully",
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
     *                         @OA\Property(property="title_vi", type="string", example="Phát triển Web"),
     *                         @OA\Property(property="title_en", type="string", example="Web Development"),
     *                         @OA\Property(property="description_vi", type="string", example="Phát triển ứng dụng web hiện đại"),
     *                         @OA\Property(property="description_en", type="string", example="Modern web application development"),
     *                         @OA\Property(property="icon", type="string", example="web-icon"),
     *                         @OA\Property(property="color", type="string", example="#FF6B6B"),
     *                         @OA\Property(property="bg_color", type="string", example="#FFE5E5"),
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
            $perPage = $request->get('per_page', 15);
            $services = $this->serviceService->getAllServices($perPage);

            return response()->json([
                'success' => true,
                'data' => $services
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/services",
     *     tags={"Services"},
     *     summary="Create a new service",
     *     description="Create a new service with bilingual content",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title_vi","title_en","description_vi","description_en","icon","color","bg_color"},
     *             @OA\Property(property="title_vi", type="string", example="Phát triển Web"),
     *             @OA\Property(property="title_en", type="string", example="Web Development"),
     *             @OA\Property(property="description_vi", type="string", example="Phát triển ứng dụng web hiện đại"),
     *             @OA\Property(property="description_en", type="string", example="Modern web application development"),
     *             @OA\Property(property="icon", type="string", example="web-icon"),
     *             @OA\Property(property="color", type="string", example="#FF6B6B"),
     *             @OA\Property(property="bg_color", type="string", example="#FFE5E5")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title_vi", type="string", example="Phát triển Web"),
     *                 @OA\Property(property="title_en", type="string", example="Web Development"),
     *                 @OA\Property(property="description_vi", type="string", example="Phát triển ứng dụng web hiện đại"),
     *                 @OA\Property(property="description_en", type="string", example="Modern web application development"),
     *                 @OA\Property(property="icon", type="string", example="web-icon"),
     *                 @OA\Property(property="color", type="string", example="#FF6B6B"),
     *                 @OA\Property(property="bg_color", type="string", example="#FFE5E5"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title_vi",
     *                     type="array",
     *                     @OA\Items(type="string", example="The Vietnamese title field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(ServiceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $service = $this->serviceService->createService($data);

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified service
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = $this->serviceService->getServiceById($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $service
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified service
     */
    public function update(ServiceRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $service = $this->serviceService->updateService($id, $data);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully',
                'data' => $service
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified service
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->serviceService->deleteService($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder services
     */
    public function reorder(ReorderRequest $request): JsonResponse
    {
        try {
            $orderData = $request->validated();
            $result = $this->serviceService->reorderServices($orderData['order']);

            return response()->json([
                'success' => true,
                'message' => 'Services reordered successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
