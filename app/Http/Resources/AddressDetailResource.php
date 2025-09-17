<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pres_address' => $this->present_address,
            'pres_village_town' => $this->present_village_town,
            'pres_ward_union' => $this->present_ward_union,
            'pres_po' => $this->present_po,
            'pres_ps_upazilla' => $this->present_ps_upazilla,
            'pres_district' => $this->present_district,
            'pres_division' => $this->present_division,
            'pres_country' => $this->present_country,
            'pres_landmark' => $this->present_landmark,
            'perm_address' => $this->permanent_address,
            'perm_village_town' => $this->permanent_village_town,
            'perm_ward_union' => $this->permanent_ward_union,
            'perm_po' => $this->permanent_po,
            'perm_ps_upazilla' => $this->permanent_ps_upazilla,
            'perm_district' => $this->permanent_district,
            'perm_division' => $this->permanent_division,
            'perm_country' => $this->permanent_country,
            'perm_landmark' => $this->permanent_landmark
         
        ];
    }
}
