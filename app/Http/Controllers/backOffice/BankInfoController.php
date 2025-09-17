<?php

namespace App\Http\Controllers\backOffice;

use App\Models\BankInfo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\InstituteDetail;
use App\Http\Controllers\Controller;
use App\Http\Resources\BankInfoResource;
use Illuminate\Support\Facades\Validator;

class BankInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bankInfo = BankInfo::all();
        return response()->json([
            'bankInfo' => BankInfoResource::collection($bankInfo)
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
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_no' => 'required',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        $instituteDetails = InstituteDetail::find($request->institute_details_id);

        $check = BankInfo::where('institute_details_id', $instituteDetails->id)->first();
        if($check)
        {
            return response()->json([$check, 'status' => 'success', 'message' => 'Bank Already Added'], Response::HTTP_CREATED);
        }
        $input = new BankInfo();
        $input->institute_details_id = $request->institute_details_id;
        $input->bank_name = $request->bank_name;
        $input->account_name = $request->account_name;
        $input->account_no = $request->account_no;
        $input->save(); 

        return response()->json([$input, 'status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
