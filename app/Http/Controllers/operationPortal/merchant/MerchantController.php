<?php

namespace App\Http\Controllers\operationPortal\merchant;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\InstituteDetail;
use App\Helpers\ApiResponseHelper;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $merchants = Merchant::with('institute_detail.wallets.walletMappings')->paginate($request->per_page);

        return response()->json($merchants);
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
            // 'wallet_no' => 'required|unique:wallets,wallet_no',
            'wallet_no' => 'required',
            'bank_account_name' => 'required',
            'bank_name' => 'nullable',
            // 'institute_id' => 'required',
            'institute_name' => 'required',
            // 'email' => 'nullable|unique:merchants,email',
            'email' => 'nullable',
            // 'mobile' => 'required|unique:merchants,mobile',
            'mobile' => 'required',
            'address' => 'required',
            'upozilla' => 'required',
            'district' => 'required',
            'username' => 'nullable',
            'password' => 'required',
            // 'merchant_code' => 'required',
            // 'pin_code' => 'required',
            // 'portal' => 'required',
            // 'role' => 'required'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // $roleName = $request->get('role');

        // // Retrieve the role by name and guard
        // $role = Role::where('name', $roleName)->where('guard_name', 'admin-api')->first();
        $latest_institute = InstituteDetail::latest()->first();
        try {
            DB::beginTransaction();
            $institute_details = new InstituteDetail();
            $institute_details->institute_id = $latest_institute ? (int)$latest_institute->institute_id + 1 : 10001;
            $institute_details->institute_name = $request->institute_name;
            $institute_details->institute_contact = $request->mobile;
            $institute_details->institute_email = $request->email;
            $institute_details->institute_address = $request->address;
            $institute_details->institute_district = $request->district;
            $institute_details->institute_upozilla = $request->upozilla;
            $institute_details->save();

            $wallet = new Wallet();
            $wallet->institute_details_id = $institute_details->id;
            $wallet->wallet_no  = $request->wallet_no;
            $wallet->bank_account_name = $request->bank_account_name;
            $wallet->bank_account_no = $request->bank_account;
            $wallet->save();

            // Create Merchant
            $input = new Merchant();
            $input->institute_details_id = $institute_details->id;
            $input->username = strtolower($request->username);
            $input->email = $request->email;
            $input->mobile = $request->mobile;
            $input->name = $request->institute_name;
            $input->user_type = $request->role ?? 'admin';
            $input->password = Hash::make($request->password);
            $input->save();
            // $input->syncRoles([$role]);
            $input->syncRoles($request->get('role') ?? 'admin');

            DB::commit();

            return response()->json(['status' => 'success',
                'message' => 'Data Upload Successfully',
                'wallet' => $wallet,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            Log::error("Merchant create error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::INVALID_REQUEST, ['Not Found']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Merchant create error: " . $e->getMessage());
            DB::rollBack();
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Merchant create error: " . $e->getMessage());
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
