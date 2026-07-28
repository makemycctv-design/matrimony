<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CmsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        return Inertia::render('admin/cms/index', [
            'pages' => CmsPage::query()->orderBy('title')->get()->map(fn (CmsPage $p) => [
                'uuid' => $p->uuid,
                'slug' => $p->slug,
                'title' => $p->title,
                'body' => $p->body,
                'meta_title' => $p->meta_title,
                'meta_description' => $p->meta_description,
                'is_published' => $p->is_published,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $data = $this->validated($request);

        $page = new CmsPage($data);
        $page->slug = Str::slug($data['slug'] ?: $data['title']);
        $page->updated_by = $request->user()->id;
        $page->published_at = $data['is_published'] ? Carbon::now() : null;
        $page->save();

        $this->audit->log('cms.created', $page, "CMS page {$page->slug} created");

        return back()->with('success', 'Page created.');
    }

    public function update(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $data = $this->validated($request, $cmsPage);

        $cmsPage->fill($data);
        $cmsPage->updated_by = $request->user()->id;
        if ($data['is_published'] && ! $cmsPage->published_at) {
            $cmsPage->published_at = Carbon::now();
        }
        $cmsPage->save();

        $this->audit->log('cms.updated', $cmsPage, "CMS page {$cmsPage->slug} updated");

        return back()->with('success', 'Page updated.');
    }

    public function destroy(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $cmsPage->delete();

        return back()->with('success', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CmsPage $page = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:100', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['boolean'],
        ]);
    }
}
