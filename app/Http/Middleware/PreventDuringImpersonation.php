<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDuringImpersonation
{
    /**
     * Block actions that must stay with the real owner of the account, like changing the password.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has(ImpersonationController::SESSION_KEY)) {
            return back()->with('error', __('Im Testmodus nicht möglich.'));
        }

        return $next($request);
    }
}
