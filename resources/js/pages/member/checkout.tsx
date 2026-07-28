import { Head, router } from '@inertiajs/react';
import { Loader2, Lock, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatPaise } from '@/lib/format';
import { CheckoutBreakdown, Plan } from '@/types/billing';

interface Props {
    plan: Plan;
    payment: { uuid: string };
    order: { id: string; key: string; amount: number; currency: string };
    breakdown: CheckoutBreakdown;
    prefill: { name: string; email: string; contact: string | null };
    simulate: boolean;
}

declare global {
    interface Window {
        Razorpay?: new (options: Record<string, unknown>) => { open: () => void };
    }
}

export default function Checkout({ plan, payment, order, breakdown, prefill, simulate }: Props) {
    const [loading, setLoading] = useState(false);

    // Load Razorpay Checkout script once (live mode only).
    useEffect(() => {
        if (simulate || window.Razorpay) return;
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        document.body.appendChild(script);
        return () => {
            document.body.removeChild(script);
        };
    }, [simulate]);

    const openRazorpay = () => {
        if (!window.Razorpay) return;
        const rzp = new window.Razorpay({
            key: order.key,
            order_id: order.id,
            amount: order.amount,
            currency: order.currency,
            name: 'Vivaaha',
            description: `${plan.name} membership`,
            prefill: { name: prefill.name, email: prefill.email, contact: prefill.contact ?? '' },
            theme: { color: '#be123c' },
            handler: (response: Record<string, string>) => {
                router.post(route('member.subscription.verify'), {
                    payment: payment.uuid,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                });
            },
            modal: {
                ondismiss: () => router.post(route('member.subscription.failed', { payment: payment.uuid })),
            },
        });
        rzp.open();
    };

    const pay = () => {
        setLoading(true);
        if (simulate) {
            router.post(route('member.subscription.simulate', { payment: payment.uuid }));
            return;
        }
        openRazorpay();
        setLoading(false);
    };

    return (
        <MemberLayout title="Checkout">
            <Head title="Checkout" />

            <div className="mx-auto grid max-w-3xl gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Order summary</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="font-medium">{plan.name} membership</span>
                            <span>{formatPaise(breakdown.subtotal_paise)}</span>
                        </div>
                        {breakdown.discount_paise > 0 && (
                            <div className="text-secondary flex justify-between text-sm">
                                <span>Discount</span>
                                <span>− {formatPaise(breakdown.discount_paise)}</span>
                            </div>
                        )}
                        <div className="text-muted-foreground flex justify-between text-sm">
                            <span>GST ({breakdown.gst_percent}%)</span>
                            <span>{formatPaise(breakdown.tax_paise)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-3 text-lg font-bold">
                            <span>Total</span>
                            <span>{formatPaise(breakdown.total_paise)}</span>
                        </div>
                        <p className="text-muted-foreground text-xs">Valid for {plan.duration_days} days.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Payment</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="bg-secondary/5 text-muted-foreground flex items-start gap-2 rounded-lg p-3 text-sm">
                            <ShieldCheck className="text-secondary mt-0.5 size-4 shrink-0" />
                            Secured by Razorpay. Supports UPI, cards, net banking and wallets.
                        </div>

                        {simulate && (
                            <div className="border-accent/40 bg-accent/10 text-accent-foreground rounded-lg border border-dashed p-3 text-xs">
                                Test mode: no live payment gateway is configured, so this completes a simulated payment.
                            </div>
                        )}

                        <Button className="w-full" size="lg" onClick={pay} disabled={loading}>
                            {loading ? <Loader2 className="size-4 animate-spin" /> : <Lock className="size-4" />}
                            Pay {formatPaise(breakdown.total_paise)}
                        </Button>

                        {simulate && (
                            <Button
                                variant="outline"
                                className="w-full"
                                onClick={() => router.post(route('member.subscription.simulate', { payment: payment.uuid }), { fail: true })}
                            >
                                Simulate failed payment
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}
