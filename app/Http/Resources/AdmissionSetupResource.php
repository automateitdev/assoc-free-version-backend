<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionSetupResource extends JsonResource
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
            'institute_id' => $this->institute->institute_id ?? null,
            'institute_name' => $this->institute->institute_name ?? null,
            'enabled' => $this->enabled,
            'heading' => $this->heading,
            'form' => $this->form,
        ];
    }
}
