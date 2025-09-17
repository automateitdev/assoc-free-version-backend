<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailsResource extends JsonResource
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
            'student_name' => $this->student_name,
            'student_gender' => $this->student_gender,
            'student_religion' => $this->student_religion,
            'student_nationality' => $this->student_nationality,
            'student_dob' => $this->student_dob,
            'student_birth_certificate' => $this->student_birth_certificate,
            'student_nid' => $this->student_nid,
            'student_mobile' => $this->student_mobile,
            'student_email' => $this->student_email,
            'student_height' => $this->student_height,
            'student_weight' => $this->student_weight,
            'student_special_disease' => $this->student_special_disease,
            'blood_group' => $this->blood_group,
            'photo' => $this->photo
        ];

        // 'student_name',
        // 'student_gender',
        // 'student_religion',
        // 'student_dob',
        // 'blood_group',
    }
}
