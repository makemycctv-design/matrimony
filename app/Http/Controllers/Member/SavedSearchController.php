<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'criteria' => ['required', 'array'],
        ]);

        $profile = $request->user()->ensureProfile();

        $search = new SavedSearch([
            'name' => $validated['name'],
            'criteria' => $validated['criteria'],
        ]);
        $search->company_id = $profile->company_id;
        $search->member_profile_id = $profile->id;
        $search->save();

        return back()->with('success', 'Search saved.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->member_profile_id === $request->user()->ensureProfile()->id, 403);

        $savedSearch->delete();

        return back()->with('success', 'Saved search removed.');
    }
}
