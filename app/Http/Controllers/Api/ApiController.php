<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\SslInfo;
use App\Models\Upazila;
use App\Models\District;
use App\Models\Division;
use Illuminate\Support\Str;
use App\Models\AdmissionFee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AdmissionSetup;
use App\Utils\ServerErrorMask;
use App\Models\AdmissionConfig;
use App\Models\InstituteDetail;
use App\Models\AdmissionApplied;
use App\Models\AdmissionPayment;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AdmissionInstruction;
use App\Models\AdmissionSubjectSetup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\AdmissionSpgPay;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Js;

class ApiController extends Controller
{
    // public function admission(string $instituteId)
    // {
    //     try {
    //         // Validate and sanitize the institute ID if necessary
    //         if (empty($instituteId)) {
    //             $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request observed!']);
    //             return response()->json([
    //                 'errors' => $formattedErrors,
    //                 'payload' => null,
    //             ], 400);
    //         }

    //         $instituteDetails = InstituteDetail::where('institute_id', $instituteId)->first();

    //         if (empty($instituteDetails)) {
    //             $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Institute not found!']);
    //             return response()->json([
    //                 'errors' => $formattedErrors,
    //                 // 'instiutes'=> $institues,
    //                 'payload' => null,
    //             ], 404);
    //         }
    //         $admissionSetup = AdmissionSetup::where('institute_details_id', $instituteDetails->id)->with('institute')->first();
    //         $detailConfig = AdmissionPayment::where('institute_details_id', $instituteDetails->id)->get();

    //         // Initialize the result array
    //         $result = [];

    //         // Loop through each entry in the data
    //         foreach ($detailConfig as $entry) {
    //             // Check if the academic year already exists in the result array
    //             if (!isset($result[$entry['academic_year']])) {
    //                 $result[$entry['academic_year']] = [
    //                     'academic_year' => $entry['academic_year'],
    //                     'details' => []
    //                 ];
    //             }

    //             // Find if the class already exists in the details array
    //             $classExists = false;
    //             foreach ($result[$entry['academic_year']]['details'] as &$detail) {
    //                 if ($detail['class'] === $entry['class']) {
    //                     $classExists = true;
    //                     // Add the shift if it does not exist
    //                     if (!in_array($entry['shift'], $detail['shifts'])) {
    //                         $detail['shifts'][] = $entry['shift'];
    //                     }
    //                     // Add the group if it does not exist
    //                     if (!in_array($entry['group'], $detail['groups'])) {
    //                         $detail['groups'][] = $entry['group'];
    //                     }
    //                     break;
    //                 }
    //             }

    //             // If the class does not exist, add it to the details array
    //             if (!$classExists) {
    //                 $result[$entry['academic_year']]['details'][] = [
    //                     'class' => $entry['class'],
    //                     'shifts' => [$entry['shift']],
    //                     'groups' => [$entry['group']],
    //                     'amount' => $entry['amount'],
    //                     'start_date_time' => $entry['start_date_time'],
    //                     'end_date_time' => $entry['start_date_time'],
    //                     'exam_enabled' => $entry['exam_enabled'] === 'YES' ?  true : false,
    //                     'exam_date_time' => $entry['exam_date_time']
    //                 ];
    //             }
    //         }

    //         // Convert the result to an array of values
    //         $result = array_values($result);

    //         // Check if admission config was found
    //         if (!$admissionSetup) {
    //             $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Admission configuration not found']);
    //             return response()->json([
    //                 'errors' => $formattedErrors,
    //                 'payload' => null,
    //             ], 400);
    //         }

    //         $data = [
    //             'id' => $admissionSetup->id,
    //             'instiute_details' => $admissionSetup->institute,
    //             'enabled' => $admissionSetup->enabled,
    //             'heading' => $admissionSetup->heading,
    //             'form' => $admissionSetup->form,
    //             'subject_status' => $admissionSetup->subject,
    //             'academic_info_status' => $admissionSetup->academic_info,
    //             'details' => $result
    //         ];

    //         // Add the admission link based on the gateway
    //         if ($instituteDetails->gateway == 'SPG') {
    //             $data['admission_link'] = env('SPG_ADMISSION') . '/' . $admissionSetup->institute->institute_id;
    //         } elseif ($instituteDetails->gateway == 'SSL') {
    //             $data['admission_link'] = env('SSL_ADMISSION') . '/' . $admissionSetup->institute->institute_id;
    //         }

    //         $payment_instruction = AdmissionInstruction::first();

    //         return response()->json([
    //             'status' => 'success',
    //             'messsage' => 'Data Found',
    //             'admissionConfig' => $data,
    //             'payment_instruction' => $payment_instruction,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error("Failed on application form: $e");
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['An unexpected error occurred! Try later.']);
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 400);
    //     } catch (ModelNotFoundException $e) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request observed!']);
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 400);
    //     }
    // }

    public function admission(string $instituteId)
    {
        try {
            // Validate institute ID
            if (empty($instituteId)) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request observed!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            // Get institute details
            $instituteDetails = InstituteDetail::where('institute_id', $instituteId)->first();
            if (empty($instituteDetails)) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Institute not found!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 404);
            }

            // Get admission setup and payment details
            $admissionSetup = AdmissionSetup::where('institute_details_id', $instituteDetails->id)
                ->with('institute')
                ->first();

            $detailConfig = AdmissionPayment::where('institute_details_id', $instituteDetails->id)->get();

            // Prepare result grouped by academic year, class, and center
            $result = [];
            foreach ($detailConfig as $entry) {
                $year = $entry['academic_year'];

                // Initialize academic year if not exists
                if (!isset($result[$year])) {
                    $result[$year] = [
                        'academic_year' => $year,
                        'details' => []
                    ];
                }

                $classExists = false;

                foreach ($result[$year]['details'] as &$detail) {
                    // Match by class_id and center_id
                    if ($detail['class_id'] === $entry['class_id'] && $detail['center_id'] === $entry['center_id']) {
                        $classExists = true;

                        // Add institute if not already present
                        if (!in_array($entry['institute_id'], $detail['institutes'])) {
                            $detail['institutes'][] = [
                                'id' => $entry['institute_id'],
                                'name' => $entry['institute_name'],
                            ];
                        }

                        break;
                    }
                }

                // If class + center combination does not exist, create new detail entry
                if (!$classExists) {
                    $result[$year]['details'][] = [
                        'class_id'        => $entry['class_id'],
                        'class_name'           => $entry['class_name'],
                        'center_id'       => $entry['center_id'],
                        'center_name'          => $entry['center_name'],
                        'institutes'      => [
                            [
                                'id' => $entry['institute_id'],
                                'name' => $entry['institute_name'],
                            ]
                        ],
                        'amount'          => $entry['amount'],
                        'start_date_time' => $entry['start_date_time'],
                        'end_date_time'   => $entry['end_date_time'],
                        'exam_enabled'    => $entry['exam_enabled'] === 'YES',
                        'exam_date_time'  => $entry['exam_date_time']
                    ];
                }
            }

            // Convert result to values array
            $result = array_values($result);

            // Check if admission setup exists
            if (!$admissionSetup) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Admission configuration not found']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            // Prepare response data
            $data = [
                'id'                   => $admissionSetup->id,
                'instiute_details'     => $admissionSetup->institute,
                'enabled'              => $admissionSetup->enabled,
                'heading'              => $admissionSetup->heading,
                'form'                 => $admissionSetup->form,
                'subject_status'       => $admissionSetup->subject,
                'academic_info_status' => $admissionSetup->academic_info,
                'details'              => $result
            ];

            // Add admission link based on gateway
            if ($instituteDetails->gateway == 'SPG') {
                $data['admission_link'] = env('SPG_ADMISSION') . '/' . $admissionSetup->institute->institute_id;
            } elseif ($instituteDetails->gateway == 'SSL') {
                $data['admission_link'] = env('SSL_ADMISSION') . '/' . $admissionSetup->institute->institute_id;
            }

            $payment_instruction = AdmissionInstruction::first();

            return response()->json([
                'status' => 'success',
                'message' => 'Data Found',
                'admissionConfig' => $data,
                'payment_instruction' => $payment_instruction,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed on application form: $e");
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['An unexpected error occurred! Try later.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        } catch (ModelNotFoundException $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request observed!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
    }


    public function yearWiseSearch(Request $request, $year)
    {
        $query = AdmissionPayment::where('institute_details_id', $request->institute_details_id)
            ->where('academic_year', $year);

        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        if ($request->has('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->has('group')) {
            $query->where('group', $request->group);
            $subject = AdmissionSubjectSetup::where('institute_details_id', $request->institute_details_id)
                ->where('class_name', $request->class)
                ->where('group_name', $request->group)
                ->first();
        }

        $instituteConfig = $query->get();

        return response()->json([
            'status' => 'success',
            'configured_data' => $instituteConfig,
            'subject_list' => $subject,
        ]);
    }

    public function rollSearch(Request $request)
    {
        $rules = [
            'institute_id' => 'required',
            'roll' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            $instituteDetails = InstituteDetail::where('institute_id', $request->institute_id)->first();

            // $check = AdmissionConfig::where('institute_details_id', $instituteDetails->id)->where('roll', $request->roll)->where('status', 'Applied')->first();
            $latestStudent = AdmissionConfig::where('institute_details_id', $instituteDetails->id)
                ->where('roll', $request->roll)
                ->latest('id')   // or created_at if reliable
                ->first();

            if ($latestStudent && $latestStudent->status === 'Applied') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student already applied',
                    'unique_number' => $latestStudent->unique_id,
                ]);
            }

            // $admission_config = AdmissionConfig::with('admissionPayment')
            //     ->where('institute_details_id', $instituteDetails->id)
            //     ->where('roll', $request->roll)->first();

            $admission_config = AdmissionConfig::where('institute_details_id', $instituteDetails->id)
                ->where('roll', $request->roll)
                ->latest('id') // safer than created_at
                ->with(['admissionPayment' => function ($q) {
                    $q->latest('id'); // or created_at
                }])
                ->first();


            if (!$admission_config) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['roll' => 'Student not found against the provided roll!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Student Data Found',
                'student_data' => $admission_config,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed Student Search by roll: $e");
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        } catch (ModelNotFoundException) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid request!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
    }

    //     The student name bangla field is required.
    // The shift field is required.
    // The group field is required.

    public function studentStore(Request $request)
    {
        $rules = [
            // 'student_name_bangla' => 'required',
            'student_name_english' => 'required',
            'student_mobile' => 'required',
            'father_name_bangla' => 'nullable',
            'father_name_english' => 'required',
            'father_nid' => 'nullable',
            'mother_name_bangla' => 'nullable',
            'mother_name_english' => 'required',
            'mother_nid' => 'nullable',
            'nationality' => 'required',
            'date_of_birth' => 'required',
            'student_nid_or_birth_no' => 'required',
            'gender' => 'required',
            'religion' => 'required',
            'blood_group' => 'nullable',
            'marital_status' => 'nullable',
            'present_division' => 'required',
            'present_district' => 'required',
            'present_upozilla' => 'required',
            'present_post_office' => 'required',
            'present_post_code' => 'nullable',
            'present_address' => 'required',
            'permanent_division' => 'required',
            'permanent_district' => 'required',
            'permanent_upozilla' => 'required',
            'permanent_post_office' => 'required',
            'permanent_post_code' => 'nullable',
            'permanent_address' => 'required',
            'guardian_name' => 'nullable',
            'guardian_relation' => 'nullable',
            'guardian_mobile' => 'nullable',
            'guardian_occupation' => 'nullable',
            'guardian_yearly_income' => 'nullable',
            'guardian_property' => 'nullable',
            'academic_year' => 'required',

            'class_id' => 'required|integer',
            'class_name' => 'required',
            // 'shift' => 'required',
            // 'group' => 'required',

            'institute_id' => 'required|integer',
            'institute_name' => 'required',

            'center_id' => 'required|integer',
            'center_name' => 'required',

            'subject' => 'sometimes|nullable',
            'edu_information' => 'sometimes|nullable',
            'quota' => 'nullable',
            'vaccine' => 'nullable',
            'vaccine_name' => 'nullable',
            'vaccine_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'student_pic' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'student_birth_nid_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'other_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }


        DB::beginTransaction();
        try {
            $instituteDetails = InstituteDetail::where('institute_id', $request->institute_id)->first();

            $eduInformation = json_decode($request->edu_information, true);
            $sscRoll = null;
            if (is_array($eduInformation)) {
                // Find the SSC exam roll number
                foreach ($eduInformation as $education) {
                    if ($education['exam'] === 'SSC') {
                        $sscRoll = $education['roll'];
                        break;
                    }
                }
            }

            $uniqueNumber = Str::random(10);

            // Ensure the unique number is unique in the database
            while (AdmissionApplied::where('unique_number', $uniqueNumber)->exists()) {
                $uniqueNumber = Str::random(10);
            }

            $admission = new AdmissionApplied();
            $admission->fill($request->all());
            $admission->institute_details_id = $instituteDetails->id;
            $admission->unique_number = $uniqueNumber;
            $admission->date = Carbon::now();
            $admission->vaccine = $request->vaccine;

            $storagePath = "admission_files/{$request->institute_id}/{$uniqueNumber}";

            if (!Storage::disk('public')->exists("admission_files/{$request->institute_id}")) {
                Storage::disk('public')->makeDirectory("admission_files/{$request->institute_id}");
            }

            if ($request->hasFile('student_pic')) {
                $studentPicPath = $request->file('student_pic')->store("{$storagePath}/pic", 'public');
                $admission->student_pic = $studentPicPath;
            }

            if ($request->hasFile('student_birth_nid_file')) {
                $birthNidFilePath = $request->file('student_birth_nid_file')->store("{$storagePath}/nid", 'public');
                $admission->student_birth_nid_file = $birthNidFilePath;
            }

            if ($request->hasFile('vaccine_certificate')) {
                $vaccineCertificatePath = $request->file('vaccine_certificate')->store("{$storagePath}/vaccine", 'public');
                $admission->vaccine_certificate = $vaccineCertificatePath;
            }

            if ($request->hasFile('other_file')) {
                $otherFilePath = $request->file('other_file')->store("{$storagePath}/other_file", 'public');
                $admission->other_file = $otherFilePath;
            }

            $pay = AdmissionPayment::where('institute_details_id', $instituteDetails->id)
                ->where('academic_year', $request->academic_year)
                ->where('class', trim($request->class))
                ->where('shift', trim($request->shift))
                ->where('group', trim($request->group))
                ->first();

            if ($sscRoll) {

                // $admissionConfig = AdmissionConfig::where('institute_details_id', $instituteDetails->id)
                //     ->where('roll', $sscRoll)->first();

                $admissionConfig = AdmissionConfig::where('institute_details_id', $instituteDetails->id)
                    ->where('roll', $request->roll)
                    ->latest('id') // safer than created_at
                    ->with(['admissionPayment' => function ($q) {
                        $q->latest('id'); // or created_at
                    }])
                    ->first();


                if ($admissionConfig) {
                    $admissionConfig->update([
                        'unique_id' => $uniqueNumber,
                        'status' => 'Applied'
                    ]);
                }
            }



            $fees = AdmissionFee::where('institute_details_id', $instituteDetails->id)->first();

            $admission->amount = $pay->amount + $fees->amount;
            $admission->save();

            DB::commit();

            return response()->json(['unique_number' => $admission->unique_number, 'status' => 'success', 'message' => 'Application saved successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving admission record: ' . $e);
            return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function preview(string $unique_number)
    {
        $data = AdmissionApplied::with('institute')->where('unique_number', $unique_number)->first();
        if (!$data) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request, No Data Found!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
        try {
            $instituteDetails = InstituteDetail::find($data->institute_details_id);
            if ($instituteDetails->gateway == "SPG") {
                $payment_url = "https://live.academyims.com/api/admission-payment";
            } else {
                $payment_url = env('API_URL') . '/api/pay';
            }

            $admissionSetupData = AdmissionSetup::where('institute_details_id', $data->institute_details_id)
                ->first();

            $pay = AdmissionPayment::where('institute_details_id', $data->institute_details_id)
                ->where('academic_year', $data->academic_year)
                ->where('class', $data->class)
                ->where('shift', $data->shift)
                ->where('group', $data->group)
                ->first();

            $fees = AdmissionFee::where('institute_details_id', $data->institute_details_id)->first();

            $paid = AdmissionSpgPay::select(
                'unique_number',
                'applicant_name',
                'invoice_no',
                'transaction_id',
                'transaction_date',
                'total_amount',
                'status',
                'msg'
            )
                ->where('institute_details_id', $data->institute_details_id)
                ->where('unique_number', $unique_number)
                ->where('status', 200)
                ->where('msg', 'Success')
                ->first();
            $paymentAmount = 0;
            $paymentAmount = !empty($paid) ? $paid->total_amount : $pay->amount;

            $currentDateTime = now();
            $deadlineDateTime = Carbon::parse($pay->end_date_time);
            $isDeadlinePassed = $currentDateTime->greaterThan($deadlineDateTime);

            return response()->json([
                'status' => 'success',
                'message' => 'Application Data Found',
                'student_data' => $data,
                'admission_fee' => $paymentAmount,
                'software_fee' => $fees->amount,
                'payment_url' => $payment_url,
                'exam' => $pay->exam_enabled === 'YES' ?? false,
                'deadline' => $pay->end_date_time,
                'deadline_status' => $isDeadlinePassed ? 'passed' : 'valid',
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing admission record: ' . $e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
    }


    public function studentDataupdate(Request $request, $unique_number)
    {
        $rules = [
            'student_name_bangla' => 'sometimes|nullable',
            'student_name_english' => 'sometimes|required',
            'student_mobile' => 'sometimes|required',
            'father_name_bangla' => 'sometimes|nullable',
            'father_name_english' => 'sometimes|required',
            'father_nid' => 'sometimes|nullable',
            'mother_name_bangla' => 'sometimes|nullable',
            'mother_name_english' => 'sometimes|required',
            'mother_nid' => 'sometimes|nullable',
            'nationality' => 'sometimes|required',
            'date_of_birth' => 'sometimes|required',
            'student_nid_or_birth_no' => 'sometimes|required',
            'gender' => 'sometimes|required',
            'religion' => 'sometimes|required',
            'blood_group' => 'sometimes|nullable',
            'marital_status' => 'sometimes|nullable',
            'present_division' => 'sometimes|required',
            'present_district' => 'sometimes|required',
            'present_upozilla' => 'sometimes|required',
            'present_post_office' => 'sometimes|required',
            'present_post_code' => 'sometimes|nullable',
            'present_address' => 'sometimes|required',
            'permanent_division' => 'sometimes|required',
            'permanent_district' => 'sometimes|required',
            'permanent_upozilla' => 'sometimes|required',
            'permanent_post_office' => 'sometimes|required',
            'permanent_post_code' => 'sometimes|nullable',
            'permanent_address' => 'sometimes|required',
            'guardian_name' => 'sometimes|nullable',
            'guardian_relation' => 'sometimes|nullable',
            'guardian_mobile' => 'sometimes|nullable',
            'guardian_occupation' => 'sometimes|nullable',
            'guardian_yearly_income' => 'sometimes|nullable',
            'guardian_property' => 'sometimes|nullable',
            'academic_year' => 'sometimes|required',
            'class' => 'sometimes|required',
            'shift' => 'sometimes|required',
            'group' => 'sometimes|required',
            'subject' => 'sometimes|required',
            'edu_information' => 'sometimes|required',
            'quota' => 'sometimes|nullable',
            'vaccine' => 'sometimes|nullable',
            'vaccine_name' => 'sometimes|nullable',
            'vaccine_certificate' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png',
            'student_pic' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png',
            'student_birth_nid_file' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png',
            'other_file' => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $admission = AdmissionApplied::where('unique_number', $unique_number)->first();


            if ($request->has('edu_information')) {
                $eduInformation = json_decode($request->edu_information, true);
                $sscRoll = null;
                if (is_array($eduInformation)) {
                    foreach ($eduInformation as $education) {
                        if ($education['exam'] === 'SSC') {
                            $sscRoll = $education['roll'];
                            break;
                        }
                    }
                }
            }

            $admission->fill($request->except(['student_pic', 'student_birth_nid_file', 'vaccine_certificate', 'other_file']));

            $storagePath = "admission_files/{$admission->institute->institute_id}/{$unique_number}";
            try {
                if ($request->hasFile('student_pic')) {
                    // Delete the old file
                    if ($admission->student_pic && Storage::disk('public')->exists($admission->student_pic)) {
                        Storage::disk('public')->delete($admission->student_pic);
                    }

                    // Store the new file
                    $studentPicPath = $request->file('student_pic')->store("{$storagePath}/pic", 'public');
                    $admission->student_pic = $studentPicPath;
                }
            } catch (\Exception $e) {
                return response()->json($e->getMessage(), 400);
            }

            if ($request->hasFile('student_birth_nid_file')) {
                // Delete the old file
                if ($admission->student_birth_nid_file && Storage::disk('public')->exists($admission->student_birth_nid_file)) {
                    Storage::disk('public')->delete($admission->student_birth_nid_file);
                }

                // Store the new file
                $birthNidFilePath = $request->file('student_birth_nid_file')->store("{$storagePath}/nid", 'public');
                $admission->student_birth_nid_file = $birthNidFilePath;
            }

            if ($request->hasFile('vaccine_certificate')) {
                // Delete the old file
                if ($admission->vaccine_certificate && Storage::disk('public')->exists($admission->vaccine_certificate)) {
                    Storage::disk('public')->delete($admission->vaccine_certificate);
                }

                // Store the new file
                $vaccineCertificatePath = $request->file('vaccine_certificate')->store("{$storagePath}/vaccine", 'public');
                $admission->vaccine_certificate = $vaccineCertificatePath;
            }

            if ($request->hasFile('other_file')) {
                // Delete the old file
                if ($admission->other_file && Storage::disk('public')->exists($admission->other_file)) {
                    Storage::disk('public')->delete($admission->other_file);
                }

                // Store the new file
                $otherFilePath = $request->file('other_file')->store("{$storagePath}/other_file", 'public');
                $admission->other_file = $otherFilePath;
            }

            $admission->save();

            DB::commit();

            return response()->json(['unique_number' => $admission->unique_number, 'status' => 'success', 'message' => 'Application updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating admission record: ' . $e);
            return response()->json(['status' => 'error', 'message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function division()
    {
        $divisions = Division::all();

        return response()->json([
            'status' => 'success',
            'divisions' => $divisions,
        ]);
    }

    public function district(string $division_id)
    {
        $districts = District::where('division_id', $division_id)->get();

        return response()->json([
            'status' => 'success',
            'districts' => $districts,
        ]);
    }

    public function upozila(string $district_id)
    {
        $upazila = Upazila::where('district_id', $district_id)->get();

        return response()->json([
            'status' => 'success',
            'upazilas' => $upazila,
        ]);
    }


    public function admissionUrl(Request $request)
    {
        if (empty($request->institute_id)) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request observed!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        $institute_details = InstituteDetail::where('institute_id', $request->institute_id)->first();

        if (empty($institute_details)) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Institute not found!']);
            return response()->json([
                'errors' => $formattedErrors,
                // 'instiutes'=> $institues,
                'payload' => null,
            ], 404);
        }
        // Add the admission link based on the gateway
        if ($institute_details->gateway == 'SPG') {
            $data = env('SPG_ADMISSION') . '/' . $institute_details->institute_id;
        } elseif ($institute_details->gateway == 'SSL') {
            $data = env('SSL_ADMISSION') . '/' . $institute_details->institute_id;
        }

        return response()->json(["admission_url" => $data]);
    }

    //ssl payment start
    public function sslPayment(Request $request)
    {
        try {
            $student = AdmissionApplied::where('unique_number', $request->unique_number)->first();

            $post_data = array();
            $post_data['total_amount'] = $request->admission_fee + $request->software_fee; # You cannot pay less than 10
            $post_data['currency'] = "BDT";
            $post_data['tran_id'] = $request->unique_number; // tran_id must be unique

            # CUSTOMER INFORMATION
            $post_data['cus_name'] = $student->student_name_english;
            $post_data['cus_email'] = 'edufee.trx@gmail.com';
            $post_data['cus_phone'] = $student->student_mobile;

            $post_data['institute_details_id'] = $student->institute_details_id;
            $post_data['shipping_method'] = "NO";
            $post_data['product_name'] = "admission";
            $post_data['product_category'] = "Education";
            $post_data['product_profile'] = "admission";

            if ($student->institute_details_id == 114) {

                //  [{"sslcz_ref_id":"TST_SZ_PAR_5FB02515DFA08","amount":"300.00"},
                //  {"sslcz_ref_id":"TST_SZ_PAR_5FAFD2BF94DCD","amount":"100.00"},
                //  {"sslcz_ref_id":"TST_SZ_PAR_5FB03A4E6980E","amount":"100.00"}]

                $disbursements_acct = [
                    ["sslcz_ref_id" => "dbdc", "amount" => $request->admission_fee],
                    ["sslcz_ref_id" => "edutech", "amount" => $request->software_fee],
                ];
                // Verify that the sum of disbursements matches total_amount
                $disbursement_total = array_sum(array_column($disbursements_acct, 'amount'));
                if ($disbursement_total != $post_data['total_amount']) {
                    $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['AMT400: Cannot resolve now, try again soon!']);
                    return response()->json([
                        'errors' => $formattedErrors,
                        'payload ' => null,
                    ], 400);
                }
                $post_data['disbursements_acct'] = json_encode($disbursements_acct);
            }
            $sslinfo = SslInfo::where('institute_details_id', $student->institute_details_id)->first();

            Log::channel('ssl_log')->info("Request payload", $post_data);

            $sslc = new SslCommerzNotification($sslinfo->store_id, $sslinfo->store_password);
            # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payment gateway here )
            $payment_options = $sslc->makePayment($post_data);

            Log::channel('ssl_log')->info($payment_options);

            return response()->json($payment_options);
        } catch (\Exception $e) {
            Log::channel('ssl_log')->error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Payment initiation failed']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload ' => null,
            ], 400);
        }
    }

    public function success(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount ');
        $currency = "BDT";

        $sslc = new SslCommerzNotification(null, null);

        # Check order status in order table against the transaction id or order id.
        $order_details = AdmissionApplied::where('unique_number', $tran_id)->first();

        if (in_array($order_details->approval_status, ['pending', 'Canceled'])) {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {
                /*
                That means IPN did not work or IPN URL was not set in your merchant panel. Here you need to update order status
                in order table as Processing or Complete.
                Here you can also send sms or email for successful transaction to customer
                */
                $update_product = AdmissionApplied::where('unique_number', $tran_id)->update([
                    'approval_status' => 'Processing',
                    'date' => Carbon::now()
                ]);
                Log::channel('ssl_log')->info('Transaction is successfully completed');
            }
        } else if (in_array($order_details->approval_status, ['Processing', 'Success'])) {
            /*
             That means through IPN Order status already updated. Now you can just show the customer that transaction is completed. No need to update database.
             */
            Log::channel('ssl_log')->info('Transaction is already successfully completed');
        } else {
            # That means something wrong happened. You can redirect customer to your product page.
            Log::channel('ssl_log')->error('Succees Request: Invalid transaction');
        }
        $url = "https://edufee.online/application/preview/{$tran_id}";

        return redirect($url);
    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = AdmissionApplied::where('unique_number', $tran_id)->first();

        if ($order_details->approval_status == 'pending') {
            $update_product = AdmissionApplied::where('unique_number', $tran_id)->update(['approval_status' => 'Failed']);

            Log::channel('ssl_log')->error('Fail Request: Transaction failed');
        } else if (in_array($order_details->approval_status, ['Processing', 'Success'])) {
            Log::channel('ssl_log')->error('Fail Request: Transaction is already successful');
        } else {
            Log::channel('ssl_log')->error('Fail Request: Invalid transaction');
        }
        $url = "https://edufee.online/application/preview/{$tran_id}";
        return redirect($url);
    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_details = AdmissionApplied::where('unique_number', $tran_id)->first();

        if ($order_details->approval_status == 'pending') {
            $update_product = AdmissionApplied::where('unique_number', $tran_id)->update(['approval_status' => 'Canceled']);

            Log::channel('ssl_log')->error('Cancel Request: Transaction canceled');
        } else if (in_array($order_details->approval_status, ['Processing', 'Success'])) {
            Log::channel('ssl_log')->error('Cancel Request: Transaction is already successful');
        } else {
            Log::channel('ssl_log')->error('Cancel Request: Invalid transaction');
        }
        $url = "https://edufee.online/application/preview/{$tran_id}";
        return redirect($url);
    }

    public function ipn(Request $request)
    {
        # Received all the payment information from the gateway
        if ($request->input('tran_id')) # Check transaction id is posted or not.
        {
            $tran_id = $request->input('tran_id');

            # Check order status in order table against the transaction id or order id.
            $order_details = AdmissionApplied::where('unique_number', $tran_id)->first();
            $findSslinfo = SslInfo::where('institute_details_id', $order_details->institute_details_id)->first();

            if (!in_array($order_details->approval_status, ['Processing', 'Success'])) {
                $sslc = new SslCommerzNotification($findSslinfo->store_id, $findSslinfo->store_password);
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->amount, "BDT");

                Log::alert($validation);

                if ($validation == true) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also send sms or email for successful transaction to customer
                    */
                    $admissionPayment = AdmissionPayment::where('institute_details_id', $order_details->institute_details_id)
                        ->where('academic_year', $order_details->academic_year)
                        ->where('class', $order_details->class)
                        ->where('shift', $order_details->shift)
                        ->where('group', $order_details->group)
                        ->first();

                    $latestRoll = AdmissionApplied::where('institute_details_id', $order_details->institute_details_id)
                        ->where('academic_year', $order_details->academic_year)
                        ->where('class', $order_details->class)
                        ->where('shift', $order_details->shift)
                        ->where('group', $order_details->group)
                        ->where('approval_status', 'Success')
                        ->max('assigned_roll');

                    $newRoll = $latestRoll ? $latestRoll + 1 : $admissionPayment->roll_start;

                    $update_product = AdmissionApplied::where('unique_number', $tran_id)->update([
                        'approval_status' => 'Success',
                        'date' => Carbon::now(),
                        'assigned_roll' => $newRoll,
                    ]);
                    Log::channel('ssl_log')->info('IPN: Transaction is successfully completed');
                } else {
                    Log::channel('ssl_log')->error('IPN: Validation False');
                }
            } else if (in_array($order_details->approval_status, ['Processing', 'Success'])) {

                # That means Order status already updated. No need to update database.
                Log::channel('ssl_log')->info('IPN: Transaction is already successfully completed');
            } else {
                # That means something wrong happened. You can redirect customer to your product page.
                Log::channel('ssl_log')->error('IPN: Invalid transaction');
            }
        } else {
            Log::channel('ssl_log')->error('IPN: Invalid data');
        }
    }

    //ssl payment end

    //invoice generate
    public function admission_invoice(string $unique_number)
    {
        $data = AdmissionApplied::with('institute')->where('unique_number', $unique_number)->first();

        if (!$data) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request, No Data Found!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
        try {
            $instituteDetails = InstituteDetail::find($data->institute_details_id);

            if ($instituteDetails->gateway == "SPG") {
                $payment_url = "https://live.academyims.com/api/admission-payment";
                $paid = AdmissionSpgPay::select(
                    'unique_number',
                    'applicant_name',
                    'invoice_no',
                    'transaction_id',
                    'transaction_date',
                    'total_amount',
                    'status',
                    'msg'
                )
                    ->where('institute_details_id', $data->institute_details_id)
                    ->where('unique_number', $unique_number)
                    ->where('status', 200)
                    ->where('msg', 'Success')
                    ->first();

                if (empty($paid)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'No payment history found! Try later.',
                        'student_data' => $data,
                        'payment_url' => $payment_url,
                    ]);
                }

                // $admissionSetup = AdmissionPayment::where('institute_details_id', $data->institute_details_id)
                //     ->where('class', $data->class)
                //     ->where('shift', $data->shift)
                //     ->where('group', $data->group)
                //     ->where('academic_year', $data->academic_year)
                //     ->first();

                // $fee = AdmissionFee::where('institute_details_id', $data->institute_details_id)->first();
                // $actualAmount = (float)$admissionSetup->amount + (float)$fee->amount;

                if ((float)$paid->total_amount != (float)$data->amount) {
                    $data->amount = $paid->total_amount;
                    $data->save();
                }
            } else {
                $payment_url = env('API_URL') . '/api/pay';
            }

            if ($data->approval_status !== "Success") {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Do Payment First',
                    'student_data' => $data,
                    'payment_url' => $payment_url,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Application Data Found',
                'student_data' => $data,
                'institute_details' => $instituteDetails,
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing admission record: ' . $e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
    }

    public function admission_admit(string $unique_number)
    {
        $data = AdmissionApplied::with('institute')->where('unique_number', $unique_number)->first();

        if (!$data) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request, No Data Found!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
        try {
            $instituteDetails = InstituteDetail::find($data->institute_details_id);

            if ($instituteDetails->gateway == "SPG") {
                $payment_url = "https://live.academyims.com/api/admission-payment";
            } else {
                $payment_url = env('API_URL') . '/api/pay';
            }

            if ($data->approval_status != "Success") {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Do Payment First',
                    'student_data' => $data,
                    'payment_url' => $payment_url,
                ]);
            }

            $admissionPayment = AdmissionPayment::where('institute_details_id', $data->institute_details_id)
                ->where('class', $data->class)
                ->where('shift', $data->shift)
                ->where('group', $data->group)
                ->where('academic_year', $data->academic_year)
                ->where('exam_enabled', 'YES')
                ->first();

            if (!$admissionPayment) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request, Admission Setup Not Found!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            $fee = AdmissionFee::where('institute_details_id', $data->institute_details_id)->first();
            if (!$fee) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['Invalid Request, Admission Fee Not Found!']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            $actualAmount = (float)$admissionPayment->amount + (float)$fee->amount;

            if ($actualAmount != (float)$data->amount) {
                $data->amount = $actualAmount;
                $data->save();
            }

            $admissionSetupData = AdmissionSetup::where('institute_details_id', $data->institute_details_id)
                ->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Application Data Found',
                'student_data' => $data,
                'institute_details' => $instituteDetails,
                'admission_payment' => $admissionPayment,
                'admission_setup' => $admissionSetupData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing admission record: ' . $e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
    }
    // qj7fHh33sF
    public function liveFixation()
    {
        $problemCases = AdmissionApplied::whereNotNull('unique_number')
            ->whereNotIn('institute_details_id', [3, 84])
            ->where(function ($query) {
                $query->where('subject', 'LIKE', '%{"compulsory":[]%')
                    ->orWhere('edu_information', '[]')
                    ->orWhere('edu_information', 'LIKE', '[{"exam":"",%');
            })
            ->orderBy('institute_details_id')
            ->get();


        $eduInfoUpdateCount = 0;
        $subjectUpdateCount = 0;
        $eitherChanges = 0;
        $changedID = [];
        $testArray = [];

        $boards = ['Dhaka', 'Barisal', 'Chittagong', 'Comilla', 'Jessore', 'Mymensingh', 'Rajshahi', 'Sylhet', 'Dinajpur', 'Bangladesh Madrasah Education Board', 'Bangladesh Technical Education Board', 'Directorate of Primary Education'];

        foreach ($problemCases as $case) {
            $config = AdmissionConfig::where('unique_id', $case->unique_number)->first();
            if (empty($config)) {
                Log::channel('bug_fix_log')->error("No config found for: $case->unique_number");
                continue;
            }

            $changeInOne = false;
            if ($case->edu_information == '[]') {
                $eduInfo = [];
                $eduInfo[] = [
                    "exam" => "SSC",
                    "board" => in_array(trim($config->board), $boards) ? $config->board : '',
                    "institute" => "",
                    "group" => "",
                    "roll" => $config->roll,
                    "registration" => "",
                    "gpa" => "",
                    "passingYear" => $config->passing_year ?? '',
                ];

                $newEduVal = json_encode($eduInfo);

                $case->edu_information = $newEduVal;
                $case->save();

                $eduInfoUpdateCount++;
                $changeInOne = true;
                $changedID['edu'][] = $case->unique_number;
                // return response()->json($newEduVal);
            } else {
                $eduInfo = json_decode($case->edu_information, true);
                $eduInfo[0]['exam'] = $eduInfo[0]['exam'] == '' ? 'SSC' : $eduInfo[0]['exam'];
                $eduInfo[0]['roll'] = $eduInfo[0]['roll'] == '' ? $config->roll : $eduInfo[0]['roll'];

                $newEduVal = json_encode($eduInfo);

                $case->edu_information = $newEduVal;
                $case->save();

                // if ($eduInfo[0]['exam'] == '') {
                //     $testArray['unique_number'][] = $case->unique_number;
                //     $testArray[] = json_decode($case->edu_information, true);
                // }
            }


            $subjects = json_decode($case->subject, true);
            if (count($subjects['compulsory']) <= 0) {
                $subjectObj = AdmissionSubjectSetup::where('institute_details_id', $case->institute_details_id)
                    ->where('class_name', $case->class)
                    ->where('group_name', $case->group)
                    ->first();

                if (empty($subjectObj)) {
                    Log::channel('bug_fix_log')->error("No subject setup found for: $case->unique_number");
                }

                $subjectSets = json_decode($subjectObj, true);
                $newSubVal = json_decode($subjectSets['compulsory'], true);

                $subjects['compulsory'] = $newSubVal;
                $case->subject = $subjects;
                $case->save();

                $subjectUpdateCount++;
                $changeInOne = true;
                $changedID['sub'][] = $case->unique_number;
                // return response()->json($subjects);
            }

            if ($changeInOne) {
                $eitherChanges++;
            }
        }

        Log::channel('bug_fix_log')->info('impacted id: ', $changedID);
        Log::channel('bug_fix_log')->info("Total Changed Subject: $subjectUpdateCount, Total Changed Subject: $subjectUpdateCount, overall changes: $eitherChanges");
        return response()->json($problemCases);
    }


    public function fixAssignRoll()
    {
        $newlyAssignedRolls = [];

        DB::transaction(function () use (&$newlyAssignedRolls) {
            // Find all successful applications without assigned rolls
            $applications = AdmissionApplied::where('approval_status', 'Success')
                ->whereNull('assigned_roll')
                ->lockForUpdate()
                ->get();

            foreach ($applications as $application) {
                // Find the corresponding AdmissionPayment record
                $admissionPayment = AdmissionPayment::where([
                    'institute_details_id' => $application->institute_details_id,
                    'academic_year' => $application->academic_year,
                    'class' => $application->class,
                    'shift' => $application->shift,
                    'group' => $application->group,
                ])->first();

                if ($admissionPayment) {
                    // Get the highest assigned roll for this criteria
                    $maxAssignedRoll = AdmissionApplied::where([
                        'institute_details_id' => $admissionPayment->institute_details_id,
                        'academic_year' => $admissionPayment->academic_year,
                        'class' => $admissionPayment->class,
                        'shift' => $admissionPayment->shift,
                        'group' => $admissionPayment->group,
                    ])->max('assigned_roll');

                    // Determine the next roll number
                    $nextRoll = $maxAssignedRoll ? $maxAssignedRoll + 1 : $admissionPayment->roll_start;

                    // Assign the roll
                    $application->update(['assigned_roll' => $nextRoll]);

                    // Add the unique number to the result array
                    $newlyAssignedRolls[] = $application->unique_number;
                }
            }
        }, 5); // Retry the transaction up to 5 times in case of a deadlock

        // Log the results
        // Log::channel('bug_fix_log')->info('Roll assignment completed. Total AdmissionPayments processed: ' . $admissionPayments->count());
        return response()->json([
            'message' => 'Missing rolls assigned successfully',
            'newly_assigned_rolls' => $newlyAssignedRolls
        ]);
    }
}
