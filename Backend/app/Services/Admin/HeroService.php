<?php

namespace App\Services\Admin;

use App\Models\Hero;
use App\Repositories\Contracts\HeroRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HeroService
{
    public function __construct(
        private HeroRepositoryInterface $heroRepository,
        private CacheService $cacheService
    ) {}

    /**
     * Get hero content with caching
     *
     * @return array
     */
    public function getHeroContent(): array
    {
        return $this->cacheService->remember(
            'hero_content',
            'hero',
            function () {
                $hero = $this->heroRepository->getContent();

                if (!$hero) {
                    // Return default structure if no hero content exists
                    return [
                        'greeting_vi' => '',
                        'greeting_en' => '',
                        'name' => '',
                        'title_vi' => '',
                        'title_en' => '',
                        'subtitle_vi' => '',
                        'subtitle_en' => '',
                        'cta_text_vi' => '',
                        'cta_text_en' => '',
                        'cta_link' => ''
                    ];
                }

                return [
                    'id' => $hero->id,
                    'greeting_vi' => $hero->greeting_vi,
                    'greeting_en' => $hero->greeting_en,
                    'name' => $hero->name,
                    'title_vi' => $hero->title_vi,
                    'title_en' => $hero->title_en,
                    'subtitle_vi' => $hero->subtitle_vi,
                    'subtitle_en' => $hero->subtitle_en,
                    'cta_text_vi' => $hero->cta_text_vi,
                    'cta_text_en' => $hero->cta_text_en,
                    'cta_link' => $hero->cta_link,
                    'updated_at' => $hero->updated_at
                ];
            }
        );
    }

    /**
     * Update hero content with cache invalidation
     *
     * @param array $data
     * @param int|null $adminId
     * @return Hero
     * @throws ValidationException
     */
    public function updateHeroContent(array $data, ?int $adminId = null): Hero
    {
        $this->validateHeroData($data);

        $hero = $this->heroRepository->updateContent($data);

        // Invalidate hero cache
        $this->cacheService->forget('hero_content', 'hero');

        $this->logAction('hero_updated', $data, $adminId);

        return $hero;
    }

    /**
     * Validate hero data
     *
     * @param array $data
     * @throws ValidationException
     */
    public function validateHeroData(array $data): void
    {
        $validator = Validator::make($data, [
            'greeting_vi' => 'required|string|max:500',
            'greeting_en' => 'required|string|max:500',
            'name' => 'required|string|max:255',
            'title_vi' => 'required|string|max:500',
            'title_en' => 'required|string|max:500',
            'subtitle_vi' => 'required|string|max:1000',
            'subtitle_en' => 'required|string|max:1000',
            'cta_text_vi' => 'required|string|max:100',
            'cta_text_en' => 'required|string|max:100',
            'cta_link' => 'required|url|max:500'
        ], [
            'greeting_vi.required' => 'Vietnamese greeting is required',
            'greeting_en.required' => 'English greeting is required',
            'name.required' => 'Name is required',
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'subtitle_vi.required' => 'Vietnamese subtitle is required',
            'subtitle_en.required' => 'English subtitle is required',
            'cta_text_vi.required' => 'Vietnamese CTA text is required',
            'cta_text_en.required' => 'English CTA text is required',
            'cta_link.required' => 'CTA link is required',
            'cta_link.url' => 'CTA link must be a valid URL'
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
        Log::info('Hero service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
