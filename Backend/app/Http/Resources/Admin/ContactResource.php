<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => [
                'is_read' => !is_null($this->read_at),
                'read_at' => $this->read_at?->toISOString(),
                'read_date' => $this->read_at?->format('Y-m-d'),
                'read_time' => $this->read_at?->format('H:i:s'),
            ],
            'message_stats' => [
                'message_length' => strlen($this->message ?? ''),
                'word_count' => str_word_count($this->message ?? ''),
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toISOString(),
                'created_date' => $this->created_at?->format('Y-m-d'),
                'created_time' => $this->created_at?->format('H:i:s'),
                'updated_at' => $this->updated_at?->toISOString(),
                'days_ago' => $this->created_at?->diffInDays(now()),
            ],
        ];
    }
}
