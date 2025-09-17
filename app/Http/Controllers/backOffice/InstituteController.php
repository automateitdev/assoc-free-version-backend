<?php

namespace App\Http\Controllers\backOffice;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\InstituteConfig;
use App\Models\InstituteDetail;
use App\Models\InstituteProfile;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\InstituteInfoShowResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstituteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $institute = InstituteDetail::get();
        return response()->json([
            'instituteDetails' => InstituteInfoShowResource::collection($institute)
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
            'institute_id' => 'required|unique:institute_details,institute_id',
            'institute_name' => 'nullable',
            'institute_ein' => 'required|unique:institute_details,institute_ein',
            'institute_contact' => 'nullable',
            'institute_email' => 'nullable',
            'institute_category' => 'nullable',
            'institute_type' => 'nullable',
            'institute_board' => 'nullable',
            'institute_address' => 'nullable',
            'institute_district' => 'nullable',
            'institute_sub_district' => 'nullable', // Corrected typo
            'institute_division' => 'nullable',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048', // Added validation for the logo
            'gateway' => 'required',
            'username' => 'required|unique:institute_profiles,username',
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

            // Handle logo file upload
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $instituteId = $request->input('institute_id');
                $logoFileName = $request->file('logo')->getClientOriginalName();
                $directoryPath = "institute/{$instituteId}/logo";

                // Ensure the directory exists
                Storage::disk('public')->makeDirectory($directoryPath);

                // Store the file in the specific directory
                $logoPath = $request->file('logo')->storeAs($directoryPath, $logoFileName, 'public');
            }

            // Create institute details record
            $institute_details = InstituteDetail::create([
                'institute_id' => $request->input('institute_id'),
                'institute_name' => $request->input('institute_name'),
                'institute_ein' => $request->input('institute_ein'),
                'institute_contact' => $request->input('institute_contact'),
                'institute_email' => $request->input('institute_email'),
                'institute_category' => $request->input('institute_category'),
                'institute_type' => $request->input('institute_type'),
                'institute_board' => $request->input('institute_board'),
                'institute_address' => $request->input('institute_address'),
                'institute_district' => $request->input('institute_district'),
                'institute_sub_district' => $request->input('institute_sub_district'),
                'institute_division' => $request->input('institute_division'),
                'logo' => $logoPath, // Save the logo path
                'gateway' => $request->input('gateway'),
            ]);

            $input = new InstituteProfile();
            $input->institute_details_id = $institute_details->id;
            $input->username = strtolower($request->username);
            $input->email = $request->institute_email;
            $input->mobile = $request->institute_contact;
            $input->name = $request->institute_name;
            $input->user_type = $request->role ?? 'admin';
            $input->password = Hash::make($request->password);
            $input->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Institute details created successfully',
                'payload' => $institute_details,
            ], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Institute create error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Institute create error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Institute create error: " . $e->getMessage());
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
        try {
            $institute_details = InstituteDetail::with('institute_profile')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Institute details retrieved successfully',
                'payload' => $institute_details,
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Institute details not found: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error retrieving institute details: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $institute_details = InstituteDetail::with('institute_profile')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Institute details retrieved successfully',
                'payload' => $institute_details,
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Institute details not found: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error retrieving institute details: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'institute_name' => 'nullable',
            'institute_contact' => 'nullable',
            'institute_email' => 'nullable',
            'institute_category' => 'nullable',
            'institute_type' => 'nullable',
            'institute_board' => 'nullable',
            'institute_address' => 'nullable',
            'institute_district' => 'nullable',
            'institute_sub_district' => 'nullable',
            'institute_division' => 'nullable',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gateway' => 'nullable',
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

            $institute_details = InstituteDetail::findOrFail($id);

            // Handle logo file upload
            $logoPath = $institute_details->logo;
            if ($request->hasFile('logo')) {
                // Delete the existing logo if it exists
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }

                $instituteId = $institute_details->institute_id; // Use existing institute_id
                $logoFileName = $request->file('logo')->getClientOriginalName();
                $directoryPath = "institute/{$instituteId}/logo";

                // Store the file in the specific directory
                $logoPath = $request->file('logo')->storeAs($directoryPath, $logoFileName, 'public');
            }

            // Prepare the data to be updated
            $updateData = array_filter([
                'institute_name' => $request->input('institute_name'),
                'institute_contact' => $request->input('institute_contact'),
                'institute_email' => $request->input('institute_email'),
                'institute_category' => $request->input('institute_category'),
                'institute_type' => $request->input('institute_type'),
                'institute_board' => $request->input('institute_board'),
                'institute_address' => $request->input('institute_address'),
                'institute_district' => $request->input('institute_district'),
                'institute_sub_district' => $request->input('institute_sub_district'),
                'institute_division' => $request->input('institute_division'),
                'logo' => $logoPath,
                'gateway' => $request->input('gateway'),
            ], fn ($value) => !is_null($value));

            $institute_details->update($updateData);

            $profile = InstituteProfile::where('institute_details_id', $id)->firstOrFail();

            $profileUpdateData = array_filter([
                'email' => $request->institute_email,
                'mobile' => $request->institute_contact,
                'name' => $request->institute_name,
                'user_type' => $request->role ?? 'admin',
                'password' => $request->password ? Hash::make($request->password) : null,
            ], fn ($value) => !is_null($value));

            $profile->update($profileUpdateData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Institute details updated successfully',
                'payload' => $institute_details,
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Institute update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Institute update error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Institute update error: " . $e->getMessage());
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
        //
    }
}
