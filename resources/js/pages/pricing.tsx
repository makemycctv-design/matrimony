import { Head, Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Check, Minus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { formatCurrency } from '@/lib/format';
import { SharedData } from '@/types';
import { Plan } from '@/types/billing';

interface Props {
    plans: Plan[];
    seo: { title: string; description: string };
}

const COMPARISON: { key: keyof Plan['limits']; label: string }[] = [
    { key: 'advanced_search', label: 'Advanced search filters' },
    { key: 'contact_view_access', label: 'View contact details' },
    { key: 'messaging_access', label: 'Messaging after mutual interest' },
    { key: 'profile_highlight', label: 'Profile highlight' },
    { key: 'profile_boost', label: 'Profile boost' },
    { key: 'verification_priority', label: 'Priority verification' },
];

export default function Pricing({ plans, seo }: Props) {
    const { auth } = usePage<SharedData>().props;
    const ctaHref = auth.user ? route('member.subscription') : route('register');

    return (
        <PublicLayout>
            <Head title={seo.title}>
                <meta name="description" content={seo.description} />
            </Head>

            <section className="from-primary/5 to-background bg-gradient-to-b py-16">
                <div className="mx-auto max-w-3xl px-4 text-center">
                    <h1 className="text-4xl font-bold tracking-tight">Membership plans</h1>
                    <p className="text-muted-foreground mt-3">
                        Start free and upgrade when you're ready to connect. GST invoices included. Prices shown are exclusive of{' '}
                        {plans[0]?.gst_percent ?? 18}% GST.
                    </p>
                </div>
            </section>

            <section className="pb-16">
                <div className="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
                    {plans.map((plan) => (
                        <Card
                            key={plan.uuid}
                            className={plan.is_featured ? 'border-primary ring-primary/20 relative shadow-lg ring-1' : 'border-border/60'}
                        >
                            {plan.is_featured && (
                                <span className="bg-primary text-primary-foreground absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-1 text-xs font-semibold">
                                    Most popular
                                </span>
                            )}
                            <CardContent className="flex h-full flex-col gap-5 p-6">
                                <div>
                                    <p className="text-muted-foreground text-sm font-medium">{plan.name}</p>
                                    <p className="mt-1 text-3xl font-bold">
                                        {plan.is_free ? 'Free' : formatCurrency(plan.price)}
                                        {!plan.is_free && <span className="text-muted-foreground text-sm font-normal"> /{plan.duration_days}d</span>}
                                    </p>
                                    {plan.description && <p className="text-muted-foreground mt-2 text-sm">{plan.description}</p>}
                                </div>
                                <ul className="flex-1 space-y-2 text-sm">
                                    {plan.features.map((f) => (
                                        <li key={f} className="flex items-start gap-2">
                                            <BadgeCheck className="text-secondary mt-0.5 size-4 shrink-0" /> {f}
                                        </li>
                                    ))}
                                </ul>
                                <Button className="w-full" variant={plan.is_featured ? 'default' : 'outline'} asChild>
                                    <Link href={ctaHref}>{plan.is_free ? 'Get started' : 'Choose plan'}</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </section>

            {/* Comparison matrix */}
            <section className="border-border/60 bg-muted/20 border-t py-16">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <h2 className="mb-8 text-center text-2xl font-bold">Compare features</h2>
                    <div className="bg-card overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="p-4 text-left font-medium">Feature</th>
                                    {plans.map((p) => (
                                        <th key={p.uuid} className="p-4 text-center font-medium">
                                            {p.name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {COMPARISON.map((row) => (
                                    <tr key={row.key} className="border-b last:border-0">
                                        <td className="text-muted-foreground p-4">{row.label}</td>
                                        {plans.map((p) => (
                                            <td key={p.uuid} className="p-4 text-center">
                                                {p.limits[row.key] ? (
                                                    <Check className="text-secondary mx-auto size-4" />
                                                ) : (
                                                    <Minus className="text-muted-foreground/40 mx-auto size-4" />
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                                <tr>
                                    <td className="text-muted-foreground p-4">Interests per day</td>
                                    {plans.map((p) => (
                                        <td key={p.uuid} className="p-4 text-center">
                                            {p.limits.max_interests_per_day ?? 'Unlimited'}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
