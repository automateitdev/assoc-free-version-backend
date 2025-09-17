<?php

namespace App\Jobs;

use App\Models\HrDetail;
use App\Models\InstituteHr;
use Illuminate\Bus\Queueable;
use App\Models\CoreInstituteConfig;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class HrExcelEnrollmentJob implements ShouldQueue
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
        $file = $this->hrData['file'];
        $tempFilePath = storage_path('app/' . $file);

        $data = Excel::toArray([], $tempFilePath);


        $hrData = [];
        foreach ($data as $key => $row) {

            foreach ($row[0] as $rowKey => $value) {
                if ($rowKey == 0) {
                    $hrData[$value] = [];
                }
            }
        }

        Log::info($hrData);

        foreach ($data as $key => $row) {

            foreach ($row as $rKey => $record) {
                if ($rKey > 0) {
                    $hrData["HR ID"][] = $record[0];
                    $hrData["HR Name"][] = $record[1];
                    $hrData["Gender"][] = $record[2];
                    $hrData["Religion"][] = $record[3];
                    $hrData["Category"][] = $record[4];
                    $hrData["Mobile"][] = $record[5];
                }
            }
        }
        // dd($hrData);
        $increment = 0;
        foreach ($hrData as $hrKey => $hrValue) {

            $hrIdCount = count($hrData["HR ID"]);
            while ($increment < $hrIdCount) {

                if ($increment === $hrIdCount) {
                    break; // Stop the loop when $increment reaches $studentIdCount
                }
                $hr_detail = new HrDetail();
                $hr_detail->hr_id = $hrData["HR ID"][$increment];
                $hr_detail->hr_name = $hrData["HR Name"][$increment];
                $hr_detail->designation_id = $this->hrData['designation_id'];
                $hr_detail->designation_name = $designationName->coresubcategories->core_subcategory_name;
                $hr_detail->hr_gender = $hrData["Gender"][$increment];
                $hr_detail->hr_religion = $hrData["Religion"][$increment];
                $hr_detail->hr_mobile = $hrData["Mobile"][$increment];
                $hr_detail->category = $hrData["Category"][$increment];
                $hr_detail->save();

                $hr_detail_id = $hr_detail->id;

                // if($hr_detail->save())
                // {
                    $institute_hr = new InstituteHr();
                    $institute_hr->institute_details_id = $this->hrData['institute_details_id'];
                    $institute_hr->hr_details_id = $hr_detail_id;
                    $institute_hr->save();
                // }
                $increment++; // Increment the counter

            }
        }
    }
}
