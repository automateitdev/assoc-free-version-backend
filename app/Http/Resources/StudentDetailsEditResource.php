<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailsEditResource extends JsonResource
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
            'student_dob' => $this->student_dob,
            'blood_group' => $this->blood_group,
        ];
    }
}
