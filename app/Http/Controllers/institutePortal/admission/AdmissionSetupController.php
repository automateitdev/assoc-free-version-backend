<?php

namespace App\Http\Controllers\institutePortal\admission;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AdmissionSetup;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AdmissionSetupResource;
use App\Models\InstituteDetail;

class AdmissionSetupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admissionSetup = AdmissionSetup::where('institute_details_id', Auth::user()->institute_details_id)->first();

        if (empty($admissionSetup)) {
            $data = null;
            return response()->json($data);
        }
        $institute_details = InstituteDetail::find(Auth::user()->institute_details_id);
        
        // Prepare the return data
        $data = [
            'id' => $admissionSetup ? $admissionSetup->id : null,
            'institute_id' => $admissionSetup->institute->institute_id,
            'institute_name' => $admissionSetup->institute->institute_name,
            'enabled' => $admissionSetup->enabled,
            'heading' => $admissionSetup->heading,
            'form' => $admissionSetup->form,
            'subject' => $admissionSetup->subject,
            'academic_info' => $admissionSetup->academic_info,
        ];

        // Add the admission link based on the gateway
        if ($institute_details->gateway == 'SPG') {
            $data['admission_link'] = env('SPG_ADMISSION'). '/' .$admissionSetup->institute->institute_id;
        } elseif ($institute_details->gateway == 'SSL') {
            $data['admission_link'] = env('SSL_ADMISSION'). '/' .$admissionSetup->institute->institute_id;
        }

        return response()->json($data);
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
        // dd(Auth::user()->institute_detail->institute_id);
        $validator = Validator::make($request->all(), [
            'enabled' => 'required',
            'heading' => 'nullable',
            'form' => 'required',
            'subject' => 'required|in:YES,NO',
            'academic_info' => 'required|in:YES,NO'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $admissionSetup = new AdmissionSetup();
        $admissionSetup->institute_details_id = Auth::user()->institute_details_id;
        $admissionSetup->subject = $request->subject;
        $admissionSetup->academic_info = $request->academic_info;
        $admissionSetup->enabled = $request->enabled;
        $admissionSetup->heading = $request->heading ?? "Online Admission";
        $admissionSetup->form = $request->form;
        $admissionSetup->save();

        return response()->json([
            $admissionSetup,
            'status' => 'success',
            'message' => 'Admission Setup successfully'
        ], Response::HTTP_CREATED);
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
        $validator = Validator::make($request->all(), [
            'enabled' => 'required',
            'heading' => 'nullable',
            'form' => 'required',
            'subject' => 'required',
            'academic_info' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $admissionSetup = AdmissionSetup::find($id);

        if (!$admissionSetup) {
            return response()->json(['status' => 'error', 'message' => 'Admission Setup not found'], Response::HTTP_NOT_FOUND);
        }

        $admissionSetup->institute_details_id = Auth::user()->institute_details_id;
        $admissionSetup->enabled = $request->enabled;
        $admissionSetup->heading = $request->heading;
        $admissionSetup->form = $request->form;
        $admissionSetup->subject = $request->subject;
        $admissionSetup->academic_info = $request->academic_info;
        $admissionSetup->save();

        return response()->json([
            $admissionSetup,
            'status' => 'success', 
            'message' => 'Admission Setup updated successfully'], Response::HTTP_OK);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
