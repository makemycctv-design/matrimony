<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Persist the chosen UI locale to the session and, if authenticated, the
     * user's profile. SetLocale middleware applies it on subsequent requests.
     */
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales', ['en']))],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $validated['locale']])->save();
        }

        return back();
    }
}
