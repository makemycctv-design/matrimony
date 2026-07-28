import { Head, Link, usePage } from '@inertiajs/react';
import { BadgeCheck, HeartHandshake, Lock, MessagesSquare, Search, ShieldCheck, Sparkles, UserPlus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { formatNumber } from '@/lib/format';
import { SharedData } from '@/types';

interface WelcomeProps {
    stats: { members: number; verified: number };
    seo: { title: string; description: string };
}

export default function Welcome({ stats, seo }: WelcomeProps) {
    const { t } = useTranslations();
    const { name } = usePage<SharedData>().props;

    const features = [
        {
            icon: BadgeCheck,
            title: t('landing.trust_verified'),
            body: 'Every profile can be identity and document verified by our team before it earns a trust badge.',
        },
        {
            icon: Lock,
            title: t('landing.trust_private'),
            body: 'You decide who sees your photos, contact details, and horoscope — nothing is public by default.',
        },
        {
            icon: Sparkles,
            title: 'Explainable matches',
            body: 'See exactly why a profile is suggested: age, community, language, and location alignment.',
        },
        {
            icon: ShieldCheck,
            title: t('landing.trust_secure'),
            body: 'Bank-grade encryption, OTP verification, and strict abuse moderation keep you safe.',
        },
    ];

    const steps = [
        { icon: UserPlus, title: 'Create your profile', body: 'Register free with your mobile and email, then complete a guided profile.' },
        { icon: Search, title: 'Discover matches', body: 'Search and receive compatibility-scored recommendations tuned to your preferences.' },
        { icon: MessagesSquare, title: 'Connect respectfully', body: 'Express interest and start a conversation only after mutual consent.' },
    ];

    const plans = [
        { name: 'Free', price: '₹0', tag: 'Get started', perks: ['Create & verify profile', 'Daily match suggestions', 'Send limited interests'] },
        {
            name: 'Premium',
            price: '₹1,999',
            tag: 'Most popular',
            highlight: true,
            perks: ['Unlimited interests', 'View contact details', 'Chat after mutual interest', 'Advanced filters'],
        },
        {
            name: 'VIP',
            price: '₹4,999',
            tag: 'Priority',
            perks: ['Everything in Premium', 'Profile boost & highlight', 'Priority verification', 'Relationship manager'],
        },
    ];

    return (
        <PublicLayout>
            <Head title={seo.title}>
                <meta name="description" content={seo.description} />
                <meta property="og:title" content={seo.title} />
                <meta property="og:description" content={seo.description} />
                <meta property="og:type" content="website" />
            </Head>

            {/* Hero */}
            <section className="relative overflow-hidden">
                <div className="from-primary/5 via-background to-background absolute inset-0 -z-10 bg-gradient-to-b" />
                <div className="mx-auto grid w-full max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
                    <div className="space-y-6 text-center lg:text-left">
                        <span className="border-primary/20 bg-primary/5 text-primary inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium">
                            <ShieldCheck className="size-3.5" /> {t('landing.trust_verified')} · {t('landing.trust_private')}
                        </span>
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">{t('app.tagline')}</h1>
                        <p className="text-muted-foreground mx-auto max-w-xl text-lg lg:mx-0">{t('app.description')}</p>
                        <div className="flex flex-col items-center gap-3 sm:flex-row lg:justify-start">
                            <Button size="lg" className="w-full sm:w-auto" asChild>
                                <Link href={route('register')}>{t('landing.hero_cta')}</Link>
                            </Button>
                            <Button size="lg" variant="outline" className="w-full sm:w-auto" asChild>
                                <a href="#pricing">{t('landing.hero_secondary')}</a>
                            </Button>
                        </div>
                        <div className="flex items-center justify-center gap-8 pt-4 lg:justify-start">
                            <Stat value={formatNumber(Math.max(stats.members, 0))} label="Members" />
                            <Stat value={formatNumber(Math.max(stats.verified, 0))} label="Verified" />
                            <Stat value="4.8★" label="Trust rating" />
                        </div>
                    </div>

                    <div className="relative mx-auto w-full max-w-md">
                        <div className="bg-card rounded-3xl border p-6 shadow-xl">
                            <div className="flex items-center gap-4">
                                <div className="bg-primary/10 text-primary flex size-16 items-center justify-center rounded-2xl">
                                    <HeartHandshake className="size-8" />
                                </div>
                                <div>
                                    <p className="font-semibold">Your match, explained</p>
                                    <p className="text-muted-foreground text-sm">Compatibility you can understand</p>
                                </div>
                            </div>
                            <div className="mt-6 space-y-3">
                                {['Matches your age preference', 'Same mother tongue', 'Preferred location', 'Verified profile'].map((reason) => (
                                    <div key={reason} className="bg-muted/50 flex items-center gap-3 rounded-lg px-3 py-2 text-sm">
                                        <BadgeCheck className="text-secondary size-4" /> {reason}
                                    </div>
                                ))}
                            </div>
                            <div className="bg-primary/5 text-muted-foreground mt-6 rounded-lg p-3 text-center text-xs">
                                Compatibility scores are guidance only and never guarantee marital outcomes.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Features */}
            <section id="how-it-works" className="border-border/60 bg-muted/20 border-t py-16 lg:py-24">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight">{t('landing.features_title')}</h2>
                        <p className="text-muted-foreground mt-3">Trust, privacy, and respect are built into every part of {name}.</p>
                    </div>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {features.map((f) => (
                            <Card key={f.title} className="border-border/60">
                                <CardContent className="space-y-3 p-6">
                                    <div className="bg-primary/10 text-primary flex size-11 items-center justify-center rounded-xl">
                                        <f.icon className="size-5" />
                                    </div>
                                    <h3 className="font-semibold">{f.title}</h3>
                                    <p className="text-muted-foreground text-sm">{f.body}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <div className="mt-16 grid gap-8 md:grid-cols-3">
                        {steps.map((s, i) => (
                            <div key={s.title} className="bg-card relative rounded-2xl border p-6">
                                <span className="bg-primary text-primary-foreground absolute -top-3 left-6 flex size-7 items-center justify-center rounded-full text-xs font-bold">
                                    {i + 1}
                                </span>
                                <s.icon className="text-secondary size-6" />
                                <h3 className="mt-4 font-semibold">{s.title}</h3>
                                <p className="text-muted-foreground mt-2 text-sm">{s.body}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing */}
            <section id="pricing" className="py-16 lg:py-24">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight">{t('landing.pricing_title')}</h2>
                        <p className="text-muted-foreground mt-3">Start free. Upgrade when you are ready to connect. GST invoices included.</p>
                    </div>
                    <div className="mt-12 grid gap-6 lg:grid-cols-3">
                        {plans.map((plan) => (
                            <Card
                                key={plan.name}
                                className={plan.highlight ? 'border-primary ring-primary/20 relative shadow-lg ring-1' : 'border-border/60'}
                            >
                                {plan.highlight && (
                                    <span className="bg-primary text-primary-foreground absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-1 text-xs font-semibold">
                                        {plan.tag}
                                    </span>
                                )}
                                <CardContent className="space-y-5 p-6">
                                    <div>
                                        <p className="text-muted-foreground text-sm font-medium">{plan.name}</p>
                                        <p className="mt-1 text-3xl font-bold">
                                            {plan.price}
                                            <span className="text-muted-foreground text-sm font-normal"> /3 months</span>
                                        </p>
                                    </div>
                                    <ul className="space-y-2 text-sm">
                                        {plan.perks.map((perk) => (
                                            <li key={perk} className="flex items-center gap-2">
                                                <BadgeCheck className="text-secondary size-4" /> {perk}
                                            </li>
                                        ))}
                                    </ul>
                                    <Button className="w-full" variant={plan.highlight ? 'default' : 'outline'} asChild>
                                        <Link href={route('register')}>{t('landing.hero_cta')}</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    <p className="text-muted-foreground mt-6 text-center text-xs">
                        Indicative pricing shown. Live plans and secure Razorpay checkout arrive in the billing module.
                    </p>
                </div>
            </section>

            {/* CTA */}
            <section
                id="stories"
                className="border-border/60 from-primary to-secondary text-primary-foreground border-t bg-gradient-to-br py-16 lg:py-20"
            >
                <div className="mx-auto w-full max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold tracking-tight">Ready to begin your journey?</h2>
                    <p className="text-primary-foreground/90 mx-auto mt-3 max-w-xl">
                        Join thousands of families who trust {name} to find a compatible life partner — respectfully and safely.
                    </p>
                    <Button size="lg" variant="secondary" className="text-primary mt-6 bg-white hover:bg-white/90" asChild>
                        <Link href={route('register')}>{t('landing.hero_cta')}</Link>
                    </Button>
                </div>
            </section>
        </PublicLayout>
    );
}

function Stat({ value, label }: { value: string; label: string }) {
    return (
        <div className="text-center lg:text-left">
            <p className="text-2xl font-bold">{value}</p>
            <p className="text-muted-foreground text-xs">{label}</p>
        </div>
    );
}
