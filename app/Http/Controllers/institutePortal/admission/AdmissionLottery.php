<?php

namespace App\Http\Controllers\institutePortal\admission;

use App\Rules\NonEmptyArray;
use Illuminate\Http\Request;
use App\Models\LotteryStudent;
use App\Models\AdmissionApplied;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Rules\EndDateAfterStartDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdmissionLottery extends Controller
{
    public function lotteryGenerate(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
            'shift' => 'required',
            'group' => 'required',
            'seat_quantity' => 'required',
            'waiting_list_quantity' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload ' => null,
            ], 422);
        }
        $admission_applieds = AdmissionApplied::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', $request->class)
            ->where('shift', $request->shift)
            ->where('group', $request->group)
            ->where('approval_status', 'Success')->get();

        $total_students = $admission_applieds->count();
        $seat_quantity = $request->seat_quantity;
        $waiting_list_quantity = $request->waiting_list_quantity;

        // Check conditions
        if ($total_students < $seat_quantity) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Insufficient students for the number of seats requested.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
        if ($total_students <= $seat_quantity && $waiting_list_quantity > 0) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Waiting list cannot be generated as the number of students is less than or equal to seat quantity.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        $check = LotteryStudent::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', $request->class)
            ->where('shift', $request->shift)
            ->where('group', $request->group)
            ->exists();

        if ($check) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Lottery already generated!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        // Shuffle the student list for random selection
        $shuffled_students = $admission_applieds->shuffle();

        // Assign seats
        $selected_students = $shuffled_students->take($seat_quantity);
        $remaining_students = $shuffled_students->slice($seat_quantity);

        foreach ($selected_students as $index => $student) {
            $lottery_student = new LotteryStudent();
            $lottery_student->institute_details_id = Auth::user()->institute_details_id;
            $lottery_student->unique_number = $student->unique_number;
            $lottery_student->academic_year = $request->academic_year;
            $lottery_student->class = $request->class;
            $lottery_student->shift = $request->shift;
            $lottery_student->group = $request->group;
            $lottery_student->lottery_number = $index + 1;
            $lottery_student->lottery_status = 'Merit';
            $lottery_student->save();
        }

        // Assign waiting list numbers if applicable
        if ($waiting_list_quantity > 0) {
            if ($remaining_students->count() < $waiting_list_quantity) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Not enough students left to generate a waiting list.']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            $waiting_list_students = $remaining_students->take($waiting_list_quantity);
            $remaining_students = $remaining_students->slice($waiting_list_quantity);

            foreach ($waiting_list_students as $index => $student) {
                $lottery_student = new LotteryStudent();
                $lottery_student->institute_details_id = Auth::user()->institute_details_id;
                $lottery_student->unique_number = $student->unique_number;
                $lottery_student->academic_year = $request->academic_year;
                $lottery_student->class = $request->class;
                $lottery_student->shift = $request->shift;
                $lottery_student->group = $request->group;
                $lottery_student->lottery_number = $index + 1;
                $lottery_student->lottery_status = 'Waiting';
                $lottery_student->save();
            }
        }

        // Mark the rest as rejected
        foreach ($remaining_students as $student) {
            $lottery_student = new LotteryStudent();
            $lottery_student->institute_details_id = Auth::user()->institute_details_id;
            $lottery_student->unique_number = $student->unique_number;
            $lottery_student->academic_year = $request->academic_year;
            $lottery_student->class = $request->class;
            $lottery_student->shift = $request->shift;
            $lottery_student->group = $request->group;
            $lottery_student->lottery_status = 'Rejected';
            $lottery_student->save();
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Lottery generation completed successfully.',
        ]);
    }

    public function lotteryList(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
            'shift' => 'required',
            'group' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload ' => null,
            ], 422);
        }

        $lottery_students = LotteryStudent::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', $request->class)
            ->where('shift', $request->shift)
            ->where('group', $request->group)
            ->with('admissionApplied')
            ->get();
        return response()->json([
            'status' => 'success',
            'payload' => $lottery_students,
        ]);
    }
}
