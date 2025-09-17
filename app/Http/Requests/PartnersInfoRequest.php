<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnersInfoRequest extends FormRequest
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
        // dd($this->partner_info);
        return [
            'partner_name' => ['required','string','max:100'],
            'partner_type' => ['required','string','max:30'],
            'partner_code' => ['required','string','max:100',Rule::unique('partners_infos')->ignore($this->partner_info)],
            'mobile' => ['required','string','max:20'],
            'email' => ['required','string','max:100',Rule::unique('partners_infos')->ignore($this->partner_info)],
            'upazila_thana' => ['required','string','max:100'],
            'district' => ['required','string','max:100'],
            'division' => ['required','string','max:100'],
            'signature' => [request()->isMethod('post') ? 'required': 'nullable','string'],
        ];
    }
}
