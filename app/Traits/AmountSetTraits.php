<?php

namespace App\Traits;

use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\FeeMapping;
use App\Models\ClassDetails;
use Illuminate\Http\Response;
use App\Models\AcademicDetail;
use App\Jobs\PayapplyUpdateJob;
use App\Models\InstituteClassMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\StudentWiseFeeAmountJob;

trait AmountSetTraits
{
    public function PayappliesUpdate($request, $institute_details_id, $pivotId, $student, $excel)
    {
        if ($student == "yes") {

            $payapplies = PayApply::where('institute_details_id', $institute_details_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->whereIn('student_id', $request->student_id)
                ->where('payment_state', 'UNPAID')
                ->get();
            foreach ($payapplies as $payapplie) {
                $studentIndex = array_search($payapplie->student_id, $request->student_id);

                if ($studentIndex !== false) {
                    $payUpdate = [
                        'id' => $payapplie->id,
                        'payable' => $request->fee_amount[$studentIndex],
                        'total_amount' => $request->fee_amount[$studentIndex],
                        'fine_amount' => isset($request->fine_amount[$studentIndex]) ? $request->fine_amount[$studentIndex] : null
                    ];

                    dispatch(new PayapplyUpdateJob($payUpdate));
                }
            }
        } elseif ($student == "no") {
            $feesubhead = FeeMapping::where('institute_details_id', $institute_details_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->get()
                ->pluck('fee_subhead_id');
            $payapplies = PayApply::where('institute_details_id', $institute_details_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->whereIn('fee_subhead_id', $feesubhead)
                ->where('payment_state', 'UNPAID')
                ->get();
            foreach ($payapplies as $payapplie) {
                $payUpdate = [
                    'id' => $payapplie->id,
                    'payable' => $request->fee_amount,
                    'total_amount' => $request->fee_amount,
                    'fine' => $request->fine_amount ?? null
                ];
                dispatch(new PayapplyUpdateJob($payUpdate));
            }
        } elseif ($excel == "yes") {
            $data = Excel::toArray([], $request->excel_file);
            foreach ($data[0] as $row) {
                if ($row[0] != "Institute ID") {
                    $studentId = $row[1];
                    $payable = $row[3];
                    $fineAmount = $row[4] ?? null;

                    $academic_data = AcademicDetail::where('institute_details_id', $institute_details_id)
                        ->where('custom_student_id', $studentId)
                        ->where('academic_year', $request->academic_year_id)
                        ->first();

                    $payApply = PayApply::where('institute_details_id', $institute_details_id)
                        ->whereIn('combinations_pivot_id', $pivotId) // Assuming pivotIds is an array
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('fee_head_id', $request->fee_head_id)
                        ->where('student_id', $academic_data->student_id)
                        ->where('payment_state', 'UNPAID')
                        ->first();

                    $payApply->update([
                        'payable' => $payable,
                        'total_amount' => $payable,
                        'fine' => $fineAmount,
                    ]);
                }
            }
        }
    }

    public function StudentWisePayappliesUpdate($request, $institute_details_id, $pivotId, $student, $excel)
    {
        try {
            $feesubhead = FeeMapping::where('institute_details_id', $institute_details_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->pluck('fee_subhead_id');
            if ($student == "yes") {
                $academicDetails = AcademicDetail::where('institute_details_id', $institute_details_id)
                    ->where('academic_year', $request->academic_year_id)
                    ->whereIn('combinations_pivot_id', $pivotId)
                    ->where('category', $request->student_category_id)
                    ->whereIn('student_id', $request->student_id)
                    ->get();
                $studentIndex = array_search($student->student_id, $request->student_id);
                if ($studentIndex !== false) {
                    $paymentData = [
                        'institute_details_id' => $institute_details_id,
                        'academic_year_id' => $request->academic_year_id,
                        'combinations_pivot_id' => $student->combinations_pivot_id,
                        'student_id' => $student->student_id,
                        'fee_head_id' => $request->fee_head_id,
                        'fee_subhead_id' => $feesubhead,
                        'payable' => $request->fee_amount[$studentIndex],
                        'total_amount' => $request->fee_amount[$studentIndex],
                        'fine_amount' => isset($request->fine_amount[$studentIndex]) ? $request->fine_amount[$studentIndex] : null
                    ];

                    dispatch(new StudentWiseFeeAmountJob($paymentData));
                }
            } elseif ($student == "no") {
                $academicDetails = AcademicDetail::where('institute_details_id', $institute_details_id)
                    ->where('academic_year', $request->academic_year_id)
                    ->whereIn('combinations_pivot_id', $pivotId)
                    ->where('category', $request->student_category_id)
                    ->get();

                foreach ($academicDetails as $student) {
                    $paymentData = [
                        'institute_details_id' => $institute_details_id,
                        'academic_year_id' => $request->academic_year_id,
                        'combinations_pivot_id' => $student->combinations_pivot_id,
                        'student_id' => $student->student_id,
                        'fee_head_id' => $request->fee_head_id,
                        'fee_subhead_id' => $feesubhead,
                        'payable' => $request->fee_amount,
                        'total_amount' => $request->fee_amount,
                        'fine_amount' => $request->fine_amount ?? null
                    ];

                    dispatch(new StudentWiseFeeAmountJob($paymentData));
                }
            } elseif ($excel == "yes") {

                $feesubhead = FeeMapping::where('institute_details_id', $institute_details_id)
                    ->where('fee_head_id', $request->fee_head_id)
                    ->pluck('fee_subhead_id');

                $data = Excel::toArray([], $request->excel_file);
                foreach ($data[0] as $row) {
                    if ($row[0] != "Institute ID") {
                        $studentId = $row[1];
                        $payable = $row[3];
                        $fineAmount = $row[4] ?? null;

                        $academicDetails = AcademicDetail::where('institute_details_id', $institute_details_id)
                            ->where('academic_year', $request->academic_year_id)
                            ->whereIn('combinations_pivot_id', $pivotId)
                            ->where('category', $request->student_category_id)
                            ->where('custom_student_id', $studentId)
                            ->first();
                        $paymentData = [
                            'institute_details_id' => $institute_details_id,
                            'academic_year_id' => $request->academic_year_id,
                            'combinations_pivot_id' => $academicDetails->combinations_pivot_id,
                            'student_id' => $academicDetails->student_id,
                            'fee_head_id' => $request->fee_head_id,
                            'fee_subhead_id' => $feesubhead,
                            'payable' => $payable,
                            'total_amount' => $payable,
                            'fine_amount' => $fineAmount ?? null
                        ];
                        dispatch(new StudentWiseFeeAmountJob($paymentData));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function updateSingleStudentPayApplies($request, $institute_details_id, $pivotId, $student_id)
    {
        $pivotDetail = DB::table('class_details_institute_class_map')
            ->where('id', $pivotId)
            ->first();
        if (!$pivotDetail) {
            return response()->json(['error' => 'Pivot detail not found'], Response::HTTP_NOT_FOUND);
        }
        $classDetail = ClassDetails::find($pivotDetail->class_details_id);
        if (!$classDetail) {
            return response()->json(['error' => 'Class detail not found'], Response::HTTP_NOT_FOUND);
        }
        $instituteClassMap = InstituteClassMap::find($pivotDetail->institute_class_map_id);
        if (!$instituteClassMap) {
            return response()->json(['error' => 'Institute class map detail not found'], Response::HTTP_NOT_FOUND);
        }
        // category update 
        if ($request->student_category_id) {

            $feeAmounts = FeeAmount::where('institute_details_id', $institute_details_id)
                ->where('class_id', $instituteClassMap->class_id)
                ->where('group_id', $classDetail->group_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('student_category_id', $request->student_category_id)
                ->get();
            if ($feeAmounts->isEmpty()) {
                return response()->json(['error' => 'Fee amounts not found'], Response::HTTP_NOT_FOUND);
            }
            foreach ($feeAmounts as $fee_amount) {
                $feeSubheads = FeeMapping::where('institute_details_id', $institute_details_id)
                    ->where('fee_head_id', $fee_amount->fee_head_id)
                    ->pluck('fee_subhead_id');

                $paymentData = [
                    'institute_details_id' => $institute_details_id,
                    'academic_year_id' => $request->academic_year_id,
                    'combinations_pivot_id' => $pivotId,
                    'student_id' => $student_id,
                    'fee_head_id' => $fee_amount->fee_head_id,
                    'fee_subhead_id' => $feeSubheads,
                    'payable' => $fee_amount->fee_amounts,
                    'total_amount' => $fee_amount->fee_amounts,
                    'fine_amount' => isset($fee_amount->fine_amount) ? $fee_amount->fine_amount : null
                ];

                dispatch(new StudentWiseFeeAmountJob($paymentData));
            }
        }

        //group update
        if ($request->group_id) {
            
            $academic_detail = AcademicDetail::where('institute_details_id', $institute_details_id)
                ->where('combinations_pivot_id', $pivotId)
                ->where('academic_year', $request->academic_year_id)
                ->where('student_id', $student_id)
                ->first();

            $feeAmounts = FeeAmount::where('institute_details_id', $institute_details_id)
                ->where('class_id', $instituteClassMap->class_id)
                ->where('group_id', $classDetail->group_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('student_category_id', $academic_detail->category)
                ->get();
            if ($feeAmounts->isEmpty()) {
                return response()->json(['error' => 'Fee amounts not found'], Response::HTTP_NOT_FOUND);
            }
            foreach ($feeAmounts as $fee_amount) {
                $feeSubheads = FeeMapping::where('institute_details_id', $institute_details_id)
                    ->where('fee_head_id', $fee_amount->fee_head_id)
                    ->pluck('fee_subhead_id');

                $paymentData = [
                    'institute_details_id' => $institute_details_id,
                    'academic_year_id' => $request->academic_year_id,
                    'combinations_pivot_id' => $pivotId,
                    'student_id' => $student_id,
                    'fee_head_id' => $fee_amount->fee_head_id,
                    'fee_subhead_id' => $feeSubheads,
                    'payable' => $fee_amount->fee_amounts,
                    'total_amount' => $fee_amount->fee_amounts,
                    'fine_amount' => isset($fee_amount->fine_amount) ? $fee_amount->fine_amount : null
                ];

                dispatch(new StudentWiseFeeAmountJob($paymentData));
            }
        }

        //section update
        if ($request->combination_id) {
            $academic_detail = AcademicDetail::where('institute_details_id', $institute_details_id)
                ->where('combinations_pivot_id', $pivotId)
                ->where('academic_year', $request->academic_year_id)
                ->where('student_id', $student_id)
                ->first();

            $feeAmounts = FeeAmount::where('institute_details_id', $institute_details_id)
                ->where('class_id', $instituteClassMap->class_id)
                ->where('group_id', $classDetail->group_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('student_category_id', $academic_detail->category)
                ->get();
            if ($feeAmounts->isEmpty()) {
                return response()->json(['error' => 'Fee amounts not found'], Response::HTTP_NOT_FOUND);
            }
            foreach ($feeAmounts as $fee_amount) {
                $feeSubheads = FeeMapping::where('institute_details_id', $institute_details_id)
                    ->where('fee_head_id', $fee_amount->fee_head_id)
                    ->pluck('fee_subhead_id');

                $paymentData = [
                    'institute_details_id' => $institute_details_id,
                    'academic_year_id' => $request->academic_year_id,
                    'combinations_pivot_id' => $pivotId,
                    'student_id' => $student_id,
                    'fee_head_id' => $fee_amount->fee_head_id,
                    'fee_subhead_id' => $feeSubheads,
                    'payable' => $fee_amount->fee_amounts,
                    'total_amount' => $fee_amount->fee_amounts,
                    'fine_amount' => isset($fee_amount->fine_amount) ? $fee_amount->fine_amount : null
                ];

                dispatch(new StudentWiseFeeAmountJob($paymentData));
            }
        }
    }
}
