<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicDetailsResource extends JsonResource
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
            'custom_student_id' => $this->custom_student_id,
            'class_roll' => $this->class_roll,
            'student_name' => $this->student_details->student_name,
            'academic_year' => $this->academic_year,
            'academic_session' => $this->academic_session,
            'category' => $this->category,

            'academic_year' => CoreSubcategoryResource::collection($this->whenLoaded('academicyear')),
            'academic_session' => CoreSubcategoryResource::collection($this->whenLoaded('academicsession')),
            'category' => CoreSubcategoryResource::collection($this->whenLoaded('categories')),
            
        ];
    }
}
