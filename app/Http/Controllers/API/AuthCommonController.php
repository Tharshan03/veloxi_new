<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthCommonController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'nullable|string|same:password',
            'contact_number' => 'nullable|string|max:20|unique:users,contact_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $this->generateUniqueUsername($request->email),
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
            'user_type' => 'client',
            'status' => 1,
            'referral_code' => generateRandomCode(),
        ]);

        $user->assignRole('client');

        return $this->respondWithToken($user, 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $user = Auth::user();

        if ((int) $user->status !== 1) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 403);
        }

        $user->last_actived_at = now();
        $user->save();

        return $this->respondWithToken($user);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout successfully',
        ]);
    }

    private function respondWithToken(User $user, int $status = 200)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
                'api_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }

    private function generateUniqueUsername(string $email): string
    {
        $baseUsername = Str::before($email, '@');
        $baseUsername = Str::slug($baseUsername, '_') ?: 'client';
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter;
            $counter++;
        }

        return $username;
    }
}
