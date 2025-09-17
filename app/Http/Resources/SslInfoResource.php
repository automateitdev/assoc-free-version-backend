<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SslInfoResource extends JsonResource
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
            'institute_id' => $this->institute->institute_id ?? null,
            'institute_name' => $this->institute->institute_name ?? null,
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
        ];
    }
}
