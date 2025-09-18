<?php

namespace App\Http\Controllers\institutePortal\admission;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AdmissionConfig;
use App\Models\AdmissionPayment;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AdmissionSubjectSetup;
use App\Models\InstituteDetail;
use App\Rules\EndDateAfterStartDate;
use App\Rules\NonEmptyArray;
use App\Rules\RequiredIfEnabled;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Savannabits\PrimevueDatatables\PrimevueDatatables;

class AdmissionConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $configs = AdmissionPayment::where('institute_details_id', Auth::user()->institute_details_id)->get();
            return response()->json($configs, 200);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['An unexpected error occurred! Try later.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
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
    // public function store(Request $request)
    // {
    //     $rules = [
    //         'academic_year' => 'required',
    //         'class' => 'required',
    //         // 'shift' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         // 'group' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         // 'file' => ['nullable', 'array', 'min:1', new NonEmptyArray],
    //         'institute' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         'amount' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         'start_date_time' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         'end_date_time' => ['required', 'array', 'min:1', new NonEmptyArray, new EndDateAfterStartDate],
    //         'roll_start' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         'exam_enabled' => ['required', 'array', 'min:1', new NonEmptyArray],
    //         'exam_date_time' => ['nullable', 'array', 'min:1'],
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload ' => null,
    //         ], 422);
    //     }

    //     foreach ($request->exam_enabled as $key => $requiredExam) {
    //         if ($requiredExam == 'YES' && empty($request->exam_date_time[$key])) {
    //             $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['exam_date_time' => 'The exam date time is required at index ' . $key + 1]);
    //             return response()->json([
    //                 'errors' => $formattedErrors,
    //                 'payload' => null,
    //             ], 422);
    //         }
    //     }

    //     DB::beginTransaction();

    //     try {

    //         foreach ($request->shift as $eachShift) {
    //             foreach ($request->group as $group_key => $group) {

    //                 $admissionPay = AdmissionPayment::where('institute_details_id', Auth::user()->institute_details_id)
    //                     ->where('academic_year', $request->academic_year)
    //                     ->where('class', $request->class)
    //                     ->where('shift', $eachShift)
    //                     ->where('group', $group)
    //                     ->first();

    //                 if (empty($admissionPay)) {
    //                     $admissionPay = new AdmissionPayment();
    //                     $admissionPay->institute_details_id = Auth::user()->institute_details_id;
    //                     $admissionPay->academic_year = $request->academic_year;
    //                     $admissionPay->class = $request->class;
    //                     $admissionPay->shift = $eachShift;
    //                     $admissionPay->group = $group;
    //                     $admissionPay->amount = $request->amount[$group_key];
    //                     $admissionPay->start_date_time = $request->start_date_time[$group_key];
    //                     $admissionPay->end_date_time = $request->end_date_time[$group_key];
    //                     $admissionPay->roll_start = $request->roll_start[$group_key];
    //                     $admissionPay->exam_enabled = $request->exam_enabled[$group_key];
    //                     $admissionPay->exam_date_time =  $request->exam_date_time[$group_key] ?? NULL;
    //                     $admissionPay->save();
    //                 }

    //                 if (!empty($request->file('file')[$group_key])) {

    //                     $file = $request->file('file')[$group_key];
    //                     $data = Excel::toArray([], $file);

    //                     foreach ($data[0] as $key => $row) {
    //                         // Skip header row
    //                         if ($key == 0) {
    //                             continue;
    //                         }


    //                         $roll = $row[1];
    //                         $studentFound = AdmissionConfig::where('institute_details_id', Auth::user()->institute_details_id)->where('roll', $roll)
    //                         ->where('admission_payment_id', $admissionPay->id)->exists();

    //                         if ($studentFound) {
    //                             continue;
    //                         }

    //                         $name = $row[2];
    //                         $board = $row[3];
    //                         $passing_year = $row[4];

    //                         $input = new AdmissionConfig();
    //                         $input->institute_details_id = Auth::user()->institute_details_id;
    //                         $input->admission_payment_id = $admissionPay->id;
    //                         $input->roll = $roll;
    //                         $input->name = $name;
    //                         $input->board = $board;
    //                         $input->passing_year = $passing_year;
    //                         $input->save();
    //                     }
    //                 } 
    //                 // else {
    //                 //     $input = new AdmissionConfig();
    //                 //     $input->institute_details_id = Auth::user()->institute_details_id;
    //                 //     $input->academic_year = $request->academic_year;
    //                 //     $input->class = $request->class;
    //                 //     $input->shift = $eachShift;
    //                 //     $input->group = $group;
    //                 //     $input->roll_start = $request->roll_start[$group_key];
    //                 //     $input->amount = $request->amount[$group_key];
    //                 //     $input->start_date_time = $request->start_date_time[$group_key];
    //                 //     $input->end_date_time = $request->end_date_time[$group_key];
    //                 //     $input->exam_enabled = $request->exam_enabled[$group_key];
    //                 //     $input->exam_date_time =  $input->exam_date_time[$group_key] ?? null;
    //                 //     $input->save();
    //                 // }
    //             }
    //         }

    //         DB::commit();

    //         return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    //     } catch (QueryException $e) {
    //         DB::rollBack();
    //         $errorCode = $e->errorInfo[1];
    //         if ($errorCode == 1062) {
    //             return response()->json(['status' => 'error', 'message' => 'Duplicate entry'], Response::HTTP_CONFLICT);
    //         }

    //         Log::error("Failed to store admission config: $e");
    //         return response()->json(['status' => 'error', 'message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Failed to store admission config: $e");
    //         return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    public function store(Request $request)
    {
        $rules = [
            'academic_year'    => 'required|string',
            'class_id'            => 'required|string',
            'class_name'            => 'required|string',

            'center_id'        => 'required',
            'center_name'      => 'required|string',

            'institute_id'     => ['required', 'array', 'min:1'],
            'institute_id.*'   => ['required', 'integer'],

            'institute_name'   => ['required', 'array', 'min:1'],
            'institute_name.*' => ['required', 'string', 'min:1'],

            'amount'           => ['required', 'array', 'min:1'],
            'amount.*'         => ['required', 'numeric', 'min:0'],

            'start_date_time'  => ['required', 'array', 'min:1'],
            'start_date_time.*' => ['required', 'date'],

            'end_date_time'    => ['required', 'array', 'min:1', new EndDateAfterStartDate],
            'end_date_time.*'  => ['required', 'date'],

            'roll_start'       => ['required', 'array', 'min:1'],
            'roll_start.*'     => ['required', 'integer', 'min:1'],

            'exam_enabled'     => ['required', 'array', 'min:1'],
            'exam_enabled.*'   => ['required', 'in:YES,NO'],

            'exam_date_time'   => ['nullable', 'array'],
            'exam_date_time.*' => ['nullable', 'date'],
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload ' => null,
            ], 422);
        }

        foreach ($request->exam_enabled as $key => $requiredExam) {
            if ($requiredExam == 'YES' && empty($request->exam_date_time[$key])) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['exam_date_time' => 'The exam date time is required at index ' . $key + 1]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            // foreach ($request->shift as $eachShift) {
            foreach ($request->institutes as $inst_key => $inst) {

                $admissionPay = AdmissionPayment::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('academic_year', $request->academic_year)
                    ->where('class', $request->class)
                    ->where('center', $request->center)
                    ->where('institute', $inst)
                    ->first();

                if (empty($admissionPay)) {
                    $admissionPay = new AdmissionPayment();
                    $admissionPay->institute_details_id = Auth::user()->institute_details_id;
                    $admissionPay->academic_year = $request->academic_year;
                    $admissionPay->class = $request->class;
                    $admissionPay->center = $request->center;
                    $admissionPay->institute = $inst;
                    $admissionPay->amount = $request->amount[$inst_key];
                    $admissionPay->start_date_time = $request->start_date_time[$inst_key];
                    $admissionPay->end_date_time = $request->end_date_time[$inst_key];
                    $admissionPay->roll_start = $request->roll_start[$inst_key];
                    $admissionPay->exam_enabled = $request->exam_enabled[$inst_key];
                    $admissionPay->exam_date_time =  $request->exam_date_time[$inst_key] ?? NULL;
                    $admissionPay->save();
                }

                // if (!empty($request->file('file')[$group_key])) {

                //     $file = $request->file('file')[$group_key];
                //     $data = Excel::toArray([], $file);

                //     foreach ($data[0] as $key => $row) {
                //         // Skip header row
                //         if ($key == 0) {
                //             continue;
                //         }


                //         $roll = $row[1];
                //         $studentFound = AdmissionConfig::where('institute_details_id', Auth::user()->institute_details_id)->where('roll', $roll)
                //             ->where('admission_payment_id', $admissionPay->id)->exists();

                //         if ($studentFound) {
                //             continue;
                //         }

                //         $name = $row[2];
                //         $board = $row[3];
                //         $passing_year = $row[4];

                //         $input = new AdmissionConfig();
                //         $input->institute_details_id = Auth::user()->institute_details_id;
                //         $input->admission_payment_id = $admissionPay->id;
                //         $input->roll = $roll;
                //         $input->name = $name;
                //         $input->board = $board;
                //         $input->passing_year = $passing_year;
                //         $input->save();
                //     }
                // }
                // else {
                //     $input = new AdmissionConfig();
                //     $input->institute_details_id = Auth::user()->institute_details_id;
                //     $input->academic_year = $request->academic_year;
                //     $input->class = $request->class;
                //     $input->shift = $eachShift;
                //     $input->group = $group;
                //     $input->roll_start = $request->roll_start[$group_key];
                //     $input->amount = $request->amount[$group_key];
                //     $input->start_date_time = $request->start_date_time[$group_key];
                //     $input->end_date_time = $request->end_date_time[$group_key];
                //     $input->exam_enabled = $request->exam_enabled[$group_key];
                //     $input->exam_date_time =  $input->exam_date_time[$group_key] ?? null;
                //     $input->save();
                // }
            }
            // }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['status' => 'error', 'message' => 'Duplicate entry'], Response::HTTP_CONFLICT);
            }

            Log::error("Failed to store admission config: $e");
            return response()->json(['status' => 'error', 'message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store admission config: $e");
            return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validation logic here, if needed
            $validatedData = $request->validate([
                'amount' => 'sometimes|numeric',
                'start_date_time' => 'required|date|before_or_equal:end_date_time',
                'end_date_time' => 'required|date',
                'exam_enabled' => 'required',
                'exam_date_time' => 'nullable|date|after:end_date_time',
            ]);

            // Find the admission configuration by ID
            $admissionConfig = AdmissionPayment::find($id);
            if (!$admissionConfig) {
                return response()->json(['error' => 'Admission configuration not found'], 404);
            }

            // Update the admission configuration with the validated data
            $admissionConfig->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Admission configuration updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Catch validation errors
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Catch all other errors
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function excelDownload(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Define your data here
            $instituteId = Auth::user()->institute_detail->institute_id;
            // Student data (you can replace this with actual data from your database)
            $students = [
                ['Institute ID', 'Roll', 'Name', 'Board', 'Passing Year'],
                [$instituteId, '', '', '', ''],
                // Add more student data as needed
            ];

            // Set the data to the spreadsheet
            foreach ($students as $rowIndex => $row) {
                foreach ($row as $colIndex => $cellValue) {
                    $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $cellValue);
                }
            }

            // Define the file name and path
            $fileName = $instituteId . '_admission.xlsx';
            $directoryPath = 'exports/' . $instituteId;
            $filePath = $directoryPath . '/' . $fileName;

            // Ensure the directory exists
            if (!Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->makeDirectory($directoryPath);
            }

            // Save the Excel file to the public storage disk
            $writer = new Xlsx($spreadsheet);
            $savePath = storage_path('app/public/' . $filePath);
            $writer->save($savePath);

            // Get the download URL
            $url = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'download_url' => $url,
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Excel file creation failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Excel file. Please try again later.',
            ], 500);
        }
    }

    public function enlistmentList(Request $request)
    {
        $rules = [
            'academic_year' => 'required',
            'class' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload ' => null,
            ], 422);
        }
        $admissionPayment = AdmissionPayment::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year', $request->academic_year)
            ->where('class', $request->class)->get();

        $admissionPaymentIds = $admissionPayment->pluck('id')->toArray();

        $list = PrimevueDatatables::of(AdmissionConfig::with('admissionPayment')->whereIn('admission_payment_id', $admissionPaymentIds))->make();

        return response()->json([
            'status' => 'success',
            'message' => 'Enlistment list fetched successfully',
            'enlistment_list' => $list,
        ]);
    }
}
