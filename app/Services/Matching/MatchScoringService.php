<?php

namespace App\Services\Matching;

use App\Models\MemberProfile;
use App\Models\PartnerPreference;

/**
 * Computes an explainable compatibility score (0-100) between a seeker and a
 * candidate profile, along with human-readable reasons.
 *
 * Design principles:
 *  - Configurable: global weights (admin) blended with per-member overrides.
 *  - Explainable: every awarded factor yields a plain-language reason.
 *  - Honest: only factors with data to compare are counted, and community /
 *    horoscope factors count ONLY when the member has opted in. The score is
 *    guidance and never a guarantee of marital compatibility.
 */
class MatchScoringService
{
    public function __construct(private readonly MatchingSettingsService $settings) {}

    /**
     * @return array{score:int, reasons:list<string>}
     */
    public function score(MemberProfile $seeker, MemberProfile $candidate): array
    {
        $weights = $this->effectiveWeights($seeker);
        $pref = $seeker->partnerPreference;

        $applicable = 0;
        $earned = 0;
        $reasons = [];

        foreach ($this->factors($seeker, $candidate, $pref) as $factor => [$isApplicable, $isMatch, $reason, $partial]) {
            $weight = $weights[$factor] ?? 0;

            if (! $isApplicable || $weight <= 0) {
                continue;
            }

            $applicable += $weight;
            $gain = $partial ?? ($isMatch ? 1.0 : 0.0);
            $earned += $weight * $gain;

            if ($isMatch && $reason !== null) {
                $reasons[] = $reason;
            }
        }

        $score = $applicable > 0 ? (int) round(($earned / $applicable) * 100) : 0;

        return ['score' => max(0, min(100, $score)), 'reasons' => array_slice($reasons, 0, 8)];
    }

    /**
     * Merge global weights with the seeker's per-factor importance overrides.
     *
     * @return array<string, int>
     */
    private function effectiveWeights(MemberProfile $seeker): array
    {
        $weights = $this->settings->weights();
        $overrides = $seeker->partnerPreference?->weight_overrides ?? [];

        foreach ($overrides as $factor => $value) {
            if (array_key_exists($factor, $weights) && is_numeric($value)) {
                $weights[$factor] = max(0, min(100, (int) $value));
            }
        }

        return $weights;
    }

    /**
     * Each factor => [applicable(bool), match(bool), reason(?string), partial(?float)].
     *
     * @return array<string, array{0:bool,1:bool,2:?string,3:?float}>
     */
    private function factors(MemberProfile $seeker, MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        return [
            'age' => $this->ageFactor($candidate, $pref),
            'religion' => $this->listOrOwnFactor($pref?->religion_ids, $candidate->religion_id, $seeker->religion_id, 'Preferred religion', 'Same religion'),
            'caste' => $this->optInListFactor($pref?->caste_ids, $candidate->caste_id, 'Preferred community'),
            'mother_tongue' => $this->listOrOwnFactor($pref?->mother_tongue_ids, $candidate->mother_tongue_id, $seeker->mother_tongue_id, 'Preferred mother tongue', 'Same mother tongue'),
            'location' => $this->locationFactor($seeker, $candidate, $pref),
            'education' => $this->listOrOwnFactor($pref?->education_ids, $candidate->education_id, null, 'Matching education', null),
            'profession' => $this->listOrOwnFactor($pref?->profession_ids, $candidate->profession_id, null, 'Matching profession', null),
            'marital_status' => $this->maritalFactor($candidate, $pref),
            'height' => $this->heightFactor($candidate, $pref),
            'lifestyle' => $this->lifestyleFactor($seeker, $candidate, $pref),
            'horoscope' => $this->horoscopeFactor($seeker, $candidate, $pref),
            'completeness' => $this->completenessFactor($candidate),
            'verification' => $this->verificationFactor($candidate),
            'mutual' => $this->mutualFactor($seeker, $candidate),
        ];
    }

    private function ageFactor(MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        $age = $candidate->age;

        if ($pref === null || ($pref->age_min === null && $pref->age_max === null) || $age === null) {
            return [false, false, null, null];
        }

        $min = $pref->age_min ?? 0;
        $max = $pref->age_max ?? 200;
        $match = $age >= $min && $age <= $max;

        return [true, $match, 'Matches your age preference', null];
    }

    /**
     * Match against a preferred id list when the member set one; otherwise fall
     * back to "same as me". The $ownReason is used for the fallback path.
     */
    private function listOrOwnFactor(?array $prefIds, ?int $candidateId, ?int $ownId, string $prefReason, ?string $ownReason): array
    {
        if (! empty($prefIds)) {
            if ($candidateId === null) {
                return [true, false, null, null];
            }

            return [true, in_array($candidateId, $prefIds, true), $prefReason, null];
        }

        if ($ownReason !== null && $ownId !== null && $candidateId !== null) {
            return [true, $ownId === $candidateId, $ownReason, null];
        }

        return [false, false, null, null];
    }

    /** Community factor that ONLY counts when the member opted in by setting it. */
    private function optInListFactor(?array $prefIds, ?int $candidateId, string $reason): array
    {
        if (empty($prefIds)) {
            return [false, false, null, null]; // Not enabled — excluded from scoring.
        }

        return [true, $candidateId !== null && in_array($candidateId, $prefIds, true), $reason, null];
    }

    private function locationFactor(MemberProfile $seeker, MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        $prefCities = $pref?->city_ids ?? [];
        $prefStates = $pref?->state_ids ?? [];

        if (! empty($prefCities) || ! empty($prefStates)) {
            $match = ($candidate->city_id && in_array($candidate->city_id, $prefCities, true))
                || ($candidate->state_id && in_array($candidate->state_id, $prefStates, true));

            return [true, $match, 'Preferred location', null];
        }

        if ($seeker->city_id && $candidate->city_id) {
            if ($seeker->city_id === $candidate->city_id) {
                return [true, true, 'Same city', null];
            }
            if ($seeker->state_id && $seeker->state_id === $candidate->state_id) {
                return [true, true, 'Same state', 0.6];
            }

            return [true, false, null, null];
        }

        return [false, false, null, null];
    }

    private function maritalFactor(MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        $wanted = $pref?->marital_statuses ?? [];

        if (empty($wanted)) {
            return [false, false, null, null];
        }

        return [true, in_array($candidate->marital_status, $wanted, true), 'Preferred marital status', null];
    }

    private function heightFactor(MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        if ($pref === null || ($pref->height_min_cm === null && $pref->height_max_cm === null) || $candidate->height_cm === null) {
            return [false, false, null, null];
        }

        $match = $candidate->height_cm >= ($pref->height_min_cm ?? 0)
            && $candidate->height_cm <= ($pref->height_max_cm ?? 300);

        return [true, $match, 'Within your height preference', null];
    }

    private function lifestyleFactor(MemberProfile $seeker, MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        $diets = $pref?->diets ?? [];

        if (! empty($diets)) {
            return [true, in_array($candidate->diet, $diets, true), 'Compatible lifestyle', null];
        }

        if ($seeker->diet && $candidate->diet) {
            return [true, $seeker->diet === $candidate->diet, 'Similar food habits', null];
        }

        return [false, false, null, null];
    }

    private function horoscopeFactor(MemberProfile $seeker, MemberProfile $candidate, ?PartnerPreference $pref): array
    {
        // Only considered when the member explicitly requires horoscope matching.
        if (! ($pref?->horoscope_match_required) || ! $seeker->horoscope_enabled) {
            return [false, false, null, null];
        }

        if (! $candidate->horoscope_enabled) {
            return [true, false, null, null];
        }

        // A conservative, non-authoritative dosham compatibility check.
        $compatible = $seeker->dosham === $candidate->dosham
            || in_array('none', [$seeker->dosham, $candidate->dosham], true) === false;

        return [true, $compatible, 'Horoscope appears compatible', null];
    }

    private function completenessFactor(MemberProfile $candidate): array
    {
        $pct = $candidate->completion_percentage / 100;

        return [true, $candidate->completion_percentage >= 80, $candidate->completion_percentage >= 80 ? 'Detailed profile' : null, $pct];
    }

    private function verificationFactor(MemberProfile $candidate): array
    {
        return [true, $candidate->is_verified, $candidate->is_verified ? 'Verified profile' : null, null];
    }

    /** Does the candidate's own preference also welcome the seeker? */
    private function mutualFactor(MemberProfile $seeker, MemberProfile $candidate): array
    {
        $pref = $candidate->relationLoaded('partnerPreference')
            ? $candidate->partnerPreference
            : $candidate->partnerPreference()->first();

        if ($pref === null) {
            return [false, false, null, null];
        }

        $seekerAge = $seeker->age;
        $ageOk = $seekerAge === null
            || ($seekerAge >= ($pref->age_min ?? 0) && $seekerAge <= ($pref->age_max ?? 200));
        $genderOk = $pref->preferred_gender === null || $pref->preferred_gender === $seeker->gender?->value;

        return [true, $ageOk && $genderOk, 'You match their preferences too', null];
    }
}
