<?php

namespace App\Http\Controllers\auth;

use App\Helpers\ApiResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AdminResource;
use App\Utils\ServerErrorMask;
use Hamcrest\Core\Set;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWT;

class BackOfficeAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        // Validate request
        $rules = [
            'emailOrMobile' => 'required|string',
            'password' => 'required|string',
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
            $user = User::where('email', $request->emailOrMobile)
                ->orWhere('mobile', $request->emailOrMobile)
                ->first();

            if ($user) {
                $credentials = $user->email
                    ? ['email' => $user->email, 'password' => $request->password]
                    : ['mobile' => $user->mobile, 'password' => $request->password];

                // Attempt authentication
                $authToken = Auth::guard('admin-api')->attempt($credentials);

                if ($authToken) {
                    $refresh_token = JWTAuth::fromUser($user);
                    return response()->json([
                        'admin' => new AdminResource($user),
                        'authorization' => [
                            'access_token' => $authToken,
                            'refresh_token' => $refresh_token,
                            'token_type' => 'Bearer',
                            'expires_in' => JWTAuth::factory()->getTTL(),
                        ]
                    ]);
                }
            }

            // Invalid credentials
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Invalid Credentials!']);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error("Admin login error: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
    }
   
    public function logout()
    {
        Auth::guard('admin-api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        try {
            $user = auth('admin-api')->user();
            $newToken = JWTAuth::fromUser($user);
            return response()->json(['status' => 'success',
                'user' => new AdminResource($user),
                'authorization' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL(),
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], 401); // Handle errors
        }
    }


    public function authUser()
    {
        $user = Auth::guard('admin-api')->user();
        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user),
        ]);
    }
}
