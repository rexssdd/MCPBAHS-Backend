<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Models\User;
use App\Models\UserPasswordOtp;
use App\Notifications\UserPasswordOtpNotification;
use Illuminate\Support\Facades\Hash;

class UserPasswordController extends Controller
{
    // TODO: clean-up controller methods
    public function requestPasswordOtp(User $user)
    {
        $otp = random_int(100000, 999999);

        // FIX: was storing the raw integer — anyone with DB read access could see
        // every pending OTP. Hash it the same way passwords are hashed.
        UserPasswordOtp::create([
            'user_id'    => $user->id,
            'otp'        => Hash::make((string) $otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new UserPasswordOtpNotification($otp));

        return response()->json([
            'message' => 'OTP sent to your email. Please check your inbox.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, User $user)
    {
        $otpRecord = UserPasswordOtp::query()
            ->where('user_id', $user->id)
            ->whereRaw('used = false')
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'message' => 'No OTP request found. Please request a new OTP.',
            ], 404);
        }

        // FIX: was Hash::check($request->otp, $otpRecord->otp) against a plain
        // integer, which always returned false. Now the OTP is hashed on save so
        // Hash::check works correctly.
        if ($otpRecord->expires_at->isPast() || ! Hash::check((string) $request->otp, $otpRecord->otp)) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $otpRecord->update(['used' => true]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
