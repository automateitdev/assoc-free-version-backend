<?php

namespace App\Imports;

use App\Models\AdmissionApplied;
use App\Models\ExamMark;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExamMarkImport implements ToCollection, WithHeadingRow
{
    protected $exam_id;

    public function __construct(int $exam_id)
    {
        $this->exam_id = $exam_id;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $uniqueNumber = $row['applicant_id'] ?? $row['unique_number'] ?? null;
            if (!$uniqueNumber) continue;

            $applicant = AdmissionApplied::where('unique_number', $uniqueNumber)->first();
            if (!$applicant) continue;

            $obtainedMark = isset($row['obtained_mark']) ? floatval($row['obtained_mark']) : null;
            $gradeInfo = $this->getGradeInfo($obtainedMark);

            // Update or create exam mark
            ExamMark::updateOrCreate(
                [
                    'admission_applied_id' => $applicant->id,
                    'exam_id' => $this->exam_id,
                ],
                [
                    'total_mark' => 100, // Or $exam->total_marks if you have it
                    'obtained_mark' => $obtainedMark,
                    'grade' => $gradeInfo['grade'],
                    'grade_point' => $gradeInfo['grade_point'],
                ]
            );
        }
    }

    private function getGradeInfo($mark)
    {
        if ($mark === null || $mark === '') {
            return ['grade' => '', 'grade_point' => ''];
        }

        $ranges = [
            ['from' => 80, 'to' => 100, 'grade' => 'A+', 'grade_point' => 5.00],
            ['from' => 70, 'to' => 79.99, 'grade' => 'A',  'grade_point' => 4.50],
            ['from' => 60, 'to' => 69.99, 'grade' => 'A-', 'grade_point' => 4.00],
            ['from' => 50, 'to' => 59.99, 'grade' => 'B',  'grade_point' => 3.00],
            ['from' => 40, 'to' => 49.99, 'grade' => 'C',  'grade_point' => 2.00],
            ['from' => 30, 'to' => 39.99, 'grade' => 'D',  'grade_point' => 1.00],
            ['from' => 0,  'to' => 32.99, 'grade' => 'F',  'grade_point' => 0.00],
        ];

        foreach ($ranges as $range) {
            if ($mark >= $range['from'] && $mark <= $range['to']) {
                return [
                    'grade' => $range['grade'],
                    'grade_point' => $range['grade_point']
                ];
            }
        }

        return ['grade' => '', 'grade_point' => ''];
    }
}
