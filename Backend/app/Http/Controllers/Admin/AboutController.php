<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AboutRequest;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Services\Admin\AboutService;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    public function __construct(
        private AboutService $aboutService
    ) {}

    /**
     * Display the about content
     */
    public function show(): JsonResponse
    {
        try {
            $about = $this->aboutService->getAboutContent();

            return response()->json([
                'success' => true,
                'data' => $about
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the about content
     */
    public function update(AboutRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $about = $this->aboutService->updateAboutContent($data);

            return response()->json([
                'success' => true,
                'message' => 'About content updated successfully',
                'data' => $about
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload profile image
     */
    public function uploadImage(ImageUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');
            $imageUrl = $this->aboutService->uploadProfileImage($file);

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'data' => [
                    'image_url' => $imageUrl
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
