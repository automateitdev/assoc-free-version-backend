<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiverHistoryResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->studentDetails->student_name,
            'feehead_name' => $this->feehead->name,
            'waiver_name' => $this->waiver->name,
            'waiver_amount' => $this->waiver_amount,
        ];
    }
}
