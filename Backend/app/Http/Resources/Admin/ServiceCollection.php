<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ServiceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => ServiceResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'has_items' => $this->collection->isNotEmpty(),
                'max_order' => $this->collection->max('order') ?? 0,
                'min_order' => $this->collection->min('order') ?? 0,
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Services retrieved successfully',
        ];
    }
}
