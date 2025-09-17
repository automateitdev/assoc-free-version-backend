<?php

namespace App\Http\Requests;

use App\Models\ModuleList;
use App\Rules\UrlOrIp;
use App\Utils\GlobalConstant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class SubscriptionFormRequst extends FormRequest
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
        $module_list_ids = ModuleList::query()->select('id')->get()->pluck('id')->toArray();
        $data = [
            // 'partner_id' => ['required', 'numeric', Rule::unique('subscription_forms')->ignore($this->subscribe),'exists:partners_infos,id'],
            'partner_id' => ['required', 'numeric','exists:partners_infos,id'],
            'agreement_date' => ['required', 'date'],
            'validity_date' => ['required', 'date'],
            'duration' => ['nullable', 'date'],
            
            'institute_name' => ['required', 'string', 'max:100',  Rule::unique('subscription_forms')->ignore($this->subscribe)],
           
            'authority_name' => ['required', 'string', 'max:100'],
            'authority_designation' => ['required', 'string', 'max:400'],
            
           
            'authority_mobile' => ['required', 'string', 'max:20','regex:/^([0-9\s\-\+\(\)]*)$/'],
            'telephone' => ['nullable', 'string', 'max:20','regex:/^([0-9\s\-\+\(\)]*)$/'],
           
            'email' => ['required','string','max:100',Rule::unique('subscription_forms')->ignore($this->subscribe)],
            'chairman_name' => ['nullable', 'string', 'max:100'],
            'chairman_mobile' => ['nullable', 'string', 'max:20','regex:/^([0-9\s\-\+\(\)]*)$/'],
            'ict_in_charge' => ['nullable', 'string', 'max:100'],
            'ict_in_charge_mobile' => ['nullable', 'string', 'max:20','regex:/^([0-9\s\-\+\(\)]*)$/'],
 
            'address' => ['required', 'string', 'max:400'],
 
            'upazila_thana' => ['required', 'string', 'max:50'],
            'district' => ['required', 'string', 'max:50'],
            'division' => ['required', 'string', 'max:50'],
            'institute_type' => ['required', 'string', 'max:20'],
            'education_board' => ['required', 'string', 'max:20'],
            'module_list' => ['required','array', Rule::in($module_list_ids)],
            'student_quantity' => ['required', 'numeric'], // institute config
            'hr_number_quantity' => ['required', 'numeric'],  // institute config
            'payment_type' => ['required', 'string', 'max:20'],
            'service_rate' => ['required', 'numeric'], // institute config
            'institute_domain' => ['nullable', 'string', 'max:200', new UrlOrIp],
            // 'status' => ['required', 'string', Rule::in(GlobalConstant::SUBSCRIPTION_STATUS)],
 
            'data_submission_date' => ['nullable', 'date'],
            'tentative_handover_date' => ['nullable', 'date'],
            'sign_2nd' => [ request()->isMethod('PUT')? 'nullable' : 'required', 'string'],
         ];
       
        return $data;
    }
}
