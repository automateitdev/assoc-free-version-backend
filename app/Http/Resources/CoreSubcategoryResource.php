<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CorecategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CoreSubcategoryResource extends JsonResource
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
            'core_category_id' => $this->corecategory->core_category_name,
            'core_subcategory_name' => ucwords($this->core_subcategory_name),
            'corecategory' => new CorecategoryResource($this->whenLoaded('corecategory')),
        ];
    }
}
