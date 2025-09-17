<?php

namespace App\Http\Controllers\backOffice;

use App\Models\SslInfo;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\InstituteDetail;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\SslInfoResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SslInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sslinfo = SslInfo::all();
        return response()->json([
            'sslInfo' => SslInfoResource::collection($sslinfo)
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
            'store_id' => 'required',
            'store_password' => 'required'
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
            $sslinfo = SslInfo::create($request->all());
            DB::commit();
            return response()->json([$sslinfo, 'status' => 'success', 'message' => 'SSL account added successfully!'], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("SSL info add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json(['errors' => $systemError,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("SSL info add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json(['errors' => $systemError,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SSL info add error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json(['errors' => $systemError,
            ], 500);
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
    public function update(Request $request, $id)
    {
        $rules = [
            'institute_details_id' => 'required',
            'store_id' => 'required',
            'store_password' => 'required'
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

            // Find the SSL info record
            $sslinfo = SslInfo::findOrFail($id);

            // Update the record with new data
            $sslinfo->update($request->all());

            DB::commit();

            return response()->json([
                $sslinfo,
                'status' => 'success',
                'message' => "SSL: $sslinfo->store_id, Updated successfully."
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("SSL info update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("SSL info update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SSL info update error: " . $e);
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
        $sslinfo = SslInfo::findOrFail($id);

        $sslinfo->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'SSL info record deleted successfully',
        ], 200);
    }
}
