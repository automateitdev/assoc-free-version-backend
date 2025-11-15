<?php

namespace App\Imports;

use App\Models\AdmissionApplied;
use Maatwebsite\Excel\Concerns\ToModel;

class ExamMarkImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new AdmissionApplied([
            //
        ]);
    }
}
