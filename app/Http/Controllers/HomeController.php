<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public, SEO-friendly landing page. Live headline stats are cached to
     * keep the marketing page fast under load.
     */
    public function index(): Response
    {
        $stats = Cache::remember('landing.stats', now()->addMinutes(15), function (): array {
            return [
                'members' => User::query()->count(),
                'verified' => MemberProfile::query()->where('is_verified', true)->count(),
            ];
        });

        return Inertia::render('welcome', [
            'stats' => $stats,
            'seo' => [
                'title' => config('app.name').' — '.__('app.tagline'),
                'description' => __('app.description'),
            ],
        ]);
    }
}
