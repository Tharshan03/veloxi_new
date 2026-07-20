<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestaurantLoginRequest;
use App\Http\Requests\RestaurantRegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantAuthController extends Controller
{
    public function showLogin()
    {
        return view('merchant.auth.login');
    }

    public function login(RestaurantLoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors(['email' => __('auth.failed')])->withInput($request->only('email'));
        }

        $user = Auth::user();

        if ((int) $user->status !== 1 || $user->user_type !== 'client') {
            Auth::logout();

            return back()->withErrors(['email' => 'Ce compte ne peut pas commander sur ce restaurant.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('merchant.checkout.show'));
    }

    public function showRegister()
    {
        return view('merchant.auth.register');
    }

    public function register(RestaurantRegisterRequest $request)
    {
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

        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web'], ['status' => 1]);
        $user->assignRole('client');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('merchant.checkout.show'));
    }

    private function generateUniqueUsername(string $email): string
    {
        $baseUsername = Str::slug(Str::before($email, '@'), '_') ?: 'client';
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter;
            $counter++;
        }

        return $username;
    }
}
