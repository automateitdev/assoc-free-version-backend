<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstituteClassMapResource extends JsonResource
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
            'institute_details_id' => $this->institute_details_id,
            'class_id' => $this->class_id,
            'class_name' => $this->class_name,

            'class_details' => ClassDetailsResource::collection($this->whenLoaded('classDetails')),
            
        ];
    }
}
