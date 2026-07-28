<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active UI locale from (in order): the authenticated user's
 * saved preference, the session, then the app default. Only whitelisted
 * locales are honoured.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('app.supported_locales', ['en']);

        $locale = $request->user()?->locale
            ?? Session::get('locale')
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
