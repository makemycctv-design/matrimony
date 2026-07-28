import { Head, Link, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Crown, Loader2, Receipt } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import MemberLayout from '@/layouts/member-layout';
import { formatCurrency, formatDate } from '@/lib/format';
import { Plan } from '@/types/billing';

interface Props {
    plans: Plan[];
    current: { uuid: string; plan: string; status: string; ends_at: string | null; auto_renew: boolean } | null;
    entitlements: Record<string, unknown>;
}

export default function Subscription({ plans, current }: Props) {
    const [coupon, setCoupon] = useState('');
    const { processing } = useForm();

    const subscribe = (plan: Plan) => {
        router.post(route('member.subscription.checkout'), { plan: plan.uuid, coupon: coupon || undefined });
    };

    return (
        <MemberLayout title="Subscription">
            <Head title="Subscription" />

            {current && (
                <Card className="border-primary/30 bg-primary/5 mb-6">
                    <CardContent className="flex flex-wrap items-center justify-between gap-4 p-5">
                        <div className="flex items-center gap-3">
                            <Crown className="text-primary size-8" />
                            <div>
                                <p className="font-semibold">
                                    {current.plan} <Badge className="bg-secondary text-secondary-foreground ml-1">{current.status}</Badge>
                                </p>
                                <p className="text-muted-foreground text-sm">Active until {formatDate(current.ends_at)}</p>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={route('member.billing.history')}>
                                    <Receipt className="size-4" /> Billing history
                                </Link>
                            </Button>
                            {current.auto_renew && (
                                <Button variant="ghost" onClick={() => router.post(route('member.subscription.cancel'))}>
                                    Cancel auto-renew
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className="mb-4 flex items-center gap-2">
                <Input
                    placeholder="Have a coupon? Enter code"
                    value={coupon}
                    onChange={(e) => setCoupon(e.target.value.toUpperCase())}
                    className="max-w-xs"
                />
                <span className="text-muted-foreground text-xs">Applied at checkout</span>
            </div>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {plans.map((plan) => (
                    <Card key={plan.uuid} className={plan.is_featured ? 'border-primary ring-primary/20 relative shadow-lg ring-1' : ''}>
                        {plan.is_featured && (
                            <span className="bg-primary text-primary-foreground absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-1 text-xs font-semibold">
                                Popular
                            </span>
                        )}
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                {plan.name}
                                {plan.is_featured && <Crown className="text-primary size-4" />}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex h-full flex-col gap-4">
                            <p className="text-2xl font-bold">
                                {plan.is_free ? 'Free' : formatCurrency(plan.price)}
                                {!plan.is_free && <span className="text-muted-foreground text-sm font-normal"> +GST /{plan.duration_days}d</span>}
                            </p>
                            <ul className="text-muted-foreground flex-1 space-y-1.5 text-sm">
                                {plan.features.slice(0, 4).map((f) => (
                                    <li key={f} className="flex items-start gap-2">
                                        <BadgeCheck className="text-secondary mt-0.5 size-4 shrink-0" /> {f}
                                    </li>
                                ))}
                            </ul>
                            <Button
                                className="w-full"
                                variant={plan.is_featured ? 'default' : 'outline'}
                                disabled={processing || current?.plan === plan.name}
                                onClick={() => subscribe(plan)}
                            >
                                {processing && <Loader2 className="size-4 animate-spin" />}
                                {current?.plan === plan.name ? 'Current plan' : plan.is_free ? 'Activate' : 'Subscribe'}
                            </Button>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <p className="text-muted-foreground mt-6 text-center text-xs">
                Payments are processed securely by Razorpay. Compatibility scores are guidance only and never guarantee marital outcomes.
            </p>
        </MemberLayout>
    );
}
