<?php

namespace App\Http\Controllers\auth;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use OpenApi\Annotations as OA;
use App\Models\InstituteProfile;
use App\Helpers\ApiResponseHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Resources\MerchantLoginResource;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Automate V2 API",
 *         version="1.0",
 *         description="Your API Description",
 *         @OA\Contact(
 *             email="contact@example.com"
 *         ),
 *         @OA\License(
 *             name="Your License",
 *             url="http://your-license-url.com"
 *         )
 *     ),
 *     servers={
 *         {
 *             "url": L5_SWAGGER_CONST_HOST,
 *             "description": "Production Server"
 *         }
 *     },
 *     @OA\Tag(
 *         name="Authentication",
 *         description="Endpoints for user authentication"
 *     )
 * )
 */
class MerchantAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }
    /**
     * @OA\Post(
     *     path="/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Successful login"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function login(Request $request)
    {
        try {

            $rules = [
                // 'emailOrMobile' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);


            if ($validator->fails()) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
                return response()->json(
                    [
                        'errors' => $formattedErrors,
                        'payload' => null,
                    ],
                    422
                );
            }

            // $user = Merchant::where('email', $request->emailOrMobile)->orWhere('mobile', $request->emailOrMobile)->first();
            $user = InstituteProfile::where('username', $request->username)->first();

            if (!$user) {
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Invalid Credentials!']);
                return response()->json(
                    [
                        'errors' => $systemError,
                        'payload' => null,
                    ],
                    400
                );
            }
            // $credentials = $request->only('email', 'password');
            $credentials = $user->email ? ['email' => $user->email, 'password' => $request->password] : ['mobile' => $user->mobile, 'password' => $request->password];

            $token = Auth::guard('api')->attempt($credentials);

            $user = Auth::guard('api')->user();

            if ($token) {
                $refresh_token = JWTAuth::fromUser($user, ['exp' => 60 * 24 * 30]);
                return response()->json([
                    'admin' => new MerchantLoginResource($user),
                    'authorization' => [
                        'access_token' => $token,
                        'refresh_token' => $refresh_token,
                        'token_type' => 'Bearer',
                        'expires_in' => JWTAuth::factory()->getTTL(),
                    ]
                ]);
            }
            if (!$token) {
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Invalid Credentials!']);
                return response()->json(
                    [
                        'errors' => $systemError,
                        'payload' => null,
                    ],
                    400
                );
            }
        } catch (\Exception $e) {
            Log::error("Merchant Login Failed: " . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::SERVER_ERROR);
            return response()->json([
                'errors' => $systemError,
                'payload' => null,
            ], 500);
        }
        

    }
  /**
     * @OA\Post(
     *     path="/logout",
     *     summary="User logout",
     *     tags={"Authentication"},
     *     @OA\Response(response=200, description="Successfully logged out"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }
    /**
     * @OA\Post(
     *     path="/refresh",
     *     summary="Refresh authentication token",
     *     tags={"Authentication"},
     *     @OA\Response(response=200, description="Token refreshed successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    
    public function refresh()
    {
        try {
            $user = auth('api')->user();
            $newToken = JWTAuth::fromUser($user);
            return response()->json([
                'status' => 'success',
                'user' => new MerchantLoginResource($user),
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
        $user = Auth::guard('api')->user();
        return response()->json([
            'status' => 'success',
            'user' => new MerchantLoginResource($user),
        ]);
    }
}
