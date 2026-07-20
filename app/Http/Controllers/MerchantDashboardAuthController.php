<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantDashboardAuthController extends Controller
{
    public function showLogin()
    {
        return view('merchant.dashboard.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => __('auth.failed')])->withInput($request->only('email'));
        }

        $user = Auth::user();

        if ((int) $user->status !== 1 || !$user->hasRole('merchant')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Ce compte ne peut pas accéder à l’espace commerçant.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('merchant.orders.index'));
    }
}
