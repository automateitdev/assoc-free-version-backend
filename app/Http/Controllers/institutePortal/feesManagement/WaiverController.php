<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Models\Waiver;
use App\Models\FeeHead;
use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\FeeSubhead;
use Illuminate\Http\Request;
use App\Models\WaiverHistory;
use Illuminate\Http\Response;
use App\Models\AcademicDetail;
use App\Models\InstituteClassMap;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\WaiverResource;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\FeeAmountResource;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\FeeSubheadResource;
use App\Http\Resources\CoreInstituteResource;
use App\Http\Resources\WaiverHistoryResource;
use App\Http\Resources\AcademicDetailsResource;
use App\Models\ClassDetails;

class WaiverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeSubhead = FeeSubhead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $waivers = Waiver::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
            })
            ->with('coresubcategories.corecategory')
            ->get();

        $category = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 7);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $instituteClassMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)->with('classDetails.shifts', 'classDetails.sections', 'classDetails.groups')->get();

        return response()->json([
            'status' => 'success',
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'feeHead' => FeeHeadResource::collection($feeHead),
            'feeSubhead' => FeeSubheadResource::collection($feeSubhead),
            'category' => CoreInstituteResource::collection($category),
            'waivers' => WaiverResource::collection($waivers),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'student_id' => 'nullable',
            'fee_head_id' => 'required',
            'waiver_id' => 'required',
            'waiver_amount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'academic_year_id' => 'required',
            'excel_file' => 'nullable',
        ];

        // $dynamic_column = count($request->fee_head_id);

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($request->excel_file)) {

            $data = Excel::toArray([], $request->excel_file);
            $waiverHistory = [];

            foreach ($data[0] as $key => $row) {
                if ($row[0] != "Institute ID") {
                    $custom_student_id = $row[1];

                    for ($i = 7; $i < count($row); $i++) {
                        $feeheadName = $data[0][0][$i];
                        $weaverAmount = $row[$i];
                        if ($weaverAmount === null || $feeheadName === null) {
                            continue;
                        }
                        $details = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
                            ->where('custom_student_id', $custom_student_id)
                            ->where('academic_year', $request->academic_year_id)
                            ->first();
                        if (!$details) {
                            continue;
                        }
                        foreach ($request->fee_head_id as $feeKey => $feeheads) {
                            $fee_head_name = FeeHead::find($feeheads);
                            if ($fee_head_name->name == $feeheadName) {
                                $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
                                    ->where('combinations_pivot_id', $details->combinations_pivot_id)
                                    ->where('academic_year_id', $request->academic_year_id)
                                    ->where('student_id', $details->student_id)
                                    ->where('fee_head_id', $feeheads)
                                    ->where('payment_state', 'UNPAID')
                                    ->where('waiver_applied', 'FALSE')
                                    ->get();

                                foreach ($payapplies as $pay) {
                                    $check = PayApply::where('student_id', $details->student_id)
                                        ->where('fee_head_id', $feeheads)->first();
                                    if ($check->total_amount >= $weaverAmount && $weaverAmount > 0) {
                                        $payUpdate = PayApply::where('student_id', $details->student_id)
                                            ->where('fee_head_id', $feeheads)
                                            ->update([
                                                'waiver_id' => $request->waiver_id,
                                                'waiver_amount' => $weaverAmount,
                                                'total_amount' => $pay->payable - $weaverAmount,
                                                'waiver_applied' => 'TRUE',
                                            ]);
                                    } else {
                                        $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Amount Error!']);
                                        return response()->json([
                                            'errors' => $formattedErrors,
                                            'payload' => null,
                                        ], 500);
                                    }
                                }

                                if ($payapplies->isNotEmpty()) {
                                    $waiverHistory[] = WaiverHistory::create([
                                        'institute_details_id' => Auth::user()->institute_details_id,
                                        'combinations_pivot_id' => $details->combinations_pivot_id,
                                        'academic_year_id' => $request->academic_year_id,
                                        'student_id' => $details->student_id,
                                        'fee_head_id' => $feeheads,
                                        'waiver_id' => $request->waiver_id,
                                        'waiver_amount' => $weaverAmount,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => "Waiver applied to $details->custom_student_id successfully",
                'waiverHistory' => $waiverHistory,
            ]);
        } else {

            if ($request->waiver_amount > $request->total_amount) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['waiver_amount' => 'Invalid waiver amount!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }
            $waiverHistory = [];

            // foreach ($request->student_id as $key => $studentId) {
            $details = AcademicDetail::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('student_id', $request->student_id)
            ->where('academic_year', $request->academic_year_id)
            ->first();

            if (!$details) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['student' => 'Student details not found!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            // foreach ($request->fee_head_id as $feeKey => $feeheads) {
            $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('combinations_pivot_id', $details->combinations_pivot_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_id', $details->student_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->where('payment_state', 'UNPAID')
            ->where('waiver_applied', 'FALSE')
            ->get();

            foreach ($payapplies as $pay) {

                $payUpdate = PayApply::where('student_id', $details->student_id)
                    ->where('fee_head_id', $request->fee_head_id)
                    ->update([
                        'waiver_id' => $request->waiver_id,
                        'waiver_amount' => $request->waiver_amount,
                        'total_amount' => $pay->payable - $request->waiver_amount,
                        'waiver_applied' => 'TRUE',
                    ]);
            }

            if ($payapplies->isNotEmpty()) {
                $waiverHistory[] = WaiverHistory::create([
                    'institute_details_id' => Auth::user()->institute_details_id,
                    'combinations_pivot_id' => $details->combinations_pivot_id,
                    'academic_year_id' => $request->academic_year_id,
                    'student_id' => $details->student_id,
                    'fee_head_id' => $request->fee_head_id,
                    'waiver_id' => $request->waiver_id,
                    'waiver_amount' => $request->waiver_amount,
                ]);
            }
            // }
            // }

            return response()->json([
                'status' => 'success',
                'message' => "Waiver applied to $details->custom_student_id successfully",
                'waiverHistory' => $waiverHistory,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $details = AcademicDetail::find($id);
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $waivers = Waiver::where('institute_details_id', Auth::user()->institute_details_id)->get();

        return response()->json([
            'status' => 'success',
            'academicDetails' => new AcademicDetailsResource($details),
            'feeHeads' => FeeHeadResource::collection($feeHead),
            'waivers' => WaiverResource::collection($waivers),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function search(Request $request)
    {
        $rules = [
            'academic_year_id' => 'required',
            'combinations_pivot_id' => 'required',
            'fee_head_id' => 'required|array|min:1',
            'waiver_id' => 'required',
            'class_id' => 'required',
            'group_id' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $collection = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->whereIn('fee_head_id', $request->fee_head_id)->first();

        if (empty($collection)) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['class' => 'Fee amount setup not found!']);
            return response()->json(
                [
                    'errors' => $formattedErrors,
                    'payload' => null,
                ],
                400
            );
        }

        $class_name = ucfirst($collection->class->core_subcategory_name);

        $com = DB::table('class_details_institute_class_map')->where('id', $request->combinations_pivot_id)->first();
        $class_details = ClassDetails::find($com->class_details_id);

        $group_name = ucfirst($class_details->groups->core_subcategory_name);
        $shift_name = ucfirst($class_details->shifts->core_subcategory_name);
        $section_name = ucfirst($class_details->sections->core_subcategory_name);
        $academic_year = $collection->academicYear->coresubcategories->core_subcategory_name;
        $cls = $class_name . '-' . $shift_name . '-' . $section_name;

        $feeamounts = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->whereIn('fee_head_id', $request->fee_head_id)->get();

        $feeHeadIds = $feeamounts->pluck('fee_head_id')->toArray();

        $feeheads = FeeHead::whereIn('id', $feeHeadIds)->get();

        $payapplies = PayApply::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('combinations_pivot_id', $request->combinations_pivot_id)
            ->where('payment_state', 'UNPAID')
            ->where('waiver_applied', 'FALSE')
            ->whereIn('fee_head_id', $request->fee_head_id)
            ->select('student_id', 'fee_head_id', 'total_amount')
            ->distinct()
            ->get();


        $payData = [];
        foreach ($payapplies as $key => $pay) {

            $payData[] = [
                'serial_index' => $key,
                'student_id' => $pay->student_id,
                'custom_student_id' => $pay->academic_details->custom_student_id,
                'roll' => $pay->academic_details->class_roll,
                'name' => $pay->student_details->student_name,
                'fee_head_name' => $pay->feeHead->name,
                'fee_head_id' => $pay->fee_head_id,
                'total_amount' => $pay->total_amount,
                'waiver_amount' => null
            ];
        }


        //excel
        // Get feeheads and student_list from the request
        $instituteId = Auth::user()->institute_detail->institute_id;
        $feeheads = FeeHeadResource::collection($feeheads);
        $studentList = $payData;

        // Initialize PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['Institute ID', 'Class-Shift-Section', 'Group', 'Academic Year', 'Student ID', 'Roll', 'Student Name'];

        // Add fee head names to headers
        foreach ($feeheads as $feehead) {
            $headers[] = $feehead['name'];
        }

        // Write headers to the first row
        $sheet->fromArray([$headers], NULL, 'A1');

        // Iterate through each student and populate the Excel sheet
        $row = 2; // Start from the second row
        $processedStudentIds = [];
        foreach ($studentList as $student) {

            if (in_array($student['custom_student_id'], $processedStudentIds)) {
                continue;
            }
            $processedStudentIds[] = $student['custom_student_id'];
            $rowData = [
                $instituteId,
                $cls,
                $group_name,
                $academic_year,
                $student['custom_student_id'],
                $student['roll'],
                $student['name']
            ];

            $totalAmounts = [];
            foreach ($studentList as $entry) {
                if ($entry['custom_student_id'] === $student['custom_student_id']) {
                    $totalAmounts[$entry['fee_head_name']] = $entry['total_amount'];
                }
            }
            foreach ($feeheads as $feehead) {
                $rowData[] = isset($totalAmounts[$feehead['name']]) ? $totalAmounts[$feehead['name']] : 0;
            }


            // Write student data to the sheet
            $sheet->fromArray([$rowData], NULL, 'A' . $row);

            // Move to the next row
            $row++;
        }

        $fileName = $instituteId . '-' . $cls . '_student_waiver.xlsx';

        $filePath = $instituteId . '/exports/' . $fileName;
        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
        }

        // Create a PHPExcel Writer instance
        $writer = new Xlsx($spreadsheet);

        // Store the Excel file in storage
        $writer->save(storage_path('app/public/' . $filePath));
        $url = Storage::url($filePath);

        return response()->json([
            'status' => 'success',
            'student_list' => $payData,
            'feeheads' => FeeHeadResource::collection($feeheads),
            'url' => $url,
        ]);
    }

    public function getfeeheadWiseAmount(Request $request)
    {
        $rules = [
            'class_id' => 'required',
            'group_id' => 'required',
            'academic_year_id' => 'required',
            'student_category_id' => 'required',
            'fee_head_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $feeamount = FeeAmount::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->class_id)
            ->where('group_id', $request->group_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('student_category_id', $request->student_category_id)
            ->where('fee_head_id', $request->fee_head_id)
            ->first();
        if (!$feeamount) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['No data found!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        return response()->json([
            'status' => 'success',
            'feeamount' => new FeeAmountResource($feeamount),
        ]);
    }

    public function assignList(Request $request)
    {
        $rules = [
            'combinations_pivot_id' => 'required',
            'academic_year_id' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $waiverHistory = WaiverHistory::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('combinations_pivot_id', $request->combinations_pivot_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->get();
        if (!$waiverHistory) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['No data found!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        return response()->json([
            'status' => 'success',
            'waiverHistory' => WaiverHistoryResource::collection($waiverHistory),
        ]);
    }
}
