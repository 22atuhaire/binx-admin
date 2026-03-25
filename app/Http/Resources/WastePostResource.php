<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WastePostResource extends JsonResource
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
            'waste_types' => $this->waste_types,
            'quantity' => $this->quantity,
            'address' => $this->address,
            'instructions' => $this->instructions,
            'photos' => $this->photos,
            'created_at' => $this->created_at,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location,
            'status' => $this->status,
            'image_path' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'donor' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'rating' => $this->user?->rating,
                ];
            }),
            'assigned_collector' => $this->whenLoaded('latestJob', function () {
                return $this->latestJob?->collector ? new UserResource($this->latestJob->collector) : null;
            }),
            'job_count' => $this->whenCounted('jobs'),
            'updated_at' => $this->updated_at,
        ];
    }
}
