<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentAttendTimeConfigRequest extends FormRequest
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
        // dd(request()->all());
        $data =  [
            'start_time' => ['required'],
            'end_time' => ['required'],
            'delay_time' => ['required'],
            'process_time' => ['required'],
            'sms_status' => ['required'],
            'academic_year' => ['required'],
            'combinations_pivot_ids' => ['required','array'],
            'combinations_pivot_ids.*' => ['required'],
        ];
        if(!isset(request()->day_or_period)){
            $data['period_id'] = ['required']; 
            $data['day'] = ['required']; 
         }else{
             if(request()->day_or_period == 'period'){
                 $data['period_id'] = ['required']; 
             }
             else if(request()->day_or_period == 'day'){
                 $data['day'] = ['required']; 
             }
         }
         return $data;
    }
}
