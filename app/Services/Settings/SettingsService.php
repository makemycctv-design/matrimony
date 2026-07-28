<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Cache;

/**
 * Typed access to the key/value settings store, grouped for the admin UI.
 * Secret values are masked when read for display and only overwritten when a
 * new value is actually provided.
 */
class SettingsService
{
    public function __construct(private readonly TenantManager $tenants) {}

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()
            ->where('company_id', $this->tenants->id())
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting?->typedValue() ?? $default;
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string', bool $isSecret = false): void
    {
        Setting::updateOrCreate(
            ['company_id' => $this->tenants->id(), 'group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type, 'is_secret' => $isSecret],
        );

        Cache::forget($this->cacheKey());
    }

    /**
     * All settings grouped for the admin screen, with secrets masked.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function grouped(): array
    {
        return Setting::query()
            ->where('company_id', $this->tenants->id())
            ->orderBy('group')
            ->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->mapWithKeys(fn (Setting $s) => [
                $s->key => [
                    'value' => $s->is_secret ? ($s->value ? '••••••••' : '') : $s->typedValue(),
                    'type' => $s->type,
                    'is_secret' => $s->is_secret,
                ],
            ]))
            ->toArray();
    }

    /**
     * Persist a batch of updates. Secret values equal to the mask are skipped
     * so an unchanged secret is never overwritten with the placeholder.
     *
     * @param  array<string, array<string, mixed>>  $groups
     */
    public function updateMany(array $groups): void
    {
        foreach ($groups as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                $existing = Setting::query()
                    ->where('company_id', $this->tenants->id())
                    ->where('group', $group)->where('key', $key)->first();

                if ($existing?->is_secret && $value === '••••••••') {
                    continue; // masked, unchanged
                }

                $type = $existing?->type ?? (is_bool($value) ? 'boolean' : 'string');
                $this->set($group, $key, is_bool($value) ? ($value ? '1' : '0') : $value, $type, (bool) $existing?->is_secret);
            }
        }
    }

    private function cacheKey(): string
    {
        return 'settings.'.($this->tenants->id() ?? 'global');
    }
}
