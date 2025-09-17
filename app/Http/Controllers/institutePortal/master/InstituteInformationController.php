<?php

namespace App\Http\Controllers\institutePortal\master;

use App\Helpers\ApiResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\InstituteDetail;
use App\Http\Controllers\Controller;
use App\Utils\ServerErrorMask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InstituteInformationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instituteDetails = InstituteDetail::where('id', Auth::user()->institute_details_id)->first();
        return response()->json([
            'instituteDetails' => $instituteDetails
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
        //
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
        // return $request->all();
        $rules = [
            'institute_id' => 'nullable',
            'institute_name' => 'nullable',
            'institute_contact' => 'nullable',
            'institute_email' => 'nullable',
            'institute_address' => 'nullable',
            'institute_district' => 'nullable',
            'institute_upozilla' => 'nullable',
            'institute_division' => 'nullable',
            'logo' => 'nullable|image|mimes:jpeg,png,gif',
        ]; 
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        try {

            $instituteDetail = InstituteDetail::findOrFail($id);

            if (!$instituteDetail) {
                return response()->json(['error' => 'Record not found'], Response::HTTP_NOT_FOUND);
            }

            if (!empty($request->institute_id)) {
                $instituteDetail->institute_id = $request->institute_id;
            }
            
            if (!empty($request->institute_name)) {
                $instituteDetail->institute_name = $request->institute_name;
            }
          
            if (!empty($request->institute_contact)) {
                $instituteDetail->institute_contact = $request->institute_contact;
            }
            if (!empty($request->institute_email)) {
                $instituteDetail->institute_email = $request->institute_email;
            }
            
            if (!empty($request->institute_address)) {
                $instituteDetail->institute_address = $request->institute_address;
            }
            if (!empty($request->institute_district)) {
                $instituteDetail->institute_district = $request->institute_district;
            }
            if (!empty($request->institute_upozilla)) {
                $instituteDetail->institute_upozilla = $request->institute_upozilla;
            }
            if (!empty($request->institute_division)) {
                $instituteDetail->institute_division = $request->institute_division;
            }

            // Handle logo image upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $extension = $logo->getClientOriginalExtension();
                $filename = 'logo_' . $instituteDetail->institute_id . '.' . $extension;
                $path = $logo->storeAs($instituteDetail->institute_id . '/logo', $filename, 'public');

                // Update the 'logo' field in the database with the file path
                $instituteDetail->logo = $path;
            }

            // Save the model after updating the 'logo' attribute
            $instituteDetail->save();

            return response()->json(['status' => 'success', 'message' => 'Record updated successfully'], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Institute not found']);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 404);
        } catch (QueryException  $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
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
