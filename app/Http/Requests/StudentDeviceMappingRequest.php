<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentDeviceMappingRequest extends FormRequest
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
        return [
            'combinations_pivot_id' => ['required','exists:class_details_institute_class_map,id'],
            'academic_year' => ['required','exists:core_subcategories,id'],
            'mashine_id' => ['required','array'],
            'mashine_id.*' => ['required'],
            'custom_student_id' => ['required','array'],
            'custom_student_id.*' => ['required'],
        ];
    }
}
