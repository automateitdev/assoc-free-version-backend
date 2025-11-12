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
use App\Models\AdmissionPayment;
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
        $distinctCombinations = AdmissionPayment::select('academic_year_id', 'class_id', 'center_id')
            ->distinct()
            ->with(['academicYear:id,name', 'class:id,name', 'center:id,name'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Fetched exam essentials',
            'essentials' => $distinctCombinations ?? [],
        ]);
    }
}
