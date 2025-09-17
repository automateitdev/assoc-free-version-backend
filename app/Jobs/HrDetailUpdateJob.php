<?php

namespace App\Jobs;

use App\Models\HrDetail;
use App\Models\InstituteHr;
use Illuminate\Bus\Queueable;
use App\Models\CoreInstituteConfig;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class HrDetailUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $hrUpdateData;
    /**
     * Create a new job instance.
     */
    public function __construct($hrUpdateData)
    {
        $this->hrUpdateData = $hrUpdateData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hr_detail = HrDetail::find($this->hrUpdateData['hr_detail_id']);
        if(!empty($this->hrUpdateData['hr_id']))
        {
            $hr_detail->hr_id = $this->hrUpdateData['hr_id'];
        }
        if(!empty($this->hrUpdateData['hr_name']))
        {
            $hr_detail->hr_name = $this->hrUpdateData['hr_name'];
        }
        if(!empty($this->hrUpdateData['designation_id']))
        {
            $designationName = CoreInstituteConfig::find($this->hrUpdateData['designation_id']);
            $hr_detail->designation_id = $this->hrUpdateData['designation_id'];
            $hr_detail->designation_name = $designationName->coresubcategories->core_subcategory_name;
        }
        if(!empty($this->hrUpdateData['hr_gender']))
        {
            $hr_detail->hr_gender = $this->hrUpdateData['hr_gender'];
        }
        if(!empty($this->hrUpdateData['hr_religion']))
        {
            $hr_detail->hr_religion = $this->hrUpdateData['hr_religion'];
        }
        if(!empty($this->hrUpdateData['hr_mobile']))
        {
            $hr_detail->hr_mobile = $this->hrUpdateData['hr_mobile'];
        }
        if(!empty($this->hrUpdateData['category']))
        {
            $hr_detail->category = $this->hrUpdateData['category'];
        }
        if(!empty($this->hrUpdateData['hr_nationality']))
        {
            $hr_detail->hr_nationality = $this->hrUpdateData['hr_nationality'];
        }

        if(!empty($this->hrUpdateData['hr_dob']))
        {
            $hr_detail->hr_dob = $this->hrUpdateData['hr_dob'];
        }
        if(!empty($this->hrUpdateData['hr_birth_certificate']))
        {
            $hr_detail->hr_birth_certificate = $this->hrUpdateData['hr_birth_certificate'];
        }
        if(!empty($this->hrUpdateData['hr_nid']))
        {
            $hr_detail->hr_nid = $this->hrUpdateData['hr_nid'];
        }
        if(!empty($this->hrUpdateData['hr_email']))
        {
            $hr_detail->hr_email = $this->hrUpdateData['hr_email'];
        }
        if(!empty($this->hrUpdateData['hr_height']))
        {
            $hr_detail->hr_height = $this->hrUpdateData['hr_height'];
        }
        if(!empty($this->hrUpdateData['hr_weight']))
        {
            $hr_detail->hr_weight = $this->hrUpdateData['hr_weight'];
        }
        if(!empty($this->hrUpdateData['hr_special_disease']))
        {
            $hr_detail->hr_special_disease = $this->hrUpdateData['hr_special_disease'];
        }

        if(!empty($this->hrUpdateData['hr_photo']))
        { 
            $hr_detail->hr_photo = $this->hrUpdateData['hr_photo'];
        }

        $hr_detail->save();
    }
}
