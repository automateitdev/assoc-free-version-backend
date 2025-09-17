<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentAttendSmsConfigRequest extends FormRequest
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
        $data =  [
            'sms_status' => ['required'],
            'sms_body' => ['required'],
        ];
        if(!isset(request()->day_or_period)){
            $data['period_id'] = ['required']; 
            $data['day'] = ['required']; 
         }else{
             if(request()->day_or_period == 'period'){
                 $data['period_id'] = ['required']; 
             }
             else if(request()->day_or_period == 'day'){
                 $data['day'] = ['required','array']; 
             }
         }
         return $data;
    }
}
