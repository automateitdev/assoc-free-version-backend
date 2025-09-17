<?php

namespace App\Http\Controllers\operationPortal\wallet;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\InstituteDetail;
use App\Models\WalletMapping;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $wallets = Wallet::paginate($request->per_page);

        return response()->json($wallets);
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
            'wallet_no' => 'required',
            'bank_name' => 'required',
            'bank_account_name' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $wallet = new Wallet();
        $wallet->institute_id = $request->institute_id;
        $wallet->wallet_no  = $request->wallet_no;
        $wallet->merchant_code = $request->merchant_code;
        $wallet->bank_name = $request->payment_partner_name;
        $wallet->bank_account = $request->bank_account;
        // $wallet->pin_code = $request->pin_code;
        $wallet->portal = $request->portal;
        $wallet->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Data Upload Successfully',
            'data' => $wallet,
        ]);
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

        $wallet = Wallet::find($id);

        if (!$wallet) {
            return response()->json([
                'error' => 'Wallet not found',
            ], 404);
        }
        // Update wallet with new data
        if(!empty($request->wallet_no))
        {
            $wallet->wallet_no  = $request->wallet_no;
        }
        if (!empty($request->merchant_code)) {
            $wallet->merchant_code = $request->merchant_code;
        }
    
        if (!empty($request->payment_partner_name)) {
            $wallet->payment_partner_name = $request->payment_partner_name;
        }
    
        if (!empty($request->bank_account)) {
            $wallet->bank_account = $request->bank_account;
        }
    
        // if (!empty($request->pin_code)) {
        //     $wallet->pin_code = $request->pin_code;
        // }
    
        if (!empty($request->portal)) {
            $wallet->portal = $request->portal;
        }

        if (!empty($request->status)) {
            $wallet->status = $request->status;
        }
        $wallet->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet updated successfully',
            'data' => $wallet,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function walletMapping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_no' => 'required',
            // 'payment_code' => 'required|unique:wallet_mappings,payment_code',
            'payment_code' => 'required',
            'portal' => 'required'
        ]);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }


        $isPaymentCodeActive = WalletMapping::where('payment_code', $request->payment_code)->where('status', 'active')->exists();

        if ($isPaymentCodeActive) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['payment_code' => 'This payment code already active!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }
        try {
            $wallet =  Wallet::where('wallet_no', $request->wallet_no)
                ->with(['walletMappings' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->firstOrFail();

            $walletMapExist = WalletMapping::where('payment_code', strtolower($request->payment_code))->exists();

            if ($walletMapExist) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['payment_code' => "Requested payment code already mapped!"]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }


            $walletMap = new walletMapping();
            $walletMap->wallet_id = $wallet->id;
            $walletMap->payment_code = strtolower($request->payment_code);
            $walletMap->portal = $request->portal;
            $walletMap->save();

            return response()->json(['status' => 'success', 'message' => "New map added for the wallet $wallet->wallet_no"]);
        } catch (ModelNotFoundException $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet_no' => "Wallet not found"]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Internal Server Error!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function addNewWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_no' => 'required|unique:wallets,wallet_no',
            'bank_account_no' => 'required',
            'bank_account_name' => 'required'
        ]);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {


            if (empty($request->institute_detail)) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet' => "Invalid Request!!"]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            $instdetail = InstituteDetail::find($request->institute_detail);

            if (empty($instdetail)) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet' => "Invalid Request!!"]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 400);
            }

            $wallet = new Wallet();
            $wallet->wallet_no = $request->wallet_no;
            $wallet->institute_details_id = $instdetail->id;
            $wallet->bank_account_no = $request->bank_account_no;
            $wallet->bank_account_name = $request->bank_account_name;
            $wallet->save();

            return response()->json(['status' => 'success', 'message' => "New wallet added: $wallet->wallet_no"]);
        } catch (ModelNotFoundException $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet_no' => "Wallet not found"]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Internal Server Error!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }


    // walletStatusToggle


    public function walletStatusToggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required',
            'wallet_no' => 'required',
        ]);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {

            $wallet = Wallet::where('id', $request->wallet_id)->where('wallet_no', $request->wallet_no)->firstOrFail();


            if ($wallet->status == 'inactive') {

                $activeWallet = wallet::where('institute_details_id', $wallet->institute_details_id)->where('status', 'active')->first();

                if ($activeWallet) {
                    $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet_no' => "$activeWallet->wallet_no is active. Deactivate it, to activate new one."]);
                    return response()->json([
                        'errors' => $formattedErrors,
                        'payload' => null,
                    ], 400);
                }
            }

            if ($wallet->status == 'active') {
                $wallet->status = 'inactive';
                $wallet->save();
            } else {
                $wallet->status = 'active';
                $wallet->save();
            }

            return response()->json(['status' => 'success', 'message' => "Wallet: $wallet->wallet_no is $wallet->status"]);
        } catch (ModelNotFoundException $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet_no' => "Wallet not found"]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Internal Server Error!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function walletMapStatusToggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'portal' => 'required',
            'wallet_id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {

            $RequestedWalletDetail = WalletMapping::where('id', $request->id)->where('wallet_id', $request->wallet_id)->firstOrFail();

            if ($request->status == 'active') {
                $walletMap = WalletMapping::where('portal', $request->portal)->where('wallet_id', $request->wallet_id)->where('status', 'active')->first();
                if ($walletMap) {
                    $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ["portal" => "Portal: $walletMap->portal is already active under payment code: $walletMap->payment_code"]);
                    return response()->json([
                        'errors' => $formattedErrors,
                        'payload' => null,
                    ], 400);
                }
                $RequestedWalletDetail->status = 'active';
                $RequestedWalletDetail->save();
            } else {
                $RequestedWalletDetail->status = 'inactive';
                $RequestedWalletDetail->save();
            }

            return response()->json(['status' => 'success', 'message' => "Payment Code: $RequestedWalletDetail->payment_code is now $RequestedWalletDetail->status"]);
        } catch (ModelNotFoundException $e) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['wallet_no' => "Wallet not found"]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Internal Server Error!']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    
}
