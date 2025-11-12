<?php

namespace App\Http\Controllers\backOffice;

use App\Models\SslInfo;
use App\Models\AdmissionFee;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AdmissionInstruction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AdmissionFeeResource;
use App\Models\AcademicDetail;
use App\Models\AdmissionApplied;
use App\Models\AdmissionPayment;
use App\Models\CenterExam;
use App\Models\Exam;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class AdmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admissionFee = AdmissionFee::all();
        return response()->json([
            'admissionInfo' => AdmissionFeeResource::collection($admissionFee)
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
            'institute_details_id' => 'required',
            'amount' => 'required',
            'status' => 'required'
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
            DB::beginTransaction();
            // Create institute details record
            $admissionFee = AdmissionFee::updateOrCreate([
                'institute_details_id'   => $request->institute_details_id,
            ], [
                'amount'     => $request->amount,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'errors' => null,
                'status' => 'success',
                'message' => 'Admission fee assigned successfully!',
                'admissionFee' => $admissionFee,
            ], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Admission Fee add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Admission Fee add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admission Fee add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $admissionFee = AdmissionFee::find($id);
        return response()->json([
            'admissionInfo' => new AdmissionFeeResource($admissionFee)
        ]);
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
    public function update(Request $request, $id)
    {
        $rules = [
            'institute_details_id' => 'required',
            'amount' => 'required',
            'status' => 'required'
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
            DB::beginTransaction();

            // Find the Admission Fee record
            $admissionFee = AdmissionFee::findOrFail($id);

            // Update the record with new data
            $admissionFee->update($request->all());

            DB::commit();

            return response()->json([
                'errors' => null,
                'payload' => $admissionFee,
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Admission Fee update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Admission Fee update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admission Fee update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $admissionFee = AdmissionFee::findOrFail($id);

        $admissionFee->delete();

        return response()->json([
            'errors' => null,
            'payload' => 'Admission Fee record deleted successfully',
        ], 200);
    }

    public function pay_instruction(Request $request)
    {
        $rules = [
            'instruction' => 'required',
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
            DB::beginTransaction();

            $instruction = AdmissionInstruction::first();

            if ($instruction) {
                $instruction->instruction = $request->instruction;
                $instruction->save();
            } else {
                $instruction = new AdmissionInstruction();
                $instruction->instruction = $request->instruction;
                $instruction->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Admission instruction added successfully!',
                'instruction' => $instruction,
            ], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Admission Instruction add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Admission Instruction add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admission Instruction add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    public function instruction()
    {
        $instruction = AdmissionInstruction::first();
        return response()->json([
            'instruction' => $instruction
        ]);
    }

    public function examEssentials()
    {
        $grouped = AdmissionPayment::select(
            'academic_year_id',
            'academic_year',
            'class_id',
            'class_name',
            'center_id',
            'center_name'
        )
            ->distinct()
            ->get()
            ->groupBy(fn($item) => $item->academic_year_id . '-' . $item->class_id)
            ->map(function ($group) {
                return [
                    'academic_year_id' => $group->first()->academic_year_id,
                    'academic_year' => $group->first()->academic_year,
                    'class_id' => $group->first()->class_id,
                    'class_name' => $group->first()->class_name,
                    'centers' => $group->map(fn($g) => [
                        'id' => $g->center_id,
                        'name' => $g->center_name,
                    ])->unique('id')->values()
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'message' => 'Fetched exam essentials',
            'essentials' => $grouped ?? [],
        ]);
    }

    public function getAdmissionExamList()
    {
        $instituteDetailsId = Auth::user()->institute_details_id;
        $examList = Exam::where('institute_details_id', $instituteDetailsId)->with('centerExams')->get();
        return response()->json(['status' => 'success', 'exams' => $examList]);
    }

    public function admissionExamSave(Request $request)
    {
        // Base validation rules
        $baseRules = [
            'academic_year_id' => 'required|integer',
            'class_id'         => 'required|integer',
        ];

        // Run base validation first
        $validator = Validator::make($request->all(), $baseRules);
        if ($validator->fails()) {
            return response()->json([
                'errors' => ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray()),
                'payload' => null,
            ], 422);
        }

        // Check if exam exists for this institute/year/class
        $exam = Exam::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->first();

        // ✅ If exam already exists → update and sync new centers
        if ($exam) {
            if ($request->filled('name')) {
                $exam->name = $request->name;
            }

            if ($request->filled('total_mark')) {
                $exam->total_marks = $request->total_mark;
            }

            $exam->save();

            // Get existing center IDs already linked
            $existingCenterIds = $exam->centerExams()->pluck('center_id')->toArray();

            // Filter new centers
            $newCenters = collect($request->centers ?? [])
                ->filter(fn($c) => !in_array($c['center_id'], $existingCenterIds))
                ->map(fn($c) => [
                    'exam_id'     => $exam->id,
                    'exam_name'   => $exam->name,
                    'center_id'   => $c['center_id'],
                    'center_name' => $c['center_name'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])
                ->values()
                ->toArray();

            if (!empty($newCenters)) {
                DB::table('center_exam')->insert($newCenters);
            }

            return response()->json([
                'status'  => 'success',
                'message' => empty($newCenters)
                    ? 'Exam updated successfully. (No new centers added)'
                    : 'Exam updated and new centers added successfully.',
            ]);
        }

        // ✅ Exam does not exist → validate full data for creation
        $createRules = array_merge($baseRules, [
            'name'                  => 'required|string',
            'academic_year'         => 'required|string',
            'class_name'            => 'required|string',
            'centers'               => 'required|array|min:1',
            'centers.*.center_id'   => 'required|integer|min:1',
            'centers.*.center_name' => 'required|string|min:1',
            'total_mark'            => 'required|numeric',
        ]);

        $validator = Validator::make($request->all(), $createRules);
        if ($validator->fails()) {
            return response()->json([
                'errors' => ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray()),
                'payload' => null,
            ], 422);
        }

        // ✅ Create new exam and associated centers
        DB::transaction(function () use ($request) {
            $exam = Exam::create([
                'institute_details_id' => Auth::user()->institute_details_id,
                'academic_year'        => $request->academic_year,
                'academic_year_id'     => $request->academic_year_id,
                'class_id'             => $request->class_id,
                'class_name'           => $request->class_name,
                'name'                 => $request->name,
                'total_marks'          => $request->total_mark,
                'is_generic'           => true,
            ]);

            $centerExamRows = collect($request->centers)->map(fn($center) => [
                'exam_id'      => $exam->id,
                'exam_name'    => $exam->name,
                'center_id'    => $center['center_id'],
                'center_name'  => $center['center_name'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ])->toArray();

            DB::table('center_exam')->insert($centerExamRows);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Exam and centers saved successfully.',
        ]);
    }



    public function removeExamCenter($center_id)
    {
        try {
            $centerExam = CenterExam::find($center_id);

            if (!$centerExam) {
                $formattedErrors = ApiResponseHelper::formatErrors(
                    ApiResponseHelper::INVALID_REQUEST,
                    ['Requested center not found!']
                );
                return response()->json([
                    'errors' => $formattedErrors,
                ], 400);
            }

            $centerExam->delete();

            return response()->json([
                "status" => "success",
                "message" => "Center detached from the exam successfully"
            ]);
        } catch (\Exception $e) {
            Log::error("Could not remove exam center:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                ServerErrorMask::SERVER_ERROR
            );

            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }



    public function removeExam($exam_id)
    {
        try {
            $exam = Exam::find($exam_id);

            if (!$exam) {
                $formattedErrors = ApiResponseHelper::formatErrors(
                    ApiResponseHelper::INVALID_REQUEST,
                    ['Requested exam not found!']
                );
                return response()->json([
                    'errors' => $formattedErrors,
                ], 400);
            }

            DB::transaction(function () use ($exam) {
                // Delete related centers first
                $exam->centerExams()->delete();

                // Then delete the exam
                $exam->delete();
            });

            return response()->json([
                "status" => "success",
                "message" => "Exam removed successfully"
            ]);
        } catch (\Exception $e) {
            Log::error("Could not remove exam:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                ServerErrorMask::SERVER_ERROR
            );

            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }


    public function getAdmissionExamineeList(Request $request)
    {
        $rules = [
            "exam"          => 'required|integer',
            "academic_year" => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'errors'  => ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray()),
                'payload' => null,
            ], 422);
        }

        $exam = Exam::where('institute_details_id', Auth::user()->institute_details_id)
            ->find($request->exam);

        if (!$exam) {
            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::INVALID_REQUEST,
                ['Requested exam not found or does not belong to this institute!']
            );
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        // ✅ Get all center IDs linked to this exam
        $centers = $exam->centerExams->pluck('center_id')->toArray();

        // ✅ Fetch examinees that belong to those centers
        $list = AdmissionApplied::select(
            'id',
            'unique_number',
            'student_name_english',
            'institute_details_id',
            'guardian_mobile',
            'class_id',
            'class_name',
            'academic_year_id',
            'academic_year',
            'center_id',
            'center_name',
            'approval_status',
            'status',
            'assigned_roll'
        )
            ->where('institute_details_id', Auth::user()->institute_details_id)
            ->whereIn('center_id', $centers)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $exam->class_id)
            ->whereNotNull('assigned_roll')
            ->where('approval_status', 'Success')
            ->where('status', 200)
            ->orderBy('center_name')
            ->orderBy('assigned_roll')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Examinee list fetched successfully.',
            'list'   => $list,
        ]);
    }
}
