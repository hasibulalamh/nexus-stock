<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Generic refusal shown for restricted accounts. Shared with
     * LoginController so login and session enforcement read the same text and
     * nothing about password correctness is ever revealed.
     */
    public const MESSAGE = 'This account is restricted. Contact an administrator.';

    /**
     * Sign a restricted user out before the request reaches the admin area.
     * Login/logout routes are outside the authenticated admin group, so this
     * cannot loop.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
