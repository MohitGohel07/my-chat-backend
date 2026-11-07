<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        if (config('chat.auth_mode') === 'phone') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            ]);
            $user = User::create(['name' => $data['name'], 'phone' => $data['phone'], 'password' => Str::random(40)]);
            return response()->json(['user' => $user, 'message' => 'Account created. Request an OTP to sign in.'], 201);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create($data);

        return response()->json(['user' => $user, 'token' => $user->createToken('mobile')->plainTextToken], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $user->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['user' => $user, 'token' => $user->createToken('mobile')->plainTextToken]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:32']]);
        $code = (string) random_int(100000, 999999);
        Cache::put('chat_otp:'.$data['phone'], $code, now()->addMinutes(10));

        $response = ['message' => 'OTP sent.'];
        if (config('chat.otp_return_code')) {
            $response['dev_otp'] = $code;
        }

        return response()->json($response);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'otp' => ['required', 'digits:6'],
        ]);

        abort_unless(Cache::pull('chat_otp:'.$data['phone']) === $data['otp'], 422, 'Invalid or expired OTP.');
        $user = User::where('phone', $data['phone'])->first();
        abort_unless($user, 404, 'Phone number is not registered.');

        return response()->json(['user' => $user, 'token' => $user->createToken('mobile')->plainTextToken]);
    }
}
