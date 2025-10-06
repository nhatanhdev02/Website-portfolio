<?php

namespace App\Services\Admin;

use App\Models\About;
use App\Repositories\Contracts\AboutRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class AboutService
{
    public function __construct(
        private AboutRepositoryInterface $aboutRepository,
        private FileUploadService $fileUploadService,
        private CacheService $cacheService
    ) {}

    /**
     * Get about content with caching
     *
     * @return array
     */
    public function getAboutContent(): array
    {
        return $this->cacheService->remember(
            'about_content',
            'about',
            function () {
                $about = $this->aboutRepository->getContent();

                if (!$about) {
                    // Return default structure if no about content exists
                    return [
                        'content_vi' => '',
                        'content_en' => '',
                        'profile_image' => null,
                        'skills' => [],
                        'experience' => [],
                        'resume_url' => null
                    ];
                }

                return [
                    'id' => $about->id,
                    'content_vi' => $about->content_vi,
                    'content_en' => $about->content_en,
                    'profile_image' => $about->profile_image,
                    'skills' => $about->skills ?? [],
                    'experience' => $about->experience ?? [],
                    'resume_url' => $about->resume_url,
                    'updated_at' => $about->updated_at
                ];
            }
        );
    }

    /**
     * Update about content with cache invalidation
     *
     * @param array $data
     * @param int|null $adminId
     * @return About
     * @throws ValidationException
     */
    public function updateAboutContent(array $data, ?int $adminId = null): About
    {
        $this->validateAboutData($data);

        $about = $this->aboutRepository->updateContent($data);

        // Invalidate about cache
        $this->cacheService->forget('about_content', 'about');

        $this->logAction('about_updated', $data, $adminId);

        return $about;
    }

    /**
     * Upload profile image
     *
     * @param UploadedFile $file
     * @param int|null $adminId
     * @return string
     * @throws ValidationException
     */
    public function uploadProfileImage(UploadedFile $file, ?int $adminId = null): string
    {
        $this->validateProfileImage($file);

        // Upload image using FileUploadService
        $imagePath = $this->fileUploadService->uploadImage($file, 'about');

        // Update about record with new image path
        $this->aboutRepository->updateImage($imagePath);

        $this->logAction('profile_image_updated', [
            'image_path' => $imagePath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ], $adminId);

        return $imagePath;
    }

    /**
     * Upload and update profile image with cache invalidation
     *
     * @param UploadedFile $file
     * @param int|null $adminId
     * @return About
     * @throws ValidationException
     */
    public function updateProfileImage(UploadedFile $file, ?int $adminId = null): About
    {
        $this->validateProfileImage($file);

        // Upload image using FileUploadService
        $imagePath = $this->fileUploadService->uploadImage($file, 'about');

        // Update about record with new image path
        $about = $this->aboutRepository->updateImage($imagePath);

        // Invalidate about cache
        $this->cacheService->forget('about_content', 'about');

        $this->logAction('profile_image_updated', [
            'image_path' => $imagePath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ], $adminId);

        return $about;
    }

    /**
     * Validate about data
     *
     * @param array $data
     * @throws ValidationException
     */
    public function validateAboutData(array $data): void
    {
        $validator = Validator::make($data, [
            'content_vi' => 'required|string|max:5000',
            'content_en' => 'required|string|max:5000',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'experience' => 'nullable|array',
            'resume_url' => 'nullable|url|max:500'
        ], [
            'content_vi.required' => 'Vietnamese content is required',
            'content_en.required' => 'English content is required',
            'content_vi.max' => 'Vietnamese content must not exceed 5000 characters',
            'content_en.max' => 'English content must not exceed 5000 characters',
            'skills.array' => 'Skills must be an array',
            'skills.*.string' => 'Each skill must be a string',
            'skills.*.max' => 'Each skill must not exceed 100 characters',
            'experience.array' => 'Experience must be an array',
            'resume_url.url' => 'Resume URL must be a valid URL'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate profile image
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    public function validateProfileImage(UploadedFile $file): void
    {
        $validator = Validator::make(['image' => $file], [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120' // 5MB max
        ], [
            'image.required' => 'Profile image is required',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format',
            'image.max' => 'Image size must not exceed 5MB'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
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
        Log::info('About service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
