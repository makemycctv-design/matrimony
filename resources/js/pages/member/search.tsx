import { Head, router } from '@inertiajs/react';
import { Bookmark, Filter, Search as SearchIcon, SlidersHorizontal, X } from 'lucide-react';
import { useState } from 'react';

import ProfileCard from '@/components/profile/profile-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import MemberLayout from '@/layouts/member-layout';
import { MatchCard, Option, ProfileOptions } from '@/types/profile';

interface Paginated<T> {
    data: T[];
    total: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface SavedSearch {
    uuid: string;
    name: string;
    criteria: Record<string, unknown>;
}

interface Props {
    results: Paginated<MatchCard>;
    filters: Record<string, unknown>;
    sort: string;
    options: ProfileOptions;
    savedSearches: SavedSearch[];
}

const MARITAL = [
    ['never_married', 'Never married'],
    ['divorced', 'Divorced'],
    ['widowed', 'Widowed'],
    ['separated', 'Separated'],
];

export default function Search({ results, filters, sort, options, savedSearches }: Props) {
    const [form, setForm] = useState<Record<string, unknown>>({ ...filters });
    const [sortBy, setSortBy] = useState(sort);
    const [open, setOpen] = useState(false);

    const set = (key: string, value: unknown) => setForm((f) => ({ ...f, [key]: value }));

    const toggleIn = (key: string, id: number) => {
        const current = (form[key] as number[]) ?? [];
        set(key, current.includes(id) ? current.filter((x) => x !== id) : [...current, id]);
    };

    const apply = (extra: Record<string, unknown> = {}) => {
        const params = clean({ ...form, ...extra, sort: sortBy });
        router.get(route('member.search'), params, { preserveState: true, preserveScroll: true });
        setOpen(false);
    };

    const reset = () => {
        setForm({});
        router.get(route('member.search'), { sort: sortBy }, { preserveState: true });
    };

    const saveSearch = () => {
        const name = window.prompt('Name this search');
        if (!name) return;
        router.post(route('member.saved-searches.store'), { name, criteria: clean(form) }, { preserveScroll: true });
    };

    const runSaved = (s: SavedSearch) => {
        setForm({ ...s.criteria });
        router.get(route('member.search'), clean({ ...s.criteria, sort: sortBy }), { preserveState: true });
    };

    return (
        <MemberLayout title="Search">
            <Head title="Search profiles" />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div className="relative min-w-56 flex-1">
                    <SearchIcon className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        className="pl-9"
                        placeholder="Search by name, ID or keyword…"
                        value={(form.keyword as string) ?? ''}
                        onChange={(e) => set('keyword', e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                    />
                </div>

                <Select
                    value={sortBy}
                    onValueChange={(v) => {
                        setSortBy(v);
                        router.get(route('member.search'), clean({ ...form, sort: v }), { preserveState: true, preserveScroll: true });
                    }}
                >
                    <SelectTrigger className="w-44">
                        <SlidersHorizontal className="size-4" />
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="relevance">Most relevant</SelectItem>
                        <SelectItem value="newest">Newest</SelectItem>
                        <SelectItem value="active">Recently active</SelectItem>
                        <SelectItem value="age_asc">Age: youngest</SelectItem>
                        <SelectItem value="age_desc">Age: oldest</SelectItem>
                    </SelectContent>
                </Select>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild>
                        <Button variant="outline">
                            <Filter className="size-4" /> Filters
                        </Button>
                    </SheetTrigger>
                    <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                        <SheetHeader>
                            <SheetTitle>Advanced filters</SheetTitle>
                        </SheetHeader>
                        <div className="space-y-5 px-4 pb-24">
                            <Range label="Age" minKey="age_min" maxKey="age_max" form={form} set={set} />
                            <Range label="Height (cm)" minKey="height_min" maxKey="height_max" form={form} set={set} />

                            <div>
                                <Label className="mb-2 block">Marital status</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    {MARITAL.map(([v, l]) => (
                                        <CheckRow
                                            key={v}
                                            label={l}
                                            checked={((form.marital_statuses as string[]) ?? []).includes(v)}
                                            onToggle={() => {
                                                const cur = (form.marital_statuses as string[]) ?? [];
                                                set('marital_statuses', cur.includes(v) ? cur.filter((x) => x !== v) : [...cur, v]);
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>

                            <MultiSelect
                                label="Religion"
                                options={options.religions}
                                selected={(form.religion_ids as number[]) ?? []}
                                onToggle={(id) => toggleIn('religion_ids', id)}
                            />
                            <MultiSelect
                                label="Mother tongue"
                                options={options.mother_tongues}
                                selected={(form.mother_tongue_ids as number[]) ?? []}
                                onToggle={(id) => toggleIn('mother_tongue_ids', id)}
                            />
                            <MultiSelect
                                label="Education"
                                options={options.educations}
                                selected={(form.education_ids as number[]) ?? []}
                                onToggle={(id) => toggleIn('education_ids', id)}
                            />

                            <div className="grid gap-2">
                                <Label>State</Label>
                                <OptionPicker
                                    options={options.states}
                                    value={form.state_id as number | undefined}
                                    onChange={(v) => set('state_id', v)}
                                />
                            </div>

                            <div className="space-y-3 rounded-lg border p-3">
                                <ToggleRow
                                    label="Verified profiles only"
                                    checked={!!form.only_verified}
                                    onChange={() => set('only_verified', !form.only_verified)}
                                />
                                <ToggleRow
                                    label="With photo only"
                                    checked={!!form.only_with_photo}
                                    onChange={() => set('only_with_photo', !form.only_with_photo)}
                                />
                                <ToggleRow
                                    label="Recently active"
                                    checked={!!form.recently_active}
                                    onChange={() => set('recently_active', !form.recently_active)}
                                />
                                <ToggleRow label="Online now" checked={!!form.online} onChange={() => set('online', !form.online)} />
                            </div>

                            <div className="flex gap-2">
                                <Button className="flex-1" onClick={() => apply()}>
                                    Apply filters
                                </Button>
                                <Button variant="outline" onClick={reset}>
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>

                <Button variant="ghost" onClick={saveSearch}>
                    <Bookmark className="size-4" /> Save
                </Button>
            </div>

            {savedSearches.length > 0 && (
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <span className="text-muted-foreground text-xs">Saved:</span>
                    {savedSearches.map((s) => (
                        <span key={s.uuid} className="bg-background flex items-center gap-1 rounded-full border px-3 py-1 text-xs">
                            <button onClick={() => runSaved(s)} className="hover:text-primary">
                                {s.name}
                            </button>
                            <button
                                onClick={() =>
                                    router.delete(route('member.saved-searches.destroy', { savedSearch: s.uuid }), { preserveScroll: true })
                                }
                                aria-label="Delete saved search"
                            >
                                <X className="text-muted-foreground hover:text-destructive size-3" />
                            </button>
                        </span>
                    ))}
                </div>
            )}

            <p className="text-muted-foreground mb-4 text-sm">{results.total} profiles found</p>

            {results.data.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground py-16 text-center text-sm">
                        No profiles match your criteria. Try widening your filters.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {results.data.map((p) => (
                        <ProfileCard key={p.uuid} profile={p} />
                    ))}
                </div>
            )}

            {results.last_page > 1 && (
                <div className="mt-6 flex flex-wrap gap-1">
                    {results.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })}
                            className={`rounded-md px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'} ${!link.url ? 'opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </MemberLayout>
    );
}

function clean(obj: Record<string, unknown>): Record<string, unknown> {
    const out: Record<string, unknown> = {};
    Object.entries(obj).forEach(([k, v]) => {
        if (v === '' || v === null || v === undefined || v === false) return;
        if (Array.isArray(v) && v.length === 0) return;
        out[k] = v;
    });
    return out;
}

function Range({
    label,
    minKey,
    maxKey,
    form,
    set,
}: {
    label: string;
    minKey: string;
    maxKey: string;
    form: Record<string, unknown>;
    set: (k: string, v: unknown) => void;
}) {
    return (
        <div>
            <Label className="mb-2 block">{label}</Label>
            <div className="flex items-center gap-2">
                <Input type="number" placeholder="Min" value={(form[minKey] as string) ?? ''} onChange={(e) => set(minKey, e.target.value)} />
                <span className="text-muted-foreground">–</span>
                <Input type="number" placeholder="Max" value={(form[maxKey] as string) ?? ''} onChange={(e) => set(maxKey, e.target.value)} />
            </div>
        </div>
    );
}

function MultiSelect({
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
    return (
        <div>
            <Label className="mb-2 block">{label}</Label>
            <div className="max-h-40 space-y-1 overflow-y-auto rounded-lg border p-2">
                {options.map((o) => (
                    <CheckRow key={o.id} label={o.name} checked={selected.includes(o.id)} onToggle={() => onToggle(o.id)} />
                ))}
            </div>
        </div>
    );
}

function OptionPicker({ options, value, onChange }: { options: Option[]; value: number | undefined; onChange: (v: number | undefined) => void }) {
    return (
        <Select value={value ? String(value) : 'any'} onValueChange={(v) => onChange(v === 'any' ? undefined : Number(v))}>
            <SelectTrigger>
                <SelectValue placeholder="Any" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="any">Any</SelectItem>
                {options.map((o) => (
                    <SelectItem key={o.id} value={String(o.id)}>
                        {o.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function CheckRow({ label, checked, onToggle }: { label: string; checked: boolean; onToggle: () => void }) {
    return (
        <label className="hover:bg-muted flex cursor-pointer items-center gap-2 rounded px-1 py-1 text-sm">
            <Checkbox checked={checked} onClick={onToggle} />
            {label}
        </label>
    );
}

function ToggleRow({ label, checked, onChange }: { label: string; checked: boolean; onChange: () => void }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-sm">{label}</span>
            <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />
        </div>
    );
}
