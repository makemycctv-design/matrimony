import { Head, useForm } from '@inertiajs/react';
import { Loader2, SlidersHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import MemberLayout from '@/layouts/member-layout';
import { Option, ProfileOptions } from '@/types/profile';

interface Preference {
    preferred_gender: string | null;
    age_min: number | null;
    age_max: number | null;
    height_min_cm: number | null;
    height_max_cm: number | null;
    marital_statuses: string[] | null;
    religion_ids: number[] | null;
    mother_tongue_ids: number[] | null;
    education_ids: number[] | null;
    income_min: number | null;
    income_max: number | null;
    accept_physically_challenged: boolean;
    horoscope_match_required: boolean;
    only_verified: boolean;
    only_with_photo: boolean;
    weight_overrides: Record<string, number> | null;
    [key: string]: unknown;
}

interface Props {
    preference: Preference;
    options: ProfileOptions;
    weightFactors: string[];
    defaultWeights: Record<string, number>;
}

const MARITAL = [
    ['never_married', 'Never married'],
    ['divorced', 'Divorced'],
    ['widowed', 'Widowed'],
    ['separated', 'Separated'],
];

export default function PartnerPreferences({ preference, options, weightFactors, defaultWeights }: Props) {
    const { data, setData, put, processing } = useForm<Preference>({
        ...preference,
        marital_statuses: preference.marital_statuses ?? [],
        religion_ids: preference.religion_ids ?? [],
        mother_tongue_ids: preference.mother_tongue_ids ?? [],
        education_ids: preference.education_ids ?? [],
        weight_overrides: preference.weight_overrides ?? {},
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('member.partner.preferences.update'), { preserveScroll: true });
    };

    const toggleArr = (key: keyof Preference, value: number | string) => {
        const cur = (data[key] as (number | string)[]) ?? [];
        setData(key, (cur.includes(value) ? cur.filter((x) => x !== value) : [...cur, value]) as never);
    };

    const setWeight = (factor: string, value: number) => setData('weight_overrides', { ...(data.weight_overrides ?? {}), [factor]: value } as never);

    return (
        <MemberLayout title="Partner Preferences">
            <Head title="Partner preferences" />

            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Basic expectations</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Looking for</Label>
                            <Select value={data.preferred_gender ?? ''} onValueChange={(v) => setData('preferred_gender', v)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Any" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">Groom (Male)</SelectItem>
                                    <SelectItem value="female">Bride (Female)</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div />
                        <RangeRow
                            label="Age range"
                            minV={data.age_min}
                            maxV={data.age_max}
                            onMin={(v) => setData('age_min', v)}
                            onMax={(v) => setData('age_max', v)}
                        />
                        <RangeRow
                            label="Height range (cm)"
                            minV={data.height_min_cm}
                            maxV={data.height_max_cm}
                            onMin={(v) => setData('height_min_cm', v)}
                            onMax={(v) => setData('height_max_cm', v)}
                        />
                        <RangeRow
                            label="Annual income (₹)"
                            minV={data.income_min}
                            maxV={data.income_max}
                            onMin={(v) => setData('income_min', v)}
                            onMax={(v) => setData('income_max', v)}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Community & background</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <CheckGroup
                            label="Marital status"
                            items={MARITAL.map(([v, l]) => ({ id: v, name: l }))}
                            selected={data.marital_statuses ?? []}
                            onToggle={(v) => toggleArr('marital_statuses', v as string)}
                        />
                        <MultiCheck
                            label="Religion"
                            options={options.religions}
                            selected={data.religion_ids ?? []}
                            onToggle={(id) => toggleArr('religion_ids', id)}
                        />
                        <MultiCheck
                            label="Mother tongue"
                            options={options.mother_tongues}
                            selected={data.mother_tongue_ids ?? []}
                            onToggle={(id) => toggleArr('mother_tongue_ids', id)}
                        />
                        <MultiCheck
                            label="Education"
                            options={options.educations}
                            selected={data.education_ids ?? []}
                            onToggle={(id) => toggleArr('education_ids', id)}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        <ToggleRow
                            label="Only verified profiles"
                            checked={data.only_verified}
                            onChange={() => setData('only_verified', !data.only_verified)}
                        />
                        <ToggleRow
                            label="Only profiles with a photo"
                            checked={data.only_with_photo}
                            onChange={() => setData('only_with_photo', !data.only_with_photo)}
                        />
                        <ToggleRow
                            label="Require horoscope match"
                            checked={data.horoscope_match_required}
                            onChange={() => setData('horoscope_match_required', !data.horoscope_match_required)}
                        />
                        <ToggleRow
                            label="Open to physically challenged"
                            checked={data.accept_physically_challenged}
                            onChange={() => setData('accept_physically_challenged', !data.accept_physically_challenged)}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <SlidersHorizontal className="text-primary size-5" /> Importance weighting
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-muted-foreground text-sm">
                            Tune how much each factor influences your match scores. Leave at default to use the platform's balanced weighting.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {weightFactors.map((factor) => {
                                const value = data.weight_overrides?.[factor] ?? defaultWeights[factor] ?? 0;
                                return (
                                    <div key={factor}>
                                        <div className="mb-1 flex items-center justify-between text-sm">
                                            <span className="capitalize">{factor.replace(/_/g, ' ')}</span>
                                            <span className="text-muted-foreground">{value}</span>
                                        </div>
                                        <input
                                            type="range"
                                            min={0}
                                            max={100}
                                            value={value}
                                            onChange={(e) => setWeight(factor, Number(e.target.value))}
                                            className="w-full accent-[var(--color-primary)]"
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="size-4 animate-spin" />}
                        Save preferences
                    </Button>
                </div>
            </form>
        </MemberLayout>
    );
}

function RangeRow({
    label,
    minV,
    maxV,
    onMin,
    onMax,
}: {
    label: string;
    minV: number | null;
    maxV: number | null;
    onMin: (v: number | null) => void;
    onMax: (v: number | null) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <div className="flex items-center gap-2">
                <Input type="number" placeholder="Min" value={minV ?? ''} onChange={(e) => onMin(e.target.value ? Number(e.target.value) : null)} />
                <span className="text-muted-foreground">–</span>
                <Input type="number" placeholder="Max" value={maxV ?? ''} onChange={(e) => onMax(e.target.value ? Number(e.target.value) : null)} />
            </div>
        </div>
    );
}

function MultiCheck({
    label,
    options,
    selected,
    onToggle,
}: {
    label: string;
    options: Option[];
    selected: number[];
    onToggle: (id: number) => void;
}) {
    return <CheckGroup label={label} items={options} selected={selected} onToggle={(v) => onToggle(v as number)} />;
}

function CheckGroup({
    label,
    items,
    selected,
    onToggle,
}: {
    label: string;
    items: { id: number | string; name: string }[];
    selected: (number | string)[];
    onToggle: (id: number | string) => void;
}) {
    return (
        <div>
            <Label className="mb-2 block">{label}</Label>
            <div className="flex max-h-40 flex-wrap gap-2 overflow-y-auto">
                {items.map((o) => {
                    const on = selected.includes(o.id);
                    return (
                        <button
                            type="button"
                            key={o.id}
                            onClick={() => onToggle(o.id)}
                            className={`flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm ${on ? 'border-primary bg-primary/10 text-primary' : 'hover:bg-muted'}`}
                        >
                            <Checkbox checked={on} className="pointer-events-none size-3.5" /> {o.name}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function ToggleRow({ label, checked, onChange }: { label: string; checked: boolean; onChange: () => void }) {
    return (
        <div className="flex items-center justify-between border-b py-3 last:border-0">
            <span className="text-sm">{label}</span>
            <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />
        </div>
    );
}
