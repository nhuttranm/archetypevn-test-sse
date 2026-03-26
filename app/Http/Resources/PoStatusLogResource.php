<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoStatusLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'from_status_label' => $this->getStatusLabel($this->from_status),
            'to_status_label' => $this->getStatusLabel($this->to_status),
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    protected function getStatusLabel(?string $status): ?string
    {
        if (!$status) return null;

        return match ($status) {
            'draft' => 'Draft',
            'pending_manager' => 'Pending Manager',
            'pending_director' => 'Pending Director',
            'pending_finance' => 'Pending Finance',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst($status),
        };
    }
}
