<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstituteDetailsResource extends JsonResource
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
            'institute_id' => $this->institute_id,
            'institute_name' => $this->institute_name,
            'institute_ein' => $this->institute_ein,
            'institute_category' => $this->institute_category,
            'institute_type' => $this->institute_type,
            'institute_board' => $this->institute_board,
            'institute_address' => $this->institute_address,
            'institute_district' => $this->institute_district,
            'institute_sub_distric' => $this->institute_sub_distric,
            'institute_division' => $this->institute_division
        ];
    }
}
