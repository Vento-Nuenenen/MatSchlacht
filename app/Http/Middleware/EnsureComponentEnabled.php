<?php

namespace App\Http\Middleware;

use App\Services\ComponentManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureComponentEnabled
{
    public function __construct(protected ComponentManager $components) {}

    /**
     * Respond with 404 when the given app component is disabled.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $component): Response
    {
        abort_unless($this->components->isEnabled($component), 404);

        return $next($request);
    }
}
