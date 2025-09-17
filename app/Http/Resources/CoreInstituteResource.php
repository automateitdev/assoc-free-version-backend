<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CorecategoryResource;
use App\Http\Resources\CoreSubcategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CoreInstituteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coreSubcategoryName = ucwords($this->formatName($this->coresubcategories->core_subcategory_name));
        
        // $coreSubcategoryName = ucwords(str_replace('-', ' ', $this->coresubcategories->core_subcategory_name));
        $coreCategoryName = ucwords($this->coresubcategories->corecategory->core_category_name);

        return [
            'id' => $this->id,
            'core_category_name' => $coreCategoryName,
            'core_subcategory_name' => $coreSubcategoryName
        ];
        
    }
    private function formatName(string $name): string
    {
        // Use regular expression to match hyphens that are not part of numeric values
        $name = preg_replace_callback('/(\d+)\s*-\(([^)]*)-(.*?)\)/', function ($matches) {
            $prefix = $matches[1];
            $firstPart = ucwords($matches[2]);
            $secondPart = ucwords($matches[3]);
            return "$prefix($firstPart-$secondPart)";
        }, $name);
    
        // Use regular expression to match other hyphens and capitalize words
        $name = preg_replace('/(?<!\d)-/', ' ', $name);
        
        return $name;
    }
  
}
