<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterClassShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coreSubcategoryName = ucwords(str_replace('-', ' ', $this->coresubcategories->core_subcategory_name));
        $coreCategoryName = ucwords($this->coresubcategories->corecategory->core_category_name);

        return [
            'id' => $this->coresubcategories->id,
            'core_subcategory_name' => $coreSubcategoryName,
            'core_category_name' => $coreCategoryName,
        ];
    }
}
