<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstituteInfoShowResource extends JsonResource
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
            'institute_email' => $this->institute_email,
            'institute_contact' => $this->institute_contact,
            'institute_category' => $this->institute_category,
            'institute_type' => $this->institute_type,
            'institute_board' => $this->institute_board,
            'institute_logo' => $this->logo,
            'institute_gateway' => $this->gateway,
        ];
    }
}
