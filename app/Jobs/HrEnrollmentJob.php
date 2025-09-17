<?php

namespace App\Jobs;

use App\Models\CoreInstituteConfig;
use App\Models\HrDetail;
use App\Models\InstituteHr;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class HrEnrollmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $hrData;
    /**
     * Create a new job instance.
     */
    public function __construct($hrData)
    {
        $this->hrData = $hrData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $designationName = CoreInstituteConfig::find($this->hrData['designation_id']);
        $hr_detail = new HrDetail();
        $hr_detail->hr_id = $this->hrData['hr_id'];
        $hr_detail->hr_name = $this->hrData['hr_name'];
        $hr_detail->designation_id = $this->hrData['designation_id'];
        $hr_detail->designation_name = $designationName->coresubcategories->core_subcategory_name;
        $hr_detail->hr_gender = $this->hrData['hr_gender'];
        $hr_detail->hr_religion = $this->hrData['hr_religion'];
        $hr_detail->hr_mobile = $this->hrData['mobile'];
        $hr_detail->category = $this->hrData['category'];

        if($hr_detail->save())
        {
            $institute_hr = new InstituteHr();
            $institute_hr->institute_details_id = $this->hrData['institute_details_id'];
            $institute_hr->hr_details_id = $hr_detail->id;
            $institute_hr->save();
        }
    }
}
