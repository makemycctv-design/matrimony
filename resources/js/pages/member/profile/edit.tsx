import { Head, useForm } from '@inertiajs/react';
import {
    BadgeCheck,
    BookHeart,
    Check,
    GraduationCap,
    Heart,
    Home,
    Images,
    Loader2,
    MapPin,
    ScrollText,
    ShieldCheck,
    Sparkles,
    User as UserIcon,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import DocumentManager from '@/components/profile/document-manager';
import PhotoGallery from '@/components/profile/photo-gallery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import MemberLayout from '@/layouts/member-layout';
import { cn } from '@/lib/utils';
import { Option, OwnerProfile, ProfileOptions } from '@/types/profile';

interface EditProps {
    profile: OwnerProfile;
    options: ProfileOptions;
    canSubmit: boolean;
    documentTypes: Record<string, string>;
}

const STEPS = [
    { key: 'basic', label: 'Basic', icon: UserIcon },
    { key: 'community', label: 'Community', icon: Heart },
    { key: 'career', label: 'Education & Career', icon: GraduationCap },
    { key: 'location', label: 'Location', icon: MapPin },
    { key: 'family', label: 'Family', icon: Home },
    { key: 'lifestyle', label: 'Lifestyle', icon: Sparkles },
    { key: 'horoscope', label: 'Horoscope', icon: BookHeart },
    { key: 'about', label: 'About & Expectations', icon: ScrollText },
    { key: 'photos', label: 'Photos', icon: Images },
    { key: 'documents', label: 'Documents', icon: ShieldCheck },
] as const;

type StepKey = (typeof STEPS)[number]['key'];

export default function ProfileEdit({ profile, options, canSubmit, documentTypes }: EditProps) {
    const [step, setStep] = useState<StepKey>('basic');

    const form = useForm<Record<string, unknown>>(() => hydrate(profile));

    const submitReview = useForm({});

    const save = (section: string) => {
        form.transform((data) => normalize(data));
        form.post(route('member.profile.section', { section }), { preserveScroll: true, preserveState: true });
    };

    const set = (key: string, value: unknown) => form.setData(key, value);
    const val = (key: string) => (form.data[key] ?? '') as string;

    // Dependent option lists.
    const castes = useMemo(
        () => options.castes.filter((c) => !form.data.religion_id || c.religion_id === Number(form.data.religion_id)),
        [options.castes, form.data.religion_id],
    );
    const subCastes = useMemo(
        () => options.sub_castes.filter((s) => !form.data.caste_id || s.caste_id === Number(form.data.caste_id)),
        [options.sub_castes, form.data.caste_id],
    );
    const states = useMemo(
        () => options.states.filter((s) => !form.data.country_id || s.parent_id === Number(form.data.country_id)),
        [options.states, form.data.country_id],
    );
    const districts = useMemo(
        () => options.districts.filter((d) => !form.data.state_id || d.parent_id === Number(form.data.state_id)),
        [options.districts, form.data.state_id],
    );
    const cities = useMemo(
        () => options.cities.filter((c) => !form.data.district_id || c.parent_id === Number(form.data.district_id)),
        [options.cities, form.data.district_id],
    );

    return (
        <MemberLayout title="My Profile">
            <Head title="My Profile" />

            <StatusBanner
                profile={profile}
                canSubmit={canSubmit}
                onSubmit={() => submitReview.post(route('member.profile.submit'), { preserveScroll: true })}
                submitting={submitReview.processing}
            />

            <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
                {/* Step nav */}
                <aside className="lg:sticky lg:top-24 lg:self-start">
                    <nav className="flex gap-2 overflow-x-auto lg:flex-col">
                        {STEPS.map((s) => (
                            <button
                                key={s.key}
                                onClick={() => setStep(s.key)}
                                className={cn(
                                    'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    step === s.key ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted',
                                )}
                            >
                                <s.icon className="size-4" />
                                {s.label}
                            </button>
                        ))}
                    </nav>
                </aside>

                <Card>
                    <CardContent className="p-6">
                        {step === 'basic' && (
                            <Section title="Basic information" onSave={() => save('basic')} processing={form.processing}>
                                <Grid>
                                    <Field label="First name" required error={form.errors.first_name}>
                                        <Input value={val('first_name')} onChange={(e) => set('first_name', e.target.value)} />
                                    </Field>
                                    <Field label="Last name" error={form.errors.last_name}>
                                        <Input value={val('last_name')} onChange={(e) => set('last_name', e.target.value)} />
                                    </Field>
                                    <SelectField
                                        label="Name display"
                                        value={val('name_display')}
                                        onChange={(v) => set('name_display', v)}
                                        options={[
                                            ['full', 'Full name'],
                                            ['first_only', 'First name only'],
                                            ['initials', 'Initials'],
                                            ['hidden', 'Hidden (ID only)'],
                                        ]}
                                        error={form.errors.name_display}
                                    />
                                    <Field label="Date of birth" required error={form.errors.date_of_birth}>
                                        <Input type="date" value={val('date_of_birth')} onChange={(e) => set('date_of_birth', e.target.value)} />
                                    </Field>
                                    <SelectField
                                        label="Gender"
                                        required
                                        value={val('gender')}
                                        onChange={(v) => set('gender', v)}
                                        options={[
                                            ['male', 'Male'],
                                            ['female', 'Female'],
                                            ['other', 'Other'],
                                        ]}
                                        error={form.errors.gender}
                                    />
                                    <SelectField
                                        label="Marital status"
                                        required
                                        value={val('marital_status')}
                                        onChange={(v) => set('marital_status', v)}
                                        options={[
                                            ['never_married', 'Never married'],
                                            ['divorced', 'Divorced'],
                                            ['widowed', 'Widowed'],
                                            ['separated', 'Separated'],
                                            ['annulled', 'Annulled'],
                                        ]}
                                        error={form.errors.marital_status}
                                    />
                                    <Field label="Height (cm)" error={form.errors.height_cm}>
                                        <Input type="number" value={val('height_cm')} onChange={(e) => set('height_cm', e.target.value)} />
                                    </Field>
                                    <Field label="Weight (kg)" error={form.errors.weight_kg}>
                                        <Input type="number" value={val('weight_kg')} onChange={(e) => set('weight_kg', e.target.value)} />
                                    </Field>
                                    <SelectField
                                        label="Physical status"
                                        value={val('physical_status')}
                                        onChange={(v) => set('physical_status', v)}
                                        options={[
                                            ['none', 'No disability'],
                                            ['physically_challenged', 'Physically challenged'],
                                        ]}
                                        error={form.errors.physical_status}
                                    />
                                    <Field label="Complexion" error={form.errors.complexion}>
                                        <Input value={val('complexion')} onChange={(e) => set('complexion', e.target.value)} />
                                    </Field>
                                </Grid>
                            </Section>
                        )}

                        {step === 'community' && (
                            <Section title="Religion & community" onSave={() => save('community')} processing={form.processing}>
                                <p className="text-muted-foreground mb-4 text-sm">
                                    Community details are optional and only used for matching when you enable them.
                                </p>
                                <Grid>
                                    <OptionSelect
                                        label="Religion"
                                        value={val('religion_id')}
                                        onChange={(v) => {
                                            set('religion_id', v);
                                            set('caste_id', '');
                                            set('sub_caste_id', '');
                                        }}
                                        options={options.religions}
                                        error={form.errors.religion_id}
                                    />
                                    <OptionSelect
                                        label="Caste"
                                        value={val('caste_id')}
                                        onChange={(v) => {
                                            set('caste_id', v);
                                            set('sub_caste_id', '');
                                        }}
                                        options={castes}
                                        error={form.errors.caste_id}
                                    />
                                    <OptionSelect
                                        label="Sub-caste"
                                        value={val('sub_caste_id')}
                                        onChange={(v) => set('sub_caste_id', v)}
                                        options={subCastes}
                                        error={form.errors.sub_caste_id}
                                    />
                                    <OptionSelect
                                        label="Mother tongue"
                                        value={val('mother_tongue_id')}
                                        onChange={(v) => set('mother_tongue_id', v)}
                                        options={options.mother_tongues}
                                        error={form.errors.mother_tongue_id}
                                    />
                                    <Field label="Gothra" error={form.errors.gothra}>
                                        <Input value={val('gothra')} onChange={(e) => set('gothra', e.target.value)} />
                                    </Field>
                                </Grid>
                            </Section>
                        )}

                        {step === 'career' && (
                            <Section title="Education & career" onSave={() => save('career')} processing={form.processing}>
                                <Grid>
                                    <OptionSelect
                                        label="Highest education"
                                        value={val('education_id')}
                                        onChange={(v) => set('education_id', v)}
                                        options={options.educations}
                                        error={form.errors.education_id}
                                    />
                                    <Field label="Education detail" error={form.errors.education_detail}>
                                        <Input value={val('education_detail')} onChange={(e) => set('education_detail', e.target.value)} />
                                    </Field>
                                    <OptionSelect
                                        label="Profession"
                                        value={val('profession_id')}
                                        onChange={(v) => set('profession_id', v)}
                                        options={options.professions}
                                        error={form.errors.profession_id}
                                    />
                                    <Field label="Role / designation" error={form.errors.profession_detail}>
                                        <Input value={val('profession_detail')} onChange={(e) => set('profession_detail', e.target.value)} />
                                    </Field>
                                    <Field label="Company / organisation" error={form.errors.company_name}>
                                        <Input value={val('company_name')} onChange={(e) => set('company_name', e.target.value)} />
                                    </Field>
                                    <SelectField
                                        label="Employment type"
                                        value={val('employment_type')}
                                        onChange={(v) => set('employment_type', v)}
                                        options={[
                                            ['government', 'Government'],
                                            ['private', 'Private'],
                                            ['business', 'Business'],
                                            ['self_employed', 'Self-employed'],
                                            ['not_working', 'Not working'],
                                        ]}
                                        error={form.errors.employment_type}
                                    />
                                    <Field label="Annual income min (₹)" error={form.errors.annual_income_min}>
                                        <Input
                                            type="number"
                                            value={val('annual_income_min')}
                                            onChange={(e) => set('annual_income_min', e.target.value)}
                                        />
                                    </Field>
                                    <Field label="Annual income max (₹)" error={form.errors.annual_income_max}>
                                        <Input
                                            type="number"
                                            value={val('annual_income_max')}
                                            onChange={(e) => set('annual_income_max', e.target.value)}
                                        />
                                    </Field>
                                </Grid>
                            </Section>
                        )}

                        {step === 'location' && (
                            <Section title="Location" onSave={() => save('location')} processing={form.processing}>
                                <Grid>
                                    <OptionSelect
                                        label="Country"
                                        value={val('country_id')}
                                        onChange={(v) => {
                                            set('country_id', v);
                                            set('state_id', '');
                                            set('district_id', '');
                                            set('city_id', '');
                                        }}
                                        options={options.countries}
                                        error={form.errors.country_id}
                                    />
                                    <OptionSelect
                                        label="State"
                                        value={val('state_id')}
                                        onChange={(v) => {
                                            set('state_id', v);
                                            set('district_id', '');
                                            set('city_id', '');
                                        }}
                                        options={states}
                                        error={form.errors.state_id}
                                    />
                                    <OptionSelect
                                        label="District"
                                        value={val('district_id')}
                                        onChange={(v) => {
                                            set('district_id', v);
                                            set('city_id', '');
                                        }}
                                        options={districts}
                                        error={form.errors.district_id}
                                    />
                                    <OptionSelect
                                        label="City"
                                        value={val('city_id')}
                                        onChange={(v) => set('city_id', v)}
                                        options={cities}
                                        error={form.errors.city_id}
                                    />
                                    <Field label="Native place" error={form.errors.native_place}>
                                        <Input value={val('native_place')} onChange={(e) => set('native_place', e.target.value)} />
                                    </Field>
                                </Grid>
                            </Section>
                        )}

                        {step === 'family' && (
                            <Section title="Family details" onSave={() => save('family')} processing={form.processing}>
                                <Grid>
                                    <SelectField
                                        label="Family type"
                                        value={val('family_type')}
                                        onChange={(v) => set('family_type', v)}
                                        options={[
                                            ['nuclear', 'Nuclear'],
                                            ['joint', 'Joint'],
                                        ]}
                                        error={form.errors.family_type}
                                    />
                                    <SelectField
                                        label="Family status"
                                        value={val('family_status')}
                                        onChange={(v) => set('family_status', v)}
                                        options={[
                                            ['middle_class', 'Middle class'],
                                            ['upper_middle', 'Upper middle class'],
                                            ['affluent', 'Affluent'],
                                            ['rich', 'Rich'],
                                        ]}
                                        error={form.errors.family_status}
                                    />
                                    <SelectField
                                        label="Family values"
                                        value={val('family_values')}
                                        onChange={(v) => set('family_values', v)}
                                        options={[
                                            ['traditional', 'Traditional'],
                                            ['moderate', 'Moderate'],
                                            ['liberal', 'Liberal'],
                                        ]}
                                        error={form.errors.family_values}
                                    />
                                    <Field label="Father's occupation" error={form.errors.father_occupation}>
                                        <Input value={val('father_occupation')} onChange={(e) => set('father_occupation', e.target.value)} />
                                    </Field>
                                    <Field label="Mother's occupation" error={form.errors.mother_occupation}>
                                        <Input value={val('mother_occupation')} onChange={(e) => set('mother_occupation', e.target.value)} />
                                    </Field>
                                    <Field label="Brothers" error={form.errors.brothers}>
                                        <Input type="number" value={val('brothers')} onChange={(e) => set('brothers', e.target.value)} />
                                    </Field>
                                    <Field label="Sisters" error={form.errors.sisters}>
                                        <Input type="number" value={val('sisters')} onChange={(e) => set('sisters', e.target.value)} />
                                    </Field>
                                </Grid>
                                <div className="mt-4">
                                    <Field label="About the family" error={form.errors.family_details}>
                                        <Textarea value={val('family_details')} onChange={(e) => set('family_details', e.target.value)} />
                                    </Field>
                                </div>
                            </Section>
                        )}

                        {step === 'lifestyle' && (
                            <Section title="Lifestyle" onSave={() => save('lifestyle')} processing={form.processing}>
                                <Grid>
                                    <SelectField
                                        label="Diet"
                                        value={val('diet')}
                                        onChange={(v) => set('diet', v)}
                                        options={[
                                            ['vegetarian', 'Vegetarian'],
                                            ['non_vegetarian', 'Non-vegetarian'],
                                            ['eggetarian', 'Eggetarian'],
                                            ['vegan', 'Vegan'],
                                        ]}
                                        error={form.errors.diet}
                                    />
                                    <SelectField
                                        label="Smoking"
                                        value={val('smoking')}
                                        onChange={(v) => set('smoking', v)}
                                        options={[
                                            ['no', 'No'],
                                            ['occasionally', 'Occasionally'],
                                            ['yes', 'Yes'],
                                        ]}
                                        error={form.errors.smoking}
                                    />
                                    <SelectField
                                        label="Drinking"
                                        value={val('drinking')}
                                        onChange={(v) => set('drinking', v)}
                                        options={[
                                            ['no', 'No'],
                                            ['occasionally', 'Occasionally'],
                                            ['yes', 'Yes'],
                                        ]}
                                        error={form.errors.drinking}
                                    />
                                    <Field label="Hobbies (comma separated)">
                                        <Input
                                            value={(form.data.hobbies as string) ?? ''}
                                            onChange={(e) => set('hobbies', e.target.value)}
                                            placeholder="Reading, Music, Travel"
                                        />
                                    </Field>
                                </Grid>
                            </Section>
                        )}

                        {step === 'horoscope' && (
                            <Section title="Horoscope (optional)" onSave={() => save('horoscope')} processing={form.processing}>
                                <div className="mb-4 flex items-center gap-3">
                                    <Switch checked={!!form.data.horoscope_enabled} onCheckedChange={(c) => set('horoscope_enabled', c)} id="horo" />
                                    <Label htmlFor="horo">Include horoscope details and use them in matching</Label>
                                </div>
                                {!!form.data.horoscope_enabled && (
                                    <Grid>
                                        <Field label="Birth time">
                                            <Input
                                                value={val('birth_time')}
                                                onChange={(e) => set('birth_time', e.target.value)}
                                                placeholder="e.g. 06:45 AM"
                                            />
                                        </Field>
                                        <Field label="Birth place">
                                            <Input value={val('birth_place')} onChange={(e) => set('birth_place', e.target.value)} />
                                        </Field>
                                        <Field label="Star (Nakshatra)">
                                            <Input value={val('star')} onChange={(e) => set('star', e.target.value)} />
                                        </Field>
                                        <Field label="Rasi (Moon sign)">
                                            <Input value={val('rasi')} onChange={(e) => set('rasi', e.target.value)} />
                                        </Field>
                                        <SelectField
                                            label="Dosham"
                                            value={val('dosham')}
                                            onChange={(v) => set('dosham', v)}
                                            options={[
                                                ['none', 'None'],
                                                ['manglik', 'Manglik'],
                                                ['partial', 'Partial'],
                                                ['dont_know', "Don't know"],
                                            ]}
                                        />
                                    </Grid>
                                )}
                            </Section>
                        )}

                        {step === 'about' && (
                            <Section title="About & partner expectations" onSave={() => save('about')} processing={form.processing}>
                                <div className="space-y-4">
                                    <Field label="About me" error={form.errors.about_me}>
                                        <Textarea
                                            rows={5}
                                            value={val('about_me')}
                                            onChange={(e) => set('about_me', e.target.value)}
                                            placeholder="Share a little about yourself, your values and interests."
                                        />
                                    </Field>
                                    <Field label="Partner expectations" error={form.errors.partner_expectations_note}>
                                        <Textarea
                                            rows={5}
                                            value={val('partner_expectations_note')}
                                            onChange={(e) => set('partner_expectations_note', e.target.value)}
                                            placeholder="Describe the kind of partner you are looking for."
                                        />
                                    </Field>
                                </div>
                            </Section>
                        )}

                        {step === 'photos' && <PhotoGallery photos={profile.photos} />}
                        {step === 'documents' && <DocumentManager documents={profile.documents} documentTypes={documentTypes} />}
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}

// --- Helpers ---------------------------------------------------------------

function hydrate(profile: OwnerProfile): Record<string, unknown> {
    const f = profile.fields;
    const data: Record<string, unknown> = {};
    Object.entries(f).forEach(([k, v]) => {
        data[k] = v === null || v === undefined ? '' : v;
    });
    data.horoscope_enabled = !!f.horoscope_enabled;
    const lifestyle = (f.lifestyle as { hobbies?: string[] }) ?? {};
    data.hobbies = Array.isArray(lifestyle.hobbies) ? lifestyle.hobbies.join(', ') : '';
    return data;
}

function normalize(data: Record<string, unknown>): Record<string, unknown> {
    const out: Record<string, unknown> = {};
    Object.entries(data).forEach(([k, v]) => {
        out[k] = v === '' ? null : v;
    });
    if (typeof data.hobbies === 'string') {
        out.lifestyle = {
            hobbies: data.hobbies
                .split(',')
                .map((s) => s.trim())
                .filter(Boolean),
        };
    }
    out.horoscope_enabled = !!data.horoscope_enabled;
    return out;
}

function StatusBanner({
    profile,
    canSubmit,
    onSubmit,
    submitting,
}: {
    profile: OwnerProfile;
    canSubmit: boolean;
    onSubmit: () => void;
    submitting: boolean;
}) {
    const status = profile.status ?? 'draft';
    const showSubmit = ['draft', 'rejected'].includes(status);

    return (
        <Card className="mb-6">
            <CardContent className="flex flex-wrap items-center justify-between gap-4 p-5">
                <div className="flex items-center gap-4">
                    <div className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-xl">
                        {profile.is_verified ? <BadgeCheck className="size-6" /> : <UserIcon className="size-6" />}
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <p className="font-semibold">{profile.profile_code ?? 'Your profile'}</p>
                            <Badge
                                variant={profile.is_verified ? 'default' : 'secondary'}
                                className={profile.is_verified ? 'bg-secondary text-secondary-foreground' : ''}
                            >
                                {status.replace('_', ' ')}
                            </Badge>
                        </div>
                        <div className="mt-1 flex items-center gap-2">
                            <div className="bg-muted h-2 w-40 overflow-hidden rounded-full">
                                <div className="bg-primary h-full rounded-full" style={{ width: `${profile.completion_percentage}%` }} />
                            </div>
                            <span className="text-muted-foreground text-xs">{profile.completion_percentage}% complete</span>
                        </div>
                        {status === 'rejected' && profile.rejection_reason && (
                            <p className="text-destructive mt-1 text-sm">Reason: {profile.rejection_reason}</p>
                        )}
                    </div>
                </div>
                {showSubmit && (
                    <Button onClick={onSubmit} disabled={!canSubmit || submitting}>
                        {submitting && <Loader2 className="size-4 animate-spin" />}
                        <Check className="size-4" /> Submit for verification
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}

function Section({ title, children, onSave, processing }: { title: string; children: React.ReactNode; onSave: () => void; processing: boolean }) {
    return (
        <div>
            <h2 className="mb-4 text-lg font-semibold">{title}</h2>
            {children}
            <div className="mt-6 flex justify-end">
                <Button onClick={onSave} disabled={processing}>
                    {processing && <Loader2 className="size-4 animate-spin" />}
                    Save
                </Button>
            </div>
        </div>
    );
}

function Grid({ children }: { children: React.ReactNode }) {
    return <div className="grid gap-4 sm:grid-cols-2">{children}</div>;
}

function Field({ label, required, error, children }: { label: string; required?: boolean; error?: string; children: React.ReactNode }) {
    return (
        <div className="grid gap-2">
            <Label>
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-destructive text-sm">{error}</p>}
        </div>
    );
}

function SelectField({
    label,
    value,
    onChange,
    options,
    required,
    error,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    options: [string, string][];
    required?: boolean;
    error?: string;
}) {
    return (
        <Field label={label} required={required} error={error}>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    {options.map(([v, l]) => (
                        <SelectItem key={v} value={v}>
                            {l}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}

function OptionSelect({
    label,
    value,
    onChange,
    options,
    error,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    options: Option[];
    error?: string;
}) {
    return (
        <Field label={label} error={error}>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    {options.map((o) => (
                        <SelectItem key={o.id} value={String(o.id)}>
                            {o.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}
