import { Head, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Ban, Flag, Heart, Lock, Mail, MapPin, Phone, Sparkles, Star, User as UserIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import MemberLayout from '@/layouts/member-layout';
import { formatCurrency, heightFromCm } from '@/lib/format';
import { cn } from '@/lib/utils';
import { MatchCard } from '@/types/profile';

interface DetailProfile extends MatchCard {
    connected: boolean;
    about_me: string | null;
    partner_expectations_note: string | null;
    marital_status: string | null;
    physical_status: string | null;
    complexion: string | null;
    employment_type: string | null;
    native_place: string | null;
    family: { type: string | null; status: string | null; values: string | null; details: string | null };
    lifestyle: { diet: string | null; smoking: string | null; drinking: string | null; extra: Record<string, unknown> | null };
    photos: { uuid: string; url: string | null; thumb_url: string | null; locked: boolean }[];
    income: { min: number | null; max: number | null } | null;
    income_locked: boolean;
    horoscope: Record<string, string | null> | null;
    horoscope_locked: boolean;
    contact: { mobile: string | null; email: string | null } | null;
    contact_locked: boolean;
}

interface Props {
    profile: DetailProfile;
    reportReasons: Record<string, string>;
    isSelf: boolean;
}

export default function ProfileDetail({ profile, reportReasons, isSelf }: Props) {
    const [active, setActive] = useState(0);
    const photos = profile.photos.filter((p) => p.url);

    const shortlist = () => router.post(route('member.shortlist.toggle'), { profile: profile.uuid }, { preserveScroll: true });

    return (
        <MemberLayout>
            <Head title={profile.display_name} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {/* Header + gallery */}
                    <Card className="overflow-hidden pt-0">
                        <div className="from-primary/10 to-secondary/10 relative flex aspect-video items-center justify-center bg-gradient-to-br">
                            {photos.length > 0 ? (
                                <img
                                    src={photos[active]?.url ?? photos[0].url ?? ''}
                                    alt={profile.display_name}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="text-primary/40 flex flex-col items-center">
                                    {profile.photo_locked ? <Lock className="size-10" /> : <UserIcon className="size-12" />}
                                    <span className="mt-2 text-sm">{profile.photo_locked ? 'Photos are protected' : 'No photo added'}</span>
                                </div>
                            )}
                            {profile.score !== null && (
                                <span className="bg-background/90 text-primary absolute top-3 right-3 flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold shadow">
                                    <Sparkles className="size-4" /> {profile.score}% match
                                </span>
                            )}
                        </div>
                        {photos.length > 1 && (
                            <div className="flex gap-2 overflow-x-auto p-3">
                                {photos.map((p, i) => (
                                    <button
                                        key={p.uuid}
                                        onClick={() => setActive(i)}
                                        className={cn(
                                            'size-16 shrink-0 overflow-hidden rounded-lg border-2',
                                            i === active ? 'border-primary' : 'border-transparent',
                                        )}
                                    >
                                        <img src={p.thumb_url ?? ''} alt="" className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-semibold">{profile.display_name}</h1>
                                {profile.is_verified && <BadgeCheck className="text-secondary size-5" />}
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {profile.profile_code}
                                {profile.age ? ` · ${profile.age} yrs` : ''}
                                {profile.height_cm ? ` · ${heightFromCm(profile.height_cm)}` : ''}
                            </p>
                            {(profile.city || profile.state) && (
                                <p className="text-muted-foreground mt-1 flex items-center gap-1 text-sm">
                                    <MapPin className="size-4" /> {[profile.city, profile.state].filter(Boolean).join(', ')}
                                </p>
                            )}
                            {profile.reasons && profile.reasons.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-1.5">
                                    {profile.reasons.map((r) => (
                                        <span key={r} className="bg-primary/5 text-primary rounded-full px-2.5 py-1 text-xs">
                                            {r}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {profile.about_me && <TextCard title="About" body={profile.about_me} />}
                    {profile.partner_expectations_note && <TextCard title="Partner expectations" body={profile.partner_expectations_note} />}

                    <DetailGrid
                        title="Basic details"
                        items={[
                            ['Marital status', profile.marital_status],
                            ['Religion', profile.religion],
                            ['Mother tongue', profile.mother_tongue],
                            ['Education', profile.education],
                            ['Profession', profile.profession],
                            ['Employment', profile.employment_type],
                            ['Complexion', profile.complexion],
                            ['Native place', profile.native_place],
                        ]}
                    />

                    <DetailGrid
                        title="Family"
                        items={[
                            ['Family type', profile.family.type],
                            ['Family status', profile.family.status],
                            ['Family values', profile.family.values],
                        ]}
                        extra={profile.family.details}
                    />

                    <DetailGrid
                        title="Lifestyle"
                        items={[
                            ['Diet', profile.lifestyle.diet],
                            ['Smoking', profile.lifestyle.smoking],
                            ['Drinking', profile.lifestyle.drinking],
                        ]}
                    />

                    {/* Sensitive locked/unlocked blocks */}
                    <LockableCard title="Income" locked={profile.income_locked}>
                        {profile.income && (profile.income.min || profile.income.max) ? (
                            <p className="text-sm">
                                {formatCurrency(profile.income.min ?? 0)} – {formatCurrency(profile.income.max ?? 0)} / year
                            </p>
                        ) : (
                            <p className="text-muted-foreground text-sm">Not specified</p>
                        )}
                    </LockableCard>

                    <LockableCard title="Horoscope" locked={profile.horoscope_locked}>
                        {profile.horoscope ? (
                            <div className="grid gap-2 text-sm sm:grid-cols-2">
                                <Detail label="Star" value={profile.horoscope.star} />
                                <Detail label="Rasi" value={profile.horoscope.rasi} />
                                <Detail label="Dosham" value={profile.horoscope.dosham} />
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">Not shared</p>
                        )}
                    </LockableCard>
                </div>

                {/* Actions sidebar */}
                {!isSelf && (
                    <div className="space-y-4 lg:sticky lg:top-24 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Connect</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {profile.contact ? (
                                    <div className="bg-secondary/5 space-y-2 rounded-lg p-3 text-sm">
                                        <p className="flex items-center gap-2">
                                            <Phone className="text-secondary size-4" /> {profile.contact.mobile}
                                        </p>
                                        <p className="flex items-center gap-2">
                                            <Mail className="text-secondary size-4" /> {profile.contact.email}
                                        </p>
                                    </div>
                                ) : (
                                    <p className="bg-muted text-muted-foreground flex items-center gap-2 rounded-lg p-3 text-xs">
                                        <Lock className="size-4" /> Contact details unlock after your interest is accepted.
                                    </p>
                                )}

                                <InterestButton profile={profile} />

                                <Button variant="outline" className="w-full" onClick={shortlist}>
                                    <Star className={cn('size-4', profile.is_shortlisted && 'fill-current')} />
                                    {profile.is_shortlisted ? 'Shortlisted' : 'Add to shortlist'}
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="flex gap-2 pt-6">
                                <ReportDialog profileUuid={profile.uuid} reasons={reportReasons} />
                                <BlockDialog profileUuid={profile.uuid} name={profile.display_name} />
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </MemberLayout>
    );
}

function InterestButton({ profile }: { profile: DetailProfile }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm<{ profile: string; message: string }>({ profile: profile.uuid, message: '' });

    if (profile.interest_sent === 'accepted' || profile.connected) {
        return (
            <Button className="bg-secondary text-secondary-foreground hover:bg-secondary/90 w-full" disabled>
                Connected
            </Button>
        );
    }
    if (profile.interest_sent === 'sent') {
        return (
            <Button className="w-full" variant="secondary" disabled>
                Interest sent
            </Button>
        );
    }

    const submit = () => post(route('member.interests.store'), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="w-full">
                    <Heart className="size-4" /> Express interest
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Express interest</DialogTitle>
                <DialogDescription>Add an optional note. Your contact details stay private until they accept.</DialogDescription>
                <div className="grid gap-2">
                    <Label>Message (optional)</Label>
                    <Textarea
                        value={data.message}
                        onChange={(e) => setData('message', e.target.value)}
                        maxLength={500}
                        placeholder="Say hello respectfully…"
                    />
                </div>
                <DialogFooter>
                    <Button onClick={submit} disabled={processing}>
                        Send interest
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ReportDialog({ profileUuid, reasons }: { profileUuid: string; reasons: Record<string, string> }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm<{ profile: string; reason: string; details: string }>({
        profile: profileUuid,
        reason: '',
        details: '',
    });
    const submit = () => post(route('member.reports.store'), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" className="text-muted-foreground flex-1">
                    <Flag className="size-4" /> Report
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Report this profile</DialogTitle>
                <DialogDescription>Reports are confidential and reviewed by our team.</DialogDescription>
                <div className="space-y-3">
                    <div className="grid gap-2">
                        <Label>Reason</Label>
                        <Select value={data.reason} onValueChange={(v) => setData('reason', v)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a reason" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(reasons).map(([v, l]) => (
                                    <SelectItem key={v} value={v}>
                                        {l}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.reason && <p className="text-destructive text-sm">{errors.reason}</p>}
                    </div>
                    <div className="grid gap-2">
                        <Label>Details (optional)</Label>
                        <Textarea value={data.details} onChange={(e) => setData('details', e.target.value)} />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="destructive" onClick={submit} disabled={processing || !data.reason}>
                        Submit report
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function BlockDialog({ profileUuid, name }: { profileUuid: string; name: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm<{ profile: string; reason: string }>({ profile: profileUuid, reason: '' });
    const submit = () => post(route('member.blocked.store'), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" className="text-destructive flex-1">
                    <Ban className="size-4" /> Block
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Block {name}?</DialogTitle>
                <DialogDescription>
                    They will no longer be able to see your profile or contact you. Any pending interest is withdrawn.
                </DialogDescription>
                <div className="grid gap-2">
                    <Label>Reason (optional)</Label>
                    <Textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} />
                </div>
                <DialogFooter>
                    <Button variant="destructive" onClick={submit} disabled={processing}>
                        Block profile
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function TextCard({ title, body }: { title: string; body: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-sm whitespace-pre-line">{body}</p>
            </CardContent>
        </Card>
    );
}

function DetailGrid({ title, items, extra }: { title: string; items: [string, string | null][]; extra?: string | null }) {
    const filled = items.filter(([, v]) => v);
    if (filled.length === 0 && !extra) return null;
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                {filled.map(([label, value]) => (
                    <Detail key={label} label={label} value={value} />
                ))}
                {extra && <p className="text-muted-foreground text-sm sm:col-span-2">{extra}</p>}
            </CardContent>
        </Card>
    );
}

function LockableCard({ title, locked, children }: { title: string; locked: boolean; children: React.ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    {title} {locked && <Lock className="text-muted-foreground size-4" />}
                </CardTitle>
            </CardHeader>
            <CardContent>{locked ? <p className="text-muted-foreground text-sm">Visible to connected members only.</p> : children}</CardContent>
        </Card>
    );
}

function Detail({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm font-medium capitalize">{value ? String(value).replace(/_/g, ' ') : '—'}</p>
        </div>
    );
}
