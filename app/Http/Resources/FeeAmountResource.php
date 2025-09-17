<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeAmountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'class_name' => $this->class->core_subcategory_name,
            'group_name' => $this->group->groups->core_subcategory_name,
            'student_category' => $this->categories->coresubcategories->core_subcategory_name,
            'academic_year' => $this->academicYear->coresubcategories->core_subcategory_name,
            'fee_head' => $this->feehead->name,
            'fee_amount' => $this->fee_amount,
            'fine_amount' => $this->fine_amount,
        ];
        // $class = new CoreSubcategoryResource($this->class);
        // $group = new CoreSubcategoryResource($this->group);
        // $category = new CoreInstituteResource($this->categories);
        // $year = new CoreInstituteResource($this->academicYear);
        // $fee_head = new CoreInstituteResource($this->feehead);

        // $groupKey = [
        //     'institute_details_id' => $this->institute_details_id,
        //     'class_id' => $class->core_subcategory_name,
        //     'group_id' => $group->core_subcategory_name,
        //     'student_category_id' => $category->coresubcategories->core_subcategory_name,
        //     'academic_year_id' => $year->coresubcategories->core_subcategory_name,
        //     'fee_head_id' => $fee_head->name,
        // ];
        // return [
        //     'institute_details_id' => $groupKey['institute_details_id'],
        //     'class_id' => $groupKey['class_id'],
        //     'group_id' => $groupKey['group_id'],
        //     'student_category_id' => $groupKey['student_category_id'],
        //     'academic_year_id' => $groupKey['academic_year_id'],
        //     'fee_head_id' => $groupKey['fee_head_id'],
        //     'fee_amount' => $this->fee_amount,
        //     'fine_amount' => $this->fine_amount,
        // ];
    }

   
}
