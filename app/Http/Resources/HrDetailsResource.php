<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrDetailsResource extends JsonResource
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
            'hr_id' => $this->hr_id,
            'hr_name' => $this->hr_name,
            'hr_gender' => $this->hr_gender,
            'hr_religion' => $this->hr_religion,
            'designation_id' => $this->designation_id,
            'designation_name' => $this->designation_name,
            'category' => $this->category,
            'hr_nationality' => $this->hr_nationality,
            'hr_dob' => $this->hr_dob,
            'hr_birth_certificate' => $this->hr_birth_certificate,
            'hr_nid' => $this->hr_nid,
            'hr_mobile' => $this->hr_mobile,
            'hr_email' => $this->hr_email,
            'hr_height' => $this->hr_height,
            'hr_weight' => $this->hr_weight,
            'hr_special_disease' => $this->hr_special_disease,
            'hr_photo' => $this->hr_photo,
        ];
    }
}
