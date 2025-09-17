<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEducationalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'level_of_education',
        'exam_degree_title',
        'major_group',
        'institute_name',
        'result',
        'gpa_division',
        'scale',
        'passing_year',
        'board',
        'attachment_name',
        'attachment_file',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
