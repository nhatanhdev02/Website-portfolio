<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => ContactResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'unread_count' => $this->collection->whereNull('read_at')->count(),
                'read_count' => $this->collection->whereNotNull('read_at')->count(),
                'recent_count' => $this->collection->where('created_at', '>=', now()->subDays(7))->count(),
                'has_items' => $this->collection->isNotEmpty(),
                'oldest_message' => $this->collection->min('created_at'),
                'newest_message' => $this->collection->max('created_at'),
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
            'message' => 'Contact messages retrieved successfully',
        ];
    }
}
