<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PinRecoveryController extends Controller
{
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate(['phone_number' => 'required|string']);

        $normalizedPhone = PhoneNormalizer::normalize($request->phone_number);

        if (! $normalizedPhone) {
            return response()->json(['fallback' => 'help_center']);
        }

        $user = User::where('phone_number', $normalizedPhone)->first();

        if (! $user) {
            // Do not reveal if phone exists or not to prevent enumeration,
            // but for a UX requiring explicit fallback, we can return the fallback.
            return response()->json(['fallback' => 'help_center']);
        }

        if (! $user->email) {
            return response()->json(['fallback' => 'help_center']);
        }

        // Generate 6 digit code
        $code = (string) random_int(100000, 999999);

        // Store hashed code
        DB::table('pin_reset_tokens')->updateOrInsert(
            ['phone_number' => $normalizedPhone],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        // Send email
        Mail::raw("Your Smart Adama PIN reset code is: {$code}", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Smart Adama - PIN Reset Code');
        });

        return response()->json([
            'masked_email' => $user->masked_email,
            'message' => 'Code sent successfully.',
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code' => 'required|digits:6',
        ]);

        $normalizedPhone = PhoneNormalizer::normalize($request->phone_number);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'code' => ['The provided reset code is incorrect or expired.'],
            ]);
        }

        $record = DB::table('pin_reset_tokens')
            ->where('phone_number', $normalizedPhone)
            ->first();

        if (! $record || ! Hash::check($request->code, $record->token)) {
            throw ValidationException::withMessages([
                'code' => ['The provided reset code is incorrect or expired.'],
            ]);
        }

        // Generate a temporary signature for the final step
        $signature = Str::random(60);

        // Cache the signature for 15 minutes
        cache()->put('pin_reset_sig_'.$normalizedPhone, $signature, now()->addMinutes(15));

        return response()->json([
            'signature' => $signature,
        ]);
    }

    public function resetPin(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'signature' => 'required|string',
            'pin' => 'required|digits:6|confirmed|not_in:000000,111111,222222,333333,444444,555555,666666,777777,888888,999999,123456,654321,987654',
        ], [
            'pin.not_in' => 'This PIN is too common. Please choose a more secure PIN.',
        ]);

        $normalizedPhone = PhoneNormalizer::normalize($request->phone_number);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid or expired reset session.'],
            ]);
        }

        $cachedSignature = cache()->get('pin_reset_sig_'.$normalizedPhone);

        if (! $cachedSignature || $cachedSignature !== $request->signature) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid or expired reset session.'],
            ]);
        }

        $user = User::where('phone_number', $normalizedPhone)->firstOrFail();

        $user->password = Hash::make($request->pin);
        $user->save();

        // Clear tokens and signature
        DB::table('pin_reset_tokens')->where('phone_number', $normalizedPhone)->delete();
        cache()->forget('pin_reset_sig_'.$normalizedPhone);

        // Clear all current user sessions/tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'PIN reset successfully.']);
    }
}
