<?php

namespace App\Services\Admin;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceManagementService
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {}

    /**
     * Get all services ordered by position
     *
     * @return Collection
     */
    public function getAllServices(): Collection
    {
        return $this->serviceRepository->getOrdered();
    }

    /**
     * Get service by ID
     *
     * @param int $id
     * @return Service|null
     */
    public function getServiceById(int $id): ?Service
    {
        return $this->serviceRepository->findById($id);
    }

    /**
     * Create new service
     *
     * @param array $data
     * @param int|null $adminId
     * @return Service
     * @throws ValidationException
     */
    public function createService(array $data, ?int $adminId = null): Service
    {
        $this->validateServiceData($data);

        // Set order to next available position if not provided
        if (!isset($data['order'])) {
            $data['order'] = $this->serviceRepository->getNextOrder();
        }

        $service = $this->serviceRepository->create($data);

        $this->logAction('service_created', [
            'service_id' => $service->id,
            'data' => $data
        ], $adminId);

        return $service;
    }

    /**
     * Update service
     *
     * @param int $id
     * @param array $data
     * @param int|null $adminId
     * @return Service
     * @throws ValidationException
     */
    public function updateService(int $id, array $data, ?int $adminId = null): Service
    {
        $this->validateServiceData($data, $id);

        $service = $this->serviceRepository->update($id, $data);

        $this->logAction('service_updated', [
            'service_id' => $id,
            'data' => $data
        ], $adminId);

        return $service;
    }

    /**
     * Delete service
     *
     * @param int $id
     * @param int|null $adminId
     * @return bool
     */
    public function deleteService(int $id, ?int $adminId = null): bool
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            return false;
        }

        $result = $this->serviceRepository->delete($id);

        if ($result) {
            $this->logAction('service_deleted', [
                'service_id' => $id,
                'service_title' => $service->title_en
            ], $adminId);
        }

        return $result;
    }

    /**
     * Reorder services
     *
     * @param array $order Array of service IDs in desired order
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function reorderServices(array $order, ?int $adminId = null): bool
    {
        $this->validateReorderData($order);

        $result = $this->serviceRepository->updateOrder($order);

        if ($result) {
            $this->logAction('services_reordered', [
                'new_order' => $order
            ], $adminId);
        }

        return $result;
    }

    /**
     * Validate service data
     *
     * @param array $data
     * @param int|null $serviceId For update validation
     * @throws ValidationException
     */
    public function validateServiceData(array $data, ?int $serviceId = null): void
    {
        $validator = Validator::make($data, [
            'title_vi' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_vi' => 'required|string|max:1000',
            'description_en' => 'required|string|max:1000',
            'icon' => 'required|string|max:100',
            'color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'order' => 'nullable|integer|min:0'
        ], [
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'description_vi.required' => 'Vietnamese description is required',
            'description_en.required' => 'English description is required',
            'icon.required' => 'Icon is required',
            'color.required' => 'Color is required',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF6B6B)',
            'bg_color.required' => 'Background color is required',
            'bg_color.regex' => 'Background color must be a valid hex color code (e.g., #FFE5E5)',
            'order.integer' => 'Order must be a number',
            'order.min' => 'Order cannot be negative'
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
            'order.*' => 'required|integer|exists:services,id'
        ], [
            'order.required' => 'Order array is required',
            'order.array' => 'Order must be an array',
            'order.min' => 'At least one service ID is required',
            'order.*.required' => 'Each order item must have a value',
            'order.*.integer' => 'Each order item must be a number',
            'order.*.exists' => 'Service ID does not exist'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check for duplicates
        if (count($order) !== count(array_unique($order))) {
            throw ValidationException::withMessages([
                'order' => ['Duplicate service IDs are not allowed']
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
        Log::info('Service management action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
