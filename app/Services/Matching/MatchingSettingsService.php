<?php

namespace App\Services\Matching;

use App\Models\Setting;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Cache;

/**
 * Reads/writes the global, admin-configurable compatibility weights. Weights
 * are stored in the settings table (group "matching") and merged over a
 * canonical default set so new factors always have a sensible value.
 */
class MatchingSettingsService
{
    /**
     * Canonical factors and their default weights (relative importance).
     *
     * @var array<string, int>
     */
    public const DEFAULT_WEIGHTS = [
        'age' => 14,
        'religion' => 12,
        'caste' => 8,
        'mother_tongue' => 10,
        'location' => 10,
        'education' => 8,
        'profession' => 5,
        'marital_status' => 6,
        'height' => 5,
        'lifestyle' => 4,
        'horoscope' => 3,
        'completeness' => 6,
        'verification' => 5,
        'mutual' => 4,
    ];

    public function __construct(private readonly TenantManager $tenants) {}

    /**
     * @return array<string, int>
     */
    public function weights(): array
    {
        return Cache::remember($this->cacheKey(), now()->addHour(), function (): array {
            $stored = Setting::query()
                ->where('group', 'matching')
                ->where('company_id', $this->tenants->id())
                ->where('key', 'like', 'weight_%')
                ->get()
                ->mapWithKeys(fn (Setting $s) => [str_replace('weight_', '', $s->key) => (int) $s->value])
                ->only(array_keys(self::DEFAULT_WEIGHTS))
                ->all();

            // Stored overrides win; any missing factor falls back to its default.
            return array_merge(self::DEFAULT_WEIGHTS, $stored);
        });
    }

    /**
     * @param  array<string, int>  $weights
     */
    public function update(array $weights): void
    {
        foreach ($weights as $factor => $value) {
            if (! array_key_exists($factor, self::DEFAULT_WEIGHTS)) {
                continue;
            }

            Setting::updateOrCreate(
                ['company_id' => $this->tenants->id(), 'group' => 'matching', 'key' => 'weight_'.$factor],
                ['value' => (string) max(0, min(100, (int) $value)), 'type' => 'integer', 'is_secret' => false],
            );
        }

        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return 'matching.weights.'.($this->tenants->id() ?? 'global');
    }
}
