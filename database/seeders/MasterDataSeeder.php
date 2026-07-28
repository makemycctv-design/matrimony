<?php

namespace Database\Seeders;

use App\Models\Caste;
use App\Models\Education;
use App\Models\Location;
use App\Models\MotherTongue;
use App\Models\Profession;
use App\Models\Religion;
use App\Models\SubCaste;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds configurable taxonomy (religions, communities, languages, education,
 * professions) and an India-focused location hierarchy. Names include a
 * Malayalam translation where available. Idempotent via updateOrCreate.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->religions();
        $this->motherTongues();
        $this->educations();
        $this->professions();
        $this->locations();
    }

    private function religions(): void
    {
        $data = [
            ['Hindu', 'ഹിന്ദു', ['Nair' => 'നായർ', 'Ezhava' => 'ഈഴവ', 'Menon' => 'മേനോൻ', 'Brahmin' => 'ബ്രാഹ്മണൻ', 'Nadar' => 'നാടാർ']],
            ['Muslim', 'മുസ്ലിം', ['Sunni' => 'സുന്നി', 'Mappila' => 'മാപ്പിള', 'Shia' => 'ഷിയ']],
            ['Christian', 'ക്രിസ്ത്യൻ', ['Syro-Malabar' => 'സിറോ-മലബാർ', 'Latin Catholic' => 'ലാറ്റിൻ കത്തോലിക്ക', 'Jacobite' => 'യാക്കോബായ', 'Marthoma' => 'മാർത്തോമ']],
            ['Jain', 'ജൈനൻ', []],
            ['Sikh', 'സിഖ്', []],
            ['Buddhist', 'ബുദ്ധൻ', []],
            ['Other', 'മറ്റുള്ളവ', []],
        ];

        foreach ($data as $i => [$name, $ml, $castes]) {
            $religion = Religion::updateOrCreate(
                ['company_id' => null, 'slug' => Str::slug($name)],
                ['name' => $name, 'name_translations' => ['ml' => $ml], 'sort_order' => $i],
            );

            $ci = 0;
            foreach ($castes as $casteName => $casteMl) {
                Caste::updateOrCreate(
                    ['company_id' => null, 'religion_id' => $religion->id, 'slug' => Str::slug($casteName)],
                    ['name' => $casteName, 'name_translations' => ['ml' => $casteMl], 'sort_order' => $ci++],
                );
            }
        }

        // A couple of illustrative sub-castes under Nair.
        $nair = Caste::where('slug', 'nair')->first();
        if ($nair) {
            foreach (['Kiryathil', 'Illathu', 'Swaroopathil'] as $si => $sub) {
                SubCaste::updateOrCreate(
                    ['caste_id' => $nair->id, 'slug' => Str::slug($sub)],
                    ['company_id' => null, 'name' => $sub, 'sort_order' => $si],
                );
            }
        }
    }

    private function motherTongues(): void
    {
        $data = [
            ['Malayalam', 'മലയാളം'], ['Tamil', 'തമിഴ്'], ['Kannada', 'കന്നഡ'], ['Telugu', 'തെലുങ്ക്'],
            ['Hindi', 'ഹിന്ദി'], ['English', 'ഇംഗ്ലീഷ്'], ['Konkani', 'കൊങ്കണി'], ['Tulu', 'തുളു'],
        ];

        foreach ($data as $i => [$name, $ml]) {
            MotherTongue::updateOrCreate(
                ['company_id' => null, 'slug' => Str::slug($name)],
                ['name' => $name, 'name_translations' => ['ml' => $ml], 'sort_order' => $i],
            );
        }
    }

    private function educations(): void
    {
        $data = [
            ['High School', 'school'], ['Higher Secondary', 'school'], ['Diploma', 'diploma'],
            ['Bachelor of Engineering (B.E/B.Tech)', 'graduate'], ['Bachelor of Commerce (B.Com)', 'graduate'],
            ['Bachelor of Science (B.Sc)', 'graduate'], ['Bachelor of Arts (B.A)', 'graduate'],
            ['MBBS', 'professional'], ['Master of Engineering (M.E/M.Tech)', 'post_graduate'],
            ['Master of Business Administration (MBA)', 'post_graduate'], ['Master of Science (M.Sc)', 'post_graduate'],
            ['CA / CS / ICWA', 'professional'], ['Doctorate (PhD)', 'doctorate'],
        ];

        foreach ($data as $i => [$name, $category]) {
            Education::updateOrCreate(
                ['company_id' => null, 'slug' => Str::slug($name)],
                ['name' => $name, 'category' => $category, 'sort_order' => $i],
            );
        }
    }

    private function professions(): void
    {
        $data = [
            ['Software Professional', 'it'], ['Doctor', 'healthcare'], ['Nurse', 'healthcare'],
            ['Teacher / Lecturer', 'education'], ['Engineer', 'engineering'], ['Chartered Accountant', 'finance'],
            ['Banking Professional', 'finance'], ['Government Employee', 'government'], ['Business Owner', 'business'],
            ['Lawyer', 'legal'], ['Civil Services', 'government'], ['Defence / Armed Forces', 'government'],
            ['Marketing Professional', 'business'], ['Not Working', 'other'],
        ];

        foreach ($data as $i => [$name, $category]) {
            Profession::updateOrCreate(
                ['company_id' => null, 'slug' => Str::slug($name)],
                ['name' => $name, 'category' => $category, 'sort_order' => $i],
            );
        }
    }

    private function locations(): void
    {
        $india = Location::updateOrCreate(
            ['parent_id' => null, 'type' => 'country', 'slug' => 'india'],
            ['company_id' => null, 'name' => 'India', 'code' => 'IN', 'name_translations' => ['ml' => 'ഇന്ത്യ']],
        );

        // States with a few representative districts and cities.
        $tree = [
            'Kerala' => [
                'Thiruvananthapuram' => ['Thiruvananthapuram', 'Neyyattinkara'],
                'Ernakulam' => ['Kochi', 'Aluva'],
                'Kozhikode' => ['Kozhikode', 'Vadakara'],
                'Thrissur' => ['Thrissur', 'Chalakudy'],
            ],
            'Tamil Nadu' => [
                'Chennai' => ['Chennai'],
                'Coimbatore' => ['Coimbatore'],
            ],
            'Karnataka' => [
                'Bengaluru Urban' => ['Bengaluru'],
                'Dakshina Kannada' => ['Mangaluru'],
            ],
        ];

        foreach ($tree as $stateName => $districts) {
            $state = Location::updateOrCreate(
                ['parent_id' => $india->id, 'type' => 'state', 'slug' => Str::slug($stateName)],
                ['company_id' => null, 'name' => $stateName],
            );

            foreach ($districts as $districtName => $cities) {
                $district = Location::updateOrCreate(
                    ['parent_id' => $state->id, 'type' => 'district', 'slug' => Str::slug($districtName)],
                    ['company_id' => null, 'name' => $districtName],
                );

                foreach ($cities as $cityName) {
                    Location::updateOrCreate(
                        ['parent_id' => $district->id, 'type' => 'city', 'slug' => Str::slug($cityName)],
                        ['company_id' => null, 'name' => $cityName],
                    );
                }
            }
        }
    }
}
