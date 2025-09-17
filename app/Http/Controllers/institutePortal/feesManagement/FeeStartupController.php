<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Models\Waiver;
use App\Models\FeeHead;
use App\Models\FeeSubhead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\WaiverResource;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\FeeSubheadResource;

class FeeStartupController extends Controller
{
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeSubhead = FeeSubhead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $waiver = Waiver::where('institute_details_id', Auth::user()->institute_details_id)->get();

        return response()->json([
            'feeHead' => FeeHeadResource::collection($feeHead),
            'feeSubhead' => FeeSubheadResource::collection($feeSubhead),
            'waiver' => WaiverResource::collection($waiver),
        ]);
    }

    public function feeHeadStore(Request $request)
    {
        $rules = [
            'name' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $input = new FeeHead();
        $input->institute_details_id = Auth::user()->institute_details_id;
        $input->name = $request->name;
        $input->save(); 

        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }

    // public function feeHeadDestroy($id)
    // {
    //     $check = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)
    //                 ->where('fee_head_id', $id)->first();
        
    //     if ($check) {
    //         return response()->json(['status' => 'error', 'message' => 'Fee Head Mapping exists. Deletion not allowed.'], Response::HTTP_CONFLICT);
    //     }

    //     FeeHead::find($id)->delete();

    //     return response()->json(['status' => 'success', 'message' => 'Fee Head deleted successfully.'], Response::HTTP_OK);
    // }


    public function feeSubheadStore(Request $request)
    {
        $rules = [
            'name' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $input = new FeeSubhead();
        $input->institute_details_id = Auth::user()->institute_details_id;
        $input->name = $request->name;
        $input->save(); 

        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }

    // public function feeSubheadDestroy($id)
    // {
    //     $check = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)
    //                 ->where('fee_subhead_id', $id)->first();
        
    //     if ($check) {
    //         return response()->json(['status' => 'error', 'message' => 'Fee Subhead Mapping exists. Deletion not allowed.'], Response::HTTP_CONFLICT);
    //     }

    //     FeeSubhead::find($id)->delete();

    //     return response()->json(['status' => 'success', 'message' => 'Fee Subhead deleted successfully.'], Response::HTTP_OK);
    // }
    
    public function waiverStore(Request $request)
    {
        $rules = [
            'name' => 'required'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $input = new Waiver();
        $input->institute_details_id = Auth::user()->institute_details_id;
        $input->name = $request->name;
        $input->save(); 

        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }
}
