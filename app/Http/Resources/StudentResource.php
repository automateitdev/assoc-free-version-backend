<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\StudentDetailsResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'student_details_id' => $this->student_details_id,
            'student_other_info' => $this->student_other_info,
            'address_details_id' => $this->address_details_id,
            'guardian_details_id' => $this->guardian_details_id,
            'academic_details_id' => $this->academic_details_id,
            'status' => $this->status,

            'student_details' => new StudentDetailsResource($this->whenLoaded('studentDetails')),
            'academic_details' =>AcademicDetailsResource::collection($this->academicDetails),
            // 'academic_details' => new AcademicDetailsResource($this->whenLoaded('academicDetails')),
            'guardian_details_id' => new GuardianDetailsResource($this->whenLoaded('guardianDetails')),

        ];
    }
}
