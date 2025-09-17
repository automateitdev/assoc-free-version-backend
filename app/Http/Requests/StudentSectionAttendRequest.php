<?php

namespace App\Http\Requests;

use App\Utils\GlobalConstant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentSectionAttendRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $all_days = GlobalConstant::DAYS;
        $data = [
            'combinations_pivot_id' => 'required|numeric|exists:class_details_institute_class_map,id',
            'academic_year' => 'required|numeric|exists:core_subcategories,id',
            'date' => ['required','date'],
            'students' => ['required','array'],
        ];
        if(isset(request()->period_id)){
        $data['period_id'] = ['required','exists:core_subcategories,id','numeric'];
        }else{
            $data['day'] = ['required', Rule::in($all_days)];
        }
        return $data;
    }
}
