<?php

namespace App\Http\Controllers\institutePortal\feesManagement;

use App\Models\FeeHead;
use App\Models\FeeMapping;
use App\Models\FeeSubhead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FeeHeadResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\FeeMappingResource;
use App\Http\Resources\FeeSubheadResource;

class FeeMappingController extends Controller
{
    public function index()
    {
        $feeHead = FeeHead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeSubhead = FeeSubhead::where('institute_details_id', Auth::user()->institute_details_id)->get();
        $feeMapping = FeeMapping::where('institute_details_id', Auth::user()->institute_details_id)->get();
        return response()->json([
            'feeHead' => FeeHeadResource::collection($feeHead),
            'feeSubhead' => FeeSubheadResource::collection($feeSubhead),
            'feeMapping' => FeeMappingResource::collection($feeMapping)
        ]);

    }
    public function feeMappingStore(Request $request)
    {
        $rules = [
            'fee_head_id' => 'required',
            'fee_subhead_id' => 'required|array|min:1',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
        foreach ($request->fee_subhead_id as $feesubhead_id) {
                $check = FeeMapping::Where('institute_details_id', Auth::user()->institute_details_id)
                                    ->where('fee_head_id', $request->fee_head_id)
                                    ->where('fee_subhead_id', $feesubhead_id)
                                    ->first();
                if($check)
                {
                    continue;
                }
                $input = [ 
                    'institute_details_id' => Auth::user()->institute_details_id,
                    'fee_head_id' => $request->fee_head_id,
                    'fee_subhead_id' => $feesubhead_id,
                ];
        
                $data = FeeMapping::create($input);
        }
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
        // return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);        
    }
}
