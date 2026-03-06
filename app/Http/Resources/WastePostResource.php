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
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'location' => $this->location,
            'status' => $this->status,
            'image_path' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'donor' => new UserResource($this->whenLoaded('user')),
            'assigned_collector' => $this->whenLoaded('latestJob', function () {
                return $this->latestJob?->collector ? new UserResource($this->latestJob->collector) : null;
            }),
            'job_count' => $this->whenCounted('jobs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
