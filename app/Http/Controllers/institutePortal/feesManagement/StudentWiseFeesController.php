<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\FeeAmount;
use Illuminate\Http\Request;
use App\Models\AcademicDetail;
use App\Traits\AmountSetTraits;
use App\Models\InstituteClassMap;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\StudentWiseSearchOneResource;
use App\Http\Resources\StudentWiseSearchTwoResource;

class StudentWiseFeesController extends Controller
{
    use AmountSetTraits;

    public function search(Request $request)
    {

        $rules = [
            'academic_year_id' => 'required',
            'class_id' => 'required',
            'group_id' => 'required',
            'student_category_id' => 'required',
            'fee_head_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        $group_id = $request->group_id;

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                422
            );
        }

        //get combination pivot id
        $pivotId = [];

        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->with('classDetails', function ($query) use ($group_id) {
                $query->where('group_id', $group_id);
            })
            ->first();
        foreach ($instituteClassMaps->classDetails as $classDetail) {
            $pivotId[] = $classDetail->pivot->id;
        }

        //check feeamount settings done or not
        $checkPresent = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->where('student_category_id', $request->student_category_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->first();
        //if feeamount present then show data from payapplies else show only students and fee head
        if ($checkPresent && !empty($checkPresent)) {
            $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $request->academic_year_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->where('category', $request->student_category_id)
                ->get();
            $studentIds = $academicDetails->pluck('student_id')->toArray();
            $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->whereIn('student_id', $studentIds)
                ->where('fee_head_id', $request->fee_head_id)
                ->get();

            return response()->json([
                'studentaWiseAmounts' => StudentWiseSearchOneResource::collection($payapplies),
            ]);
        } else {
            $academicDetails = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year', $request->academic_year_id)
                ->whereIn('combinations_pivot_id', $pivotId)
                ->where('category', $request->student_category_id)
                ->with('student_details')->get();

            $feehead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('id', $request->fee_head_id)
                ->first();

            return response()->json([
                'studentaWiseAmounts' => StudentWiseSearchTwoResource::collection($academicDetails),
                // 'feehead' => $feehead
            ]);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required',
            'class_id' => 'required',
            'group_id' => 'required',
            'student_category_id' => 'required',
            'fee_head_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        $group_id = $request->group_id;

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                422
            );
        }

        $pivotId = [];

        $instituteClassMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->with('classDetails', function ($query) use ($group_id) {
                $query->where('group_id', $group_id);
            })
            ->first();
        foreach ($instituteClassMaps->classDetails as $classDetail) {
            $pivotId[] = $classDetail->pivot->id;
        }

        if (!empty($request->excel_file)) {

            $checkPresent = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('class_id', $request->class_id)
                ->where('group_id', $request->group_id)
                ->where('student_category_id', $request->student_category_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->first();
            if ($checkPresent) {
                    $this->PayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "nope", 'yes');
                    return response()->json([
                        'status' => 'success',
                    ]);
            }else{

                $this->StudentWisePayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "nope", 'yes');
                return response()->json([
                    'status' => 'success',
                ]);
            }
            
        } else {
            $checkPresent = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('class_id', $request->class_id)
                ->where('group_id', $request->group_id)
                ->where('student_category_id', $request->student_category_id)
                ->where('fee_head_id', $request->fee_head_id)
                ->first();
            if ($checkPresent) {
                $this->PayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "yes", 'no');
                return response()->json([
                    'status' => 'success',
                ]);
            } else {

                $this->StudentWisePayappliesUpdate($request, Auth::user()->institute_details_id, $pivotId, "yes", 'no');
                return response()->json([
                    'status' => 'success',
                ]);
            }
        }
    }

    public function excelGenerate(Request $request)
    {
        $rules = [
            'custom_student_id' => 'required|array|min:1',
            'student_name' => 'required|array|min:1',
            'fee_amount' => 'nullable|array|min:1',
            'fine_amount' => 'nullable|array|min:1',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                422
            );
        }
        $instituteId = Auth::user()->institute_detail->institute_id;


        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set the active worksheet
        $worksheet = $spreadsheet->getActiveSheet();

        // Define the column headers
        $headers = [
            'Institute ID',
            'Student ID',
            'Student Name',
            'Fee Amount',
            'Fine Amount'
        ];

        // Set the column headers in the first row
        $rowIndex = 1;
        $colIndex = 1;
        foreach ($headers as $header) {
            $worksheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $header);
        }

        // Loop through each student data
        $studentCount = count($request->custom_student_id);
        for ($i = 0; $i < $studentCount; $i++) {
            $rowIndex++; // Start from the second row for data

            $worksheet->setCellValueByColumnAndRow(1, $rowIndex, $instituteId);
            $worksheet->setCellValueByColumnAndRow(2, $rowIndex, $request->custom_student_id[$i]);
            $worksheet->setCellValueByColumnAndRow(3, $rowIndex, $request->student_name[$i]);
            $worksheet->setCellValueByColumnAndRow(4, $rowIndex, $request->fee_amount[$i]);
            $worksheet->setCellValueByColumnAndRow(5, $rowIndex, $request->fine_amount[$i]);
        }
        $fileName = $instituteId . '_student_wise_fees.xlsx';

        // Create a directory path based on institute ID (optional)
        // $filePath = storage_path('app/public/' . $instituteId . '/exports/');
        $filePath = $instituteId . '/exports/' . $fileName;
        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
        }

        // Create a PHPExcel Writer instance
        $writer = new Xlsx($spreadsheet);

        // Store the Excel file in storage
        $writer->save(storage_path('app/public/' . $filePath));
        // $writer->save(storage_path('app/public/excel/' . $fileName));
        $url = Storage::url($filePath);
        // Prepare a success response
        return response()->json([
            'status' => 'success','url' => $url, // Include the filename in the response
        ]);
    }
}
