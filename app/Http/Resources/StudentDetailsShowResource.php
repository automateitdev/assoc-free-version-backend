<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailsShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'student_name' => $this->student_details->student_name,
            'custom_student_id' => $this->custom_student_id,
            'class_roll' => $this->class_roll,
            'student_id' => $this->student_id,
            'category' => $this->categories->coresubcategories->core_subcategory_name,
            'mobile' => $this->guardianDetails->father_mobile,
            'photo' => $this->student_details->photo,
            'class_name' => $this->class_name, // Add 'class_name'
            'group' => $this->group,
            'shift' => $this->shift,
            'section' => $this->section,
            'academic_year' => $this->academicyear->coresubcategories->core_subcategory_name,
            'academic_session' => $this->academicsession->coresubcategories->core_subcategory_name,
        ];
    }
}
