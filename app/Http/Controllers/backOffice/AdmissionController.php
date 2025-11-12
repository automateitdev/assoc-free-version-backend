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
use App\Models\AdmissionPayment;
use App\Models\Exam;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    public function getAdmissionExamList() {
        Exam::
    }

    public function admissionExamSave(Request $request)
    {
        $rules = [
            'name'                  => 'required|string',
            'academic_year'         => 'required|string',
            'academic_year_id'      => 'required|integer',
            'class_id'              => 'required|integer',
            'class_name'            => 'required|string',
            'centers'               => 'required|array|min:1',
            'centers.*.center_id'   => 'required|integer|min:1',
            'centers.*.center_name' => 'required|string|min:1',
            'total_mark'            => 'required|numeric'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }


        $examFound = Exam::where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->first();

        if ($examFound) {
            // Get existing center IDs already linked
            $existingCenterIds = $examFound->centerExams()->pluck('center_id')->toArray();

            // Filter incoming centers to only add new ones
            $newCenters = collect($request->centers)
                ->filter(fn($c) => !in_array($c['center_id'], $existingCenterIds))
                ->map(fn($c) => [
                    'exam_id'     => $examFound->id,
                    'exam_name'   => $examFound->name,
                    'center_id'   => $c['center_id'],
                    'center_name' => $c['center_name'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])
                ->toArray();

            if (!empty($newCenters)) {
                // Insert only the new centers
                DB::table('center_exam')->insert($newCenters);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Centers updated successfully for existing exam.',
            ]);
        }



        // ✅ Save exam
        $exam = new Exam();
        $exam->institute_details_id = Auth::user()->institute_detail->institute_details_id;
        $exam->academic_year      = $request->academic_year;
        $exam->academic_year_id   = $request->academic_year_id;
        $exam->class_id           = $request->class_id;
        $exam->class_name         = $request->class_name;
        $exam->name               = $request->name;
        $exam->total_marks        = $request->filled('total_mark') ? $request->total_mark : null;
        $exam->is_generic         = true;

        $exam->save();

        // ✅ Save pivot table manually
        $centerExamRows = collect($request->centers)->map(function ($center) use ($exam) {
            return [
                'exam_id'      => $exam->id,
                'exam_name'      => $exam->name,
                'center_id'    => $center['center_id'],
                'center_name'  => $center['center_name'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        })->toArray();

        DB::table('center_exam')->insert($centerExamRows);

        return response()->json([
            'status' => 'success',
            'message' => 'Exam and centers saved successfully'
        ]);
    }
}
