<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentWiseSearchTwoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'student_id' => $this->student_id,
            'custom_student_id' => $this->custom_student_id,
            'student_name' => $this->student_details->student_name,
            // 'fee_head_id' => $this->fee_head_id,
            // 'fee_head_name' => $this->feeHead->name,
            // 'fee_subhead_id' => $this->fee_subhead_id,
            // 'fee_subhead_name' => $this->feeSubead->name,
        ];
    }
}
