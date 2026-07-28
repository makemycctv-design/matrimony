<?php

namespace App\Services\Profile;

use App\Models\MemberProfile;

/**
 * Computes a weighted profile-completion score and returns actionable
 * suggestions for the fields that still need attention. Used on the member
 * dashboard and persisted onto member_profiles.completion_percentage.
 */
class ProfileCompletionCalculator
{
    /**
     * Field => [weight, human label]. Weights sum to 100.
     *
     * @var array<string, array{0:int,1:string}>
     */
    private const FIELDS = [
        'first_name' => [4, 'Add your name'],
        'date_of_birth' => [8, 'Add your date of birth'],
        'gender' => [4, 'Specify gender'],
        'marital_status' => [6, 'Add marital status'],
        'height_cm' => [5, 'Add your height'],
        'religion_id' => [6, 'Select your religion'],
        'mother_tongue_id' => [5, 'Add mother tongue'],
        'education_id' => [7, 'Add education'],
        'profession_id' => [7, 'Add profession'],
        'annual_income_min' => [4, 'Add income range'],
        'country_id' => [3, 'Add country'],
        'state_id' => [5, 'Add state'],
        'city_id' => [5, 'Add city'],
        'family_type' => [4, 'Add family details'],
        'diet' => [3, 'Add lifestyle details'],
        'about_me' => [12, 'Write a few lines about yourself'],
        'partner_expectations_note' => [12, 'Describe your partner expectations'],
    ];

    /**
     * @return array{percentage:int, suggestions:list<string>}
     */
    public function calculate(MemberProfile $profile): array
    {
        $score = 0;
        $suggestions = [];

        foreach (self::FIELDS as $field => [$weight, $label]) {
            if (filled($profile->getAttribute($field))) {
                $score += $weight;
            } else {
                $suggestions[] = $label;
            }
        }

        return [
            'percentage' => min(100, $score),
            'suggestions' => array_slice($suggestions, 0, 5),
        ];
    }

    /** Recalculate and persist the percentage on the model. */
    public function refresh(MemberProfile $profile): int
    {
        $result = $this->calculate($profile);

        $profile->forceFill(['completion_percentage' => $result['percentage']])->saveQuietly();

        return $result['percentage'];
    }
}
