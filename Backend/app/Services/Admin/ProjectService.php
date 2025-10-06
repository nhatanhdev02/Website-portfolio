<?php

namespace App\Services\Admin;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Get all projects with optional filters
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllProjects(array $filters = []): Collection
    {
        if (isset($filters['category'])) {
            return $this->projectRepository->findByCategory($filters['category']);
        }

        if (isset($filters['featured']) && $filters['featured']) {
            return $this->projectRepository->findFeatured();
        }

        return $this->projectRepository->findAll();
    }

    /**
     * Get project by ID
     *
     * @param int $id
     * @return Project|null
     */
    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->findById($id);
    }

    /**
     * Create new project
     *
     * @param array $data
     * @param UploadedFile|null $image
     * @param int|null $adminId
     * @return Project
     * @throws ValidationException
     */
    public function createProject(array $data, ?UploadedFile $image = null, ?int $adminId = null): Project
    {
        $this->validateProjectData($data);

        // Handle image upload if provided
        if ($image) {
            $this->validateProjectImage($image);
            $data['image'] = $this->fileUploadService->uploadImage($image, 'projects');
        }

        // Set order to next available position if not provided
        if (!isset($data['order'])) {
            $data['order'] = $this->projectRepository->getNextOrder();
        }

        $project = $this->projectRepository->create($data);

        $this->logAction('project_created', [
            'project_id' => $project->id,
            'data' => $data,
            'has_image' => isset($data['image'])
        ], $adminId);

        return $project;
    }

    /**
     * Update project
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $image
     * @param int|null $adminId
     * @return Project
     * @throws ValidationException
     */
    public function updateProject(int $id, array $data, ?UploadedFile $image = null, ?int $adminId = null): Project
    {
        $this->validateProjectData($data, $id);

        // Handle image upload if provided
        if ($image) {
            $this->validateProjectImage($image);
            $data['image'] = $this->fileUploadService->uploadImage($image, 'projects');
        }

        $project = $this->projectRepository->update($id, $data);

        $this->logAction('project_updated', [
            'project_id' => $id,
            'data' => $data,
            'has_new_image' => isset($data['image'])
        ], $adminId);

        return $project;
    }

    /**
     * Delete project
     *
     * @param int $id
     * @param int|null $adminId
     * @return bool
     */
    public function deleteProject(int $id, ?int $adminId = null): bool
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            return false;
        }

        $result = $this->projectRepository->delete($id);

        if ($result) {
            // TODO: Delete associated image file from storage
            $this->logAction('project_deleted', [
                'project_id' => $id,
                'project_title' => $project->title_en
            ], $adminId);
        }

        return $result;
    }

    /**
     * Toggle featured status
     *
     * @param int $id
     * @param int|null $adminId
     * @return Project
     */
    public function toggleFeatured(int $id, ?int $adminId = null): Project
    {
        $project = $this->projectRepository->toggleFeatured($id);

        $this->logAction('project_featured_toggled', [
            'project_id' => $id,
            'featured' => $project->featured
        ], $adminId);

        return $project;
    }

    /**
     * Reorder projects
     *
     * @param array $order Array of project IDs in desired order
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function reorderProjects(array $order, ?int $adminId = null): bool
    {
        $this->validateReorderData($order);

        $result = $this->projectRepository->updateOrder($order);

        if ($result) {
            $this->logAction('projects_reordered', [
                'new_order' => $order
            ], $adminId);
        }

        return $result;
    }

    /**
     * Get projects by category
     *
     * @param string $category
     * @return Collection
     */
    public function getProjectsByCategory(string $category): Collection
    {
        return $this->projectRepository->findByCategory($category);
    }

    /**
     * Get featured projects
     *
     * @return Collection
     */
    public function getFeaturedProjects(): Collection
    {
        return $this->projectRepository->findFeatured();
    }

    /**
     * Validate project data
     *
     * @param array $data
     * @param int|null $projectId For update validation
     * @throws ValidationException
     */
    public function validateProjectData(array $data, ?int $projectId = null): void
    {
        $validator = Validator::make($data, [
            'title_vi' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_vi' => 'required|string|max:2000',
            'description_en' => 'required|string|max:2000',
            'link' => 'nullable|url|max:500',
            'technologies' => 'required|array|min:1',
            'technologies.*' => 'required|string|max:50',
            'category' => 'required|string|in:web,mobile,desktop,api,other',
            'featured' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0'
        ], [
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'description_vi.required' => 'Vietnamese description is required',
            'description_en.required' => 'English description is required',
            'link.url' => 'Project link must be a valid URL',
            'technologies.required' => 'Technologies are required',
            'technologies.min' => 'At least one technology is required',
            'technologies.*.required' => 'Each technology must have a value',
            'technologies.*.max' => 'Each technology must not exceed 50 characters',
            'category.required' => 'Category is required',
            'category.in' => 'Category must be one of: web, mobile, desktop, api, other',
            'featured.boolean' => 'Featured must be true or false',
            'order.integer' => 'Order must be a number',
            'order.min' => 'Order cannot be negative'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate project image
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    public function validateProjectImage(UploadedFile $file): void
    {
        $validator = Validator::make(['image' => $file], [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240' // 10MB max
        ], [
            'image.required' => 'Project image is required',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format',
            'image.max' => 'Image size must not exceed 10MB'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate reorder data
     *
     * @param array $order
     * @throws ValidationException
     */
    public function validateReorderData(array $order): void
    {
        $validator = Validator::make(['order' => $order], [
            'order' => 'required|array|min:1',
            'order.*' => 'required|integer|exists:projects,id'
        ], [
            'order.required' => 'Order array is required',
            'order.array' => 'Order must be an array',
            'order.min' => 'At least one project ID is required',
            'order.*.required' => 'Each order item must have a value',
            'order.*.integer' => 'Each order item must be a number',
            'order.*.exists' => 'Project ID does not exist'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check for duplicates
        if (count($order) !== count(array_unique($order))) {
            throw ValidationException::withMessages([
                'order' => ['Duplicate project IDs are not allowed']
            ]);
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
        Log::info('Project service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
