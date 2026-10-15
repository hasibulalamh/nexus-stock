<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\LandingService;

class LoginController extends Controller
{
    public function view()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput($request->only('email'));
        }

        // Throttle key: same email + same IP
        $key = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return redirect()->back()
                ->withErrors(['email' => "Too many login attempts. Try again in {$seconds} seconds."])
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');

        // Restricted account: refuse with the same message whether or not the
        // password was correct, so the reply reveals nothing about it.
        $existing = User::where('email', $request->email)->first();
        if ($existing && $existing->status !== 'active') {
            RateLimiter::hit($key, 60);

            return redirect()->route('login')
                ->withErrors(['email' => EnsureUserIsActive::MESSAGE])
                ->withInput($request->only('email'));
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return redirect()->route('login')
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->withInput($request->only('email'));
        }

        $user = Auth::user();

        // Defence in depth: the pre-check above already refuses restricted
        // accounts before the password is ever verified.
        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => EnsureUserIsActive::MESSAGE])
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate(); // prevents session fixation

        return app(LandingService::class)->redirect($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
