<?php

namespace App\Services\Profile;

use App\Models\Caste;
use App\Models\Education;
use App\Models\Location;
use App\Models\MotherTongue;
use App\Models\Profession;
use App\Models\Religion;
use App\Models\SubCaste;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Supplies localized option lists for the profile wizard and search filters.
 * Cached per-locale because master data changes rarely.
 */
class ProfileOptionsService
{
    public function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return Cache::remember("profile.options.{$locale}", now()->addHour(), function () use ($locale): array {
            return [
                'religions' => Religion::active()->ordered()->get()
                    ->map(fn (Religion $r) => ['id' => $r->id, 'name' => $r->localizedName($locale)])->values(),
                'castes' => Caste::active()->ordered()->get()
                    ->map(fn (Caste $c) => ['id' => $c->id, 'religion_id' => $c->religion_id, 'name' => $c->localizedName($locale)])->values(),
                'sub_castes' => SubCaste::active()->ordered()->get()
                    ->map(fn (SubCaste $s) => ['id' => $s->id, 'caste_id' => $s->caste_id, 'name' => $s->localizedName($locale)])->values(),
                'mother_tongues' => MotherTongue::active()->ordered()->get()
                    ->map(fn (MotherTongue $m) => ['id' => $m->id, 'name' => $m->localizedName($locale)])->values(),
                'educations' => Education::active()->ordered()->get()
                    ->map(fn (Education $e) => ['id' => $e->id, 'name' => $e->localizedName($locale), 'category' => $e->category])->values(),
                'professions' => Profession::active()->ordered()->get()
                    ->map(fn (Profession $p) => ['id' => $p->id, 'name' => $p->localizedName($locale), 'category' => $p->category])->values(),
                'countries' => $this->locations('country', $locale),
                'states' => $this->locations('state', $locale),
                'districts' => $this->locations('district', $locale),
                'cities' => $this->locations('city', $locale),
            ];
        });
    }

    private function locations(string $type, string $locale): Collection
    {
        return Location::active()->ofType($type)->orderBy('name')->get()
            ->map(fn (Location $l) => [
                'id' => $l->id,
                'parent_id' => $l->parent_id,
                'name' => $l->localizedName($locale),
            ])->values();
    }

    public static function flush(): void
    {
        foreach ((array) config('app.supported_locales', ['en']) as $locale) {
            Cache::forget("profile.options.{$locale}");
        }
    }
}
