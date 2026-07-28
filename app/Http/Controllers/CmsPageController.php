<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    /** Render a published CMS page (privacy, terms, faq, …). */
    public function show(string $slug): Response
    {
        $page = CmsPage::query()->published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('cms-page', [
            'page' => [
                'title' => $page->title,
                'body' => $page->body,
            ],
            'seo' => [
                'title' => $page->meta_title ?: $page->title,
                'description' => $page->meta_description,
            ],
        ]);
    }
}
