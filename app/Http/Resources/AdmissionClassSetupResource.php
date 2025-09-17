<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionClassSetupResource extends JsonResource
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
            'institute_id' => $this->institute->institute_id ?? null,
            'institute_name' => $this->institute->institute_name ?? null,
            'class_id' => $this->class_id,
            'class_name' => $this->class_name,
            'group_id' => $this->group_id,
            'group_name' => $this->group_name,
        ];
    }
}
