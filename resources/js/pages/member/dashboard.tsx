import { Head, Link } from '@inertiajs/react';
import { BadgeCheck, Eye, Heart, Mail, Smartphone, Sparkles, Star } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';

interface DashboardProps {
    profile: {
        code: string | null;
        display_name: string;
        status: string | null;
        is_verified: boolean;
        completion: number;
        suggestions: string[];
    };
    verification: { email_verified: boolean; mobile_verified: boolean };
    sections: {
        recommended: unknown[];
        new_matches: unknown[];
        near_you: unknown[];
        recently_joined: { code: string | null; display_name: string; age: number | null; is_verified: boolean }[];
    };
    activity: { interests_received: number; interests_sent: number; shortlisted: number; profile_views: number };
}

export default function MemberDashboard({ profile, verification, sections, activity }: DashboardProps) {
    const stats = [
        { label: 'Profile views', value: activity.profile_views, icon: Eye },
        { label: 'Interests received', value: activity.interests_received, icon: Heart },
        { label: 'Interests sent', value: activity.interests_sent, icon: Sparkles },
        { label: 'Shortlisted', value: activity.shortlisted, icon: Star },
    ];

    return (
        <MemberLayout>
            <Head title="Dashboard" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Welcome, {profile.display_name} 👋</h1>
                    <p className="text-muted-foreground text-sm">
                        {profile.code && <span>ID {profile.code} · </span>}
                        <StatusBadge status={profile.status} verified={profile.is_verified} />
                    </p>
                </div>
                <Button asChild>
                    <Link href={route('member.my.profile')}>Complete my profile</Link>
                </Button>
            </div>

            {/* Verification prompts */}
            {(!verification.email_verified || !verification.mobile_verified) && (
                <div className="mb-6 grid gap-3 sm:grid-cols-2">
                    {!verification.mobile_verified && (
                        <VerifyBanner
                            icon={Smartphone}
                            title="Verify your mobile"
                            body="Confirm your number to secure your account."
                            href={route('verification.mobile.notice')}
                            cta="Verify now"
                        />
                    )}
                    {!verification.email_verified && (
                        <VerifyBanner
                            icon={Mail}
                            title="Verify your email"
                            body="Check your inbox for a verification link."
                            href={route('verification.notice')}
                            cta="Resend link"
                        />
                    )}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Completion */}
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle className="text-base">Profile completion</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="font-medium">{profile.completion}%</span>
                                <span className="text-muted-foreground">complete</span>
                            </div>
                            <div className="bg-muted h-2.5 w-full overflow-hidden rounded-full">
                                <div className="bg-primary h-full rounded-full transition-all" style={{ width: `${profile.completion}%` }} />
                            </div>
                        </div>
                        {profile.suggestions.length > 0 ? (
                            <ul className="space-y-2 text-sm">
                                {profile.suggestions.map((s) => (
                                    <li key={s} className="text-muted-foreground flex items-center gap-2">
                                        <span className="bg-primary size-1.5 rounded-full" /> {s}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-secondary text-sm">Your profile looks great! 🎉</p>
                        )}
                        <Button variant="outline" size="sm" className="w-full" asChild>
                            <Link href={route('member.my.profile')}>Update profile</Link>
                        </Button>
                    </CardContent>
                </Card>

                {/* Activity */}
                <div className="grid grid-cols-2 gap-4 lg:col-span-2">
                    {stats.map((s) => (
                        <Card key={s.label}>
                            <CardContent className="flex items-center gap-4 p-5">
                                <div className="bg-primary/10 text-primary flex size-11 items-center justify-center rounded-xl">
                                    <s.icon className="size-5" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{s.value}</p>
                                    <p className="text-muted-foreground text-xs">{s.label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            {/* Recently joined */}
            <div className="mt-8">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-lg font-semibold">Recently joined</h2>
                    <Link href={route('member.matches')} className="text-primary text-sm hover:underline">
                        View all matches
                    </Link>
                </div>
                {sections.recently_joined.length > 0 ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {sections.recently_joined.map((m) => (
                            <Card key={m.code} className="overflow-hidden">
                                <div className="from-primary/10 to-secondary/10 flex h-32 items-center justify-center bg-gradient-to-br">
                                    <span className="text-primary/40 text-3xl font-semibold">{m.display_name.charAt(0)}</span>
                                </div>
                                <CardContent className="p-4">
                                    <div className="flex items-center gap-1.5">
                                        <p className="font-medium">{m.display_name}</p>
                                        {m.is_verified && <BadgeCheck className="text-secondary size-4" />}
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        {m.age ? `${m.age} yrs` : ''} · {m.code}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="text-muted-foreground py-12 text-center text-sm">
                            No matches to show yet. Complete your profile to get personalised recommendations.
                        </CardContent>
                    </Card>
                )}
            </div>
        </MemberLayout>
    );
}

function StatusBadge({ status, verified }: { status: string | null; verified: boolean }) {
    if (verified) return <Badge className="bg-secondary text-secondary-foreground">Verified</Badge>;
    return <Badge variant="secondary">{(status ?? 'draft').replace('_', ' ')}</Badge>;
}

function VerifyBanner({ icon: Icon, title, body, href, cta }: { icon: typeof Mail; title: string; body: string; href: string; cta: string }) {
    return (
        <div className="border-accent/30 bg-accent/10 flex items-center gap-4 rounded-xl border p-4">
            <div className="bg-accent/20 text-accent-foreground flex size-10 items-center justify-center rounded-lg">
                <Icon className="size-5" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold">{title}</p>
                <p className="text-muted-foreground truncate text-xs">{body}</p>
            </div>
            <Button size="sm" variant="outline" asChild>
                <Link href={href} method={cta === 'Resend link' ? 'get' : 'get'}>
                    {cta}
                </Link>
            </Button>
        </div>
    );
}
