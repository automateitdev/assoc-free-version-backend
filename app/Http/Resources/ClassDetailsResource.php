<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CoreSubcategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassDetailsResource extends JsonResource
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
            'group_id' => $this->group_id,
            'shift_id' => $this->shift_id,
            'section_id' => $this->section_id,
            'pivot' =>$this->pivot,
            'shifts' => new CoreSubcategoryResource($this->whenLoaded('shifts')),
            'groups' => new CoreSubcategoryResource($this->whenLoaded('groups')),
            'sections' => new CoreSubcategoryResource($this->whenLoaded('sections')),
            
            
        ];
    }
}
