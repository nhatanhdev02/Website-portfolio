<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroRequest;
use App\Services\Admin\HeroService;
use Illuminate\Http\JsonResponse;

class HeroController extends Controller
{
    public function __construct(
        private HeroService $heroService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/hero",
     *     tags={"Hero Section"},
     *     summary="Get hero section content",
     *     description="Retrieve current hero section content in both Vietnamese and English",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Hero content retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="greeting_vi", type="string", example="Xin chào, tôi là"),
     *                 @OA\Property(property="greeting_en", type="string", example="Hello, I'm"),
     *                 @OA\Property(property="name", type="string", example="Nhật Anh"),
     *                 @OA\Property(property="title_vi", type="string", example="Lập trình viên Fullstack"),
     *                 @OA\Property(property="title_en", type="string", example="Fullstack Developer"),
     *                 @OA\Property(property="subtitle_vi", type="string", example="Chuyên về phát triển web hiện đại"),
     *                 @OA\Property(property="subtitle_en", type="string", example="Specialized in modern web development"),
     *                 @OA\Property(property="cta_text_vi", type="string", example="Xem dự án"),
     *                 @OA\Property(property="cta_text_en", type="string", example="View Projects"),
     *                 @OA\Property(property="cta_link", type="string", example="#projects"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function show(): JsonResponse
    {
        try {
            $hero = $this->heroService->getHeroContent();

            return response()->json([
                'success' => true,
                'data' => $hero
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/hero",
     *     tags={"Hero Section"},
     *     summary="Update hero section content",
     *     description="Update hero section content with bilingual support",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"greeting_vi","greeting_en","name","title_vi","title_en","subtitle_vi","subtitle_en","cta_text_vi","cta_text_en","cta_link"},
     *             @OA\Property(property="greeting_vi", type="string", example="Xin chào, tôi là"),
     *             @OA\Property(property="greeting_en", type="string", example="Hello, I'm"),
     *             @OA\Property(property="name", type="string", example="Nhật Anh"),
     *             @OA\Property(property="title_vi", type="string", example="Lập trình viên Fullstack"),
     *             @OA\Property(property="title_en", type="string", example="Fullstack Developer"),
     *             @OA\Property(property="subtitle_vi", type="string", example="Chuyên về phát triển web hiện đại"),
     *             @OA\Property(property="subtitle_en", type="string", example="Specialized in modern web development"),
     *             @OA\Property(property="cta_text_vi", type="string", example="Xem dự án"),
     *             @OA\Property(property="cta_text_en", type="string", example="View Projects"),
     *             @OA\Property(property="cta_link", type="string", example="#projects")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hero content updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hero content updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="greeting_vi", type="string", example="Xin chào, tôi là"),
     *                 @OA\Property(property="greeting_en", type="string", example="Hello, I'm"),
     *                 @OA\Property(property="name", type="string", example="Nhật Anh"),
     *                 @OA\Property(property="title_vi", type="string", example="Lập trình viên Fullstack"),
     *                 @OA\Property(property="title_en", type="string", example="Fullstack Developer"),
     *                 @OA\Property(property="subtitle_vi", type="string", example="Chuyên về phát triển web hiện đại"),
     *                 @OA\Property(property="subtitle_en", type="string", example="Specialized in modern web development"),
     *                 @OA\Property(property="cta_text_vi", type="string", example="Xem dự án"),
     *                 @OA\Property(property="cta_text_en", type="string", example="View Projects"),
     *                 @OA\Property(property="cta_link", type="string", example="#projects"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(HeroRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $hero = $this->heroService->updateHeroContent($data);

            return response()->json([
                'success' => true,
                'message' => 'Hero content updated successfully',
                'data' => $hero
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
