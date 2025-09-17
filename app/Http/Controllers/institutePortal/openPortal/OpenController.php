<?php

namespace App\Http\Controllers\institutePortal\openPortal;

use App\Models\FeeHead;
use App\Models\OpenPortal;
use App\Models\OpenHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\InstituteClassMap;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Resources\CoreInstituteResource;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class OpenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $academicYears = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 1);
            })
            ->with('coresubcategories.corecategory')
            ->get();
        $groups = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories', function ($query) {
                $query->where('core_category_id', 5);
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
            'instituteClassMap' => $instituteClassMap,
            'academicYears' => CoreInstituteResource::collection($academicYears),
            'category' => CoreInstituteResource::collection($category),
            'groups' => CoreInstituteResource::collection($groups),
            'feeHead' => FeeHeadResource::collection($feeHead),
        ]);
    }

    public function store(Request $request)
    {

        $rules = [
            'rule_id' => 'required',
            'file' => 'nullable',
            'amount' => 'nullable',
            'start_date_time' => 'required',
            'end_date_time' => 'required',
        ];
        // 1. Any ID, Any Amount
        // 2. Fixed ID, Fixed Amount
        // 3. Any ID, Fixed Amount
        // 4. Fixed ID, Any Amount

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $fileExists = $request->hasFile('file');
        $fileUploaded = $fileExists ? 'YES' : 'NO';

        if ($request->rule_id == 3 && empty($request->amount)) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['amount' => 'The Amount field is required.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        if (($request->rule_id == 2 || $request->rule_id == 4) && !$fileExists) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['file' => 'The Excel File is required.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        try {

            DB::beginTransaction();
            $existingRecord = OpenHistory::where('institute_details_id', Auth::user()->institute_details_id)
                ->where('status', 'ACTIVE')
                ->first();


            if ($existingRecord) {
                if ($existingRecord->rule_id == $request->rule_id) {
                    $open_history = new OpenHistory();
                    $open_history->institute_details_id = Auth::user()->institute_details_id;
                    $open_history->rule_id = $request->rule_id;
                    $open_history->start_date_time = $request->start_date_time;
                    $open_history->end_date_time = $request->end_date_time;
                    $open_history->amount = !empty($request->amount) ? json_encode($request->amount) : null;;
                    $open_history->file = $fileUploaded;
                    $open_history->status = "ACTIVE";
                } else {
                    $open_history = new OpenHistory();
                    $open_history->institute_details_id = Auth::user()->institute_details_id;
                    $open_history->rule_id = $request->rule_id;
                    $open_history->start_date_time = $request->start_date_time;
                    $open_history->end_date_time = $request->end_date_time;
                    $open_history->amount = !empty($request->amount) ? json_encode($request->amount) : null;;
                    $open_history->file = $fileUploaded;
                    $open_history->status = "ACTIVE";
                }
            } else {
                $open_history = new OpenHistory();
                $open_history->institute_details_id = Auth::user()->institute_details_id;
                $open_history->rule_id = $request->rule_id;
                $open_history->start_date_time = $request->start_date_time;
                $open_history->end_date_time = $request->end_date_time;
                $open_history->amount = !empty($request->amount) ? json_encode($request->amount) : null;;
                $open_history->file = $fileUploaded;
                $open_history->status = "ACTIVE";
            }


            if ($open_history->save()) {
                // For Rules 1
                if ($request->rule_id == 1) {
                    if ($fileExists) {
                        $data = Excel::toArray([], $request->file);
                        foreach ($data[0] as $key => $row) {
                            if ($row[0] != "Institute ID") {

                                $academic_year = $row[1];
                                $class_name = $row[2];
                                $shift = $row[3];
                                $group_name = $row[4];
                                $section = $row[5];
                                $student_id = $row[6];
                                $student_name = $row[7];
                                $fee_head_name = $row[8];
                                $fee_amount = $row[9];

                                $open_portal = new OpenPortal();
                                $open_portal->institute_details_id = Auth::user()->institute_details_id;
                                $open_portal->academic_year = $academic_year;
                                $open_portal->class_name = $class_name;
                                $open_portal->student_id = $student_id;
                                $open_portal->student_name = $student_name;
                                $open_portal->group_name = $group_name;
                                $open_portal->shift = $shift;
                                $open_portal->section = $section;
                                $open_portal->rules = $request->rule_id;
                                $open_portal->fee_head_name = $fee_head_name;
                                $open_portal->amount = json_encode($fee_amount);
                                $open_portal->start_date = $request->start_date_time;
                                $open_portal->end_date = $request->end_date_time;
                                $open_portal->history_id = $open_history->id;
                                $open_portal->save();
                            }
                        }
                    } else {
                        $open_portal = new OpenPortal();
                        $open_portal->institute_details_id = Auth::user()->institute_details_id;
                        $open_portal->rules = $request->rule_id;
                        $open_portal->start_date = $request->start_date_time;
                        $open_portal->end_date = $request->end_date_time;
                        $open_portal->history_id = $open_history->id;
                        $open_portal->save();
                    }
                }
                // For Rules 3
                if ($request->rule_id == 3) {
                    $open_portal = new OpenPortal();
                    $open_portal->institute_details_id = Auth::user()->institute_details_id;
                    $open_portal->rules = $request->rule_id;
                    $open_portal->amount = json_encode($request->amount);
                    $open_portal->start_date = $request->start_date_time;
                    $open_portal->end_date = $request->end_date_time;
                    $open_portal->history_id = $open_history->id;
                    $open_portal->save();
                }
                // For Rules 2
                if ($request->rule_id == 2) {

                    $data = Excel::toArray([], $request->file);
                    foreach ($data[0] as $key => $row) {
                        if ($row[0] != "Institute ID") {

                            $academic_year = $row[1];
                            $class_name = $row[2];
                            $shift = $row[3];
                            $group_name = $row[4];
                            $section = $row[5];
                            $student_id = $row[6];
                            $student_name = $row[7];
                            $fee_head_name = $row[8];
                            $fee_amount = $row[9];

                            $open_portal = new OpenPortal();
                            $open_portal->institute_details_id = Auth::user()->institute_details_id;
                            $open_portal->academic_year = $academic_year;
                            $open_portal->class_name = $class_name;
                            $open_portal->student_id = $student_id;
                            $open_portal->student_name = $student_name;
                            $open_portal->group_name = $group_name;
                            $open_portal->shift = $shift;
                            $open_portal->section = $section;
                            $open_portal->rules = $request->rule_id;
                            $open_portal->fee_head_name = $fee_head_name;
                            $open_portal->amount = $fee_amount;
                            $open_portal->start_date = $request->start_date_time;
                            $open_portal->end_date = $request->end_date_time;
                            $open_portal->history_id = $open_history->id;
                            $open_portal->save();
                        }
                    }
                }
                // For Rules 4
                if ($request->rule_id == 4) {

                    $data = Excel::toArray([], $request->file);
                    foreach ($data[0] as $key => $row) {
                        if ($row[0] != "Institute ID") {

                            $academic_year = $row[1];
                            $class_name = $row[2];
                            $shift = $row[3];
                            $group_name = $row[4];
                            $section = $row[5];
                            $student_id = $row[6];
                            $student_name = $row[7];
                            $fee_head_name = $row[8];

                            $open_portal = new OpenPortal();
                            $open_portal->institute_details_id = Auth::user()->institute_details_id;
                            $open_portal->academic_year = $academic_year;
                            $open_portal->class_name = $class_name;
                            $open_portal->student_id = $student_id;
                            $open_portal->student_name = $student_name;
                            $open_portal->group_name = $group_name;
                            $open_portal->shift = $shift;
                            $open_portal->section = $section;
                            $open_portal->rules = $request->rule_id;
                            $open_portal->fee_head_name = $fee_head_name;
                            $open_portal->amount = $fee_amount;
                            $open_portal->start_date = $request->start_date_time;
                            $open_portal->end_date = $request->end_date_time;
                            $open_portal->history_id = $open_history->id;
                            $open_portal->save();
                        }
                    }
                }
                // For Rules 4
                if ($request->rule_id == 4) {

                    $data = Excel::toArray([], $request->file);
                    foreach ($data[0] as $key => $row) {
                        if ($row[0] != "Institute ID") {

                            $academic_year = $row[1];
                            $class_name = $row[2];
                            $shift = $row[3];
                            $group_name = $row[4];
                            $section = $row[5];
                            $student_id = $row[6];
                            $student_name = $row[7];
                            $fee_head_name = $row[8];

                            $open_portal = new OpenPortal();
                            $open_portal->institute_details_id = Auth::user()->institute_details_id;
                            $open_portal->academic_year = $academic_year;
                            $open_portal->class_name = $class_name;
                            $open_portal->student_id = $student_id;
                            $open_portal->student_name = $student_name;
                            $open_portal->group_name = $group_name;
                            $open_portal->shift = $shift;
                            $open_portal->section = $section;
                            $open_portal->rules = $request->rule_id;
                            $open_portal->fee_head_name = $fee_head_name;
                            $open_portal->start_date = $request->start_date_time;
                            $open_portal->end_date = $request->end_date_time;
                            $open_portal->history_id = $open_history->id;
                            $open_portal->save();
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully saved settings for open portal',
                    'open_portal' => $open_portal,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function excelDwonload(Request $request)
    {
        // $rules = [
        //     'rows' => 'required',
        // ];

        // Validate the request data
        // $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        // if ($validator->fails()) {
        //     $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
        //     return response()->json(
        //         [
        //             'errors' => $formattedErrors,
        //             'payload' => null,
        //         ],
        //         422
        //     );
        // }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define your data here
        $instituteId = Auth::user()->institute_detail->institute_id;

        // Institute ID*, Academic Year*, Class*, Shift, Group, Section, Student ID*, Name, Fee Head, Fee Amount*
        // Add dropdown options for Gender and Religion
        $students = [
            [
                'Institute ID',
                'Academic Year',
                'Class',
                'Shift',
                'Group',
                'Section',
                'Student ID',
                'Student Name',
                'Fee Head',
                'Fee Amount'
            ],
            [
                $instituteId,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            // Add more student data as needed
        ];

        // Set font color for specific column names
        $redColumns = ['Institute ID', 'Academic Year', 'Class', 'Student ID', 'Fee Amount'];
        foreach ($redColumns as $columnName) {
            $columnIndex = array_search($columnName, $students[0]); // Get the index of the column name
            if ($columnIndex !== false) {
                // Set font color for the column
                $sheet->getStyleByColumnAndRow($columnIndex + 1, 1)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            }
        }

        // Add data to the spreadsheet
        foreach ($students as $rowIndex => $rowData) {
            foreach ($rowData as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        // Save the Excel file
        $fileName = $instituteId . '_open_portal_demo.xlsx';
        $filePath = $instituteId . '/exports/' . $fileName;
        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filePath));

        // Get the download URL
        $url = Storage::url($filePath);

        return response()->json([
            'status' => 'success',
            'download_url' => $url,
        ]);
    }

    public function show()
    {
        $ruleNames = [
            1 => 'Any ID, Any Amount',
            2 => 'Fixed ID, Fixed Amount',
            3 => 'Any ID, Fixed Amount',
            4 => 'Fixed ID, Any Amount',
        ];

        $openHistory = OpenHistory::where('institute_details_id', Auth::user()->institute_details_id)
            ->get();

        foreach ($openHistory as $history) {
            $history->rule_name = $ruleNames[$history->rule_id];
        }

        return response()->json([
            'status' => 'success',
            'open_history' => $openHistory,
        ]);
    }

    public function update(Request $request)
    {

        $rules = [
            'open_history_id' => 'required',
            'start_date_time' => 'nullable',
            'end_date_time' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $openHistory = OpenHistory::find($request->open_history_id);

        if (!$openHistory) {
            return response()->json(['error' => 'Open history not found'], Response::HTTP_NOT_FOUND);
        }

        // Start a transaction
        DB::beginTransaction();

        try {
            // Only update start_date if it's present in the request
            if ($request->has('start_date_time')) {
                $openHistory->start_date_time = $request->start_date_time;
            }

            // Only update end_date if it's present in the request
            if ($request->has('end_date_time')) {
                $openHistory->end_date_time = $request->end_date_time;
            }

            $openHistory->save();

            // Update related OpenPortal records
            $openPortals = OpenPortal::where('history_id', $request->open_history_id)->where('payment_state', 'UNPAID')->get();

            foreach ($openPortals as $openPortal) {
                // Update start_date if present in the request
                if ($request->has('start_date_time')) {
                    $openPortal->start_date = $request->start_date_time;
                }

                // Update end_date if present in the request
                if ($request->has('end_date_time')) {
                    $openPortal->end_date = $request->end_date_time;
                }

                $openPortal->save();
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Portal date updated successfully', 'open_history' => $openHistory]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollback();

            // Return error response
            return response()->json(['error' => 'Failed to update records'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleOpenHistory()
    {
    }
}
