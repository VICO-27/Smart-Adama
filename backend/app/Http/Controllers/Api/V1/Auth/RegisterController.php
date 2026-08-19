<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Utils\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * POST /api/v1/auth/register
 * Requirement 1.1, 1.2
 */
class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $normalizedPhone = null;
        if ($request->phone_number) {
            $normalizedPhone = PhoneNormalizer::normalize($request->phone_number);
            
            if (!$normalizedPhone) {
                return response()->json([
                    'message' => 'The provided phone number is invalid.',
                    'errors' => ['phone_number' => ['The provided phone number is invalid.']]
                ], 422);
            }
            
            // Check if normalized phone is unique
            if (User::where('phone_number', $normalizedPhone)->exists()) {
                return response()->json([
                    'message' => 'The phone number has already been taken.',
                    'errors' => ['phone_number' => ['The phone number has already been taken.']]
                ], 422);
            }
        }

        // Use PIN if phone is provided, otherwise use password
        $credential = $request->phone_number ? $request->pin : $request->password;

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone_number' => $normalizedPhone,
            'password'     => Hash::make($credential),
            'role'         => 'learner',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
