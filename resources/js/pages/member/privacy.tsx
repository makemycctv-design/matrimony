import { Head, useForm } from '@inertiajs/react';
import { Bell, Eye, Loader2, ShieldCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import MemberLayout from '@/layouts/member-layout';
import { Preferences } from '@/types/profile';

const AUDIENCES: [string, string][] = [
    ['everyone', 'Everyone'],
    ['members', 'All members'],
    ['verified', 'Verified members'],
    ['premium', 'Premium members'],
    ['connected', 'Connected (interest accepted)'],
    ['none', 'No one'],
];

const INTEREST_AUDIENCES: [string, string][] = [
    ['everyone', 'Everyone'],
    ['members', 'All members'],
    ['verified', 'Verified members'],
    ['premium', 'Premium members'],
];

export default function Privacy({ preferences }: { preferences: Preferences }) {
    const { data, setData, put, processing } = useForm<Preferences>({ ...preferences });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('member.privacy.update'), { preserveScroll: true });
    };

    const toggle = (key: keyof Preferences) => setData(key, !data[key] as never);

    return (
        <MemberLayout title="Privacy & Visibility">
            <Head title="Privacy & Visibility" />

            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Eye className="text-primary size-5" /> Who can see what
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <VisibilityRow
                            label="Photos"
                            value={data.photo_visibility}
                            onChange={(v) => setData('photo_visibility', v)}
                            options={AUDIENCES}
                        />
                        <VisibilityRow
                            label="Contact details"
                            value={data.contact_visibility}
                            onChange={(v) => setData('contact_visibility', v)}
                            options={AUDIENCES}
                        />
                        <VisibilityRow
                            label="Horoscope"
                            value={data.horoscope_visibility}
                            onChange={(v) => setData('horoscope_visibility', v)}
                            options={AUDIENCES}
                        />
                        <VisibilityRow
                            label="Income"
                            value={data.income_visibility}
                            onChange={(v) => setData('income_visibility', v)}
                            options={AUDIENCES}
                        />
                        <VisibilityRow
                            label="Who can send me interest"
                            value={data.interest_from}
                            onChange={(v) => setData('interest_from', v)}
                            options={INTEREST_AUDIENCES}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ShieldCheck className="text-primary size-5" /> Discovery controls
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        <ToggleRow
                            label="Appear in search results"
                            description="Let members find your profile through search."
                            checked={data.appear_in_search}
                            onChange={() => toggle('appear_in_search')}
                        />
                        <ToggleRow
                            label="Only verified members can see me"
                            checked={data.visible_to_verified_only}
                            onChange={() => toggle('visible_to_verified_only')}
                        />
                        <ToggleRow
                            label="Only premium members can see me"
                            checked={data.visible_to_premium_only}
                            onChange={() => toggle('visible_to_premium_only')}
                        />
                        <ToggleRow
                            label="Hide details until I accept an interest"
                            checked={data.hide_details_until_interest_accepted}
                            onChange={() => toggle('hide_details_until_interest_accepted')}
                        />
                        <ToggleRow label="Show my online status" checked={data.show_online_status} onChange={() => toggle('show_online_status')} />
                        <ToggleRow label="Show my last seen" checked={data.show_last_seen} onChange={() => toggle('show_last_seen')} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Bell className="text-primary size-5" /> Notifications
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        <ToggleRow label="Email" checked={data.notify_email} onChange={() => toggle('notify_email')} />
                        <ToggleRow label="SMS" checked={data.notify_sms} onChange={() => toggle('notify_sms')} />
                        <ToggleRow label="WhatsApp" checked={data.notify_whatsapp} onChange={() => toggle('notify_whatsapp')} />
                        <ToggleRow label="In-app" checked={data.notify_in_app} onChange={() => toggle('notify_in_app')} />
                        <ToggleRow label="Browser push" checked={data.notify_push} onChange={() => toggle('notify_push')} />
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="size-4 animate-spin" />}
                        Save settings
                    </Button>
                </div>
            </form>
        </MemberLayout>
    );
}

function VisibilityRow({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    options: [string, string][];
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map(([v, l]) => (
                        <SelectItem key={v} value={v}>
                            {l}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function ToggleRow({ label, description, checked, onChange }: { label: string; description?: string; checked: boolean; onChange: () => void }) {
    return (
        <div className="flex items-center justify-between border-b py-3 last:border-0">
            <div>
                <p className="text-sm font-medium">{label}</p>
                {description && <p className="text-muted-foreground text-xs">{description}</p>}
            </div>
            <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />
        </div>
    );
}
