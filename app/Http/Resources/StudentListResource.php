<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $studentDetails = new StudentDetailsEditResource($this->whenLoaded('studentDetails'));
        $guardianDetails = new GuardianDetailsResource($this->whenLoaded('guardianDetails'));

        return collect([
            'id' => $this->id,
            'student_id' => $this->student_id,
            'custom_student_id' => $this->custom_student_id,
            'class_roll' => $this->class_roll,
            'admission_date' => $this->admission_date,
        ])->merge($studentDetails->resolve())
          ->merge($guardianDetails->resolve())
          ->toArray();

    }
}
