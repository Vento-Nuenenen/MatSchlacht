<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Use the language of the account, otherwise the one the browser asks for.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<string, string> $availableLocales */
        $availableLocales = config('app.available_locales');

        App::setLocale(
            $request->user()?->preferredLocale()
                ?? $request->getPreferredLanguage(array_keys($availableLocales))
                ?? config('app.locale')
        );

        return $next($request);
    }
}
