import { Head, useForm } from '@inertiajs/react';
import { BadgeCheck, ShieldAlert } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate, formatPaise } from '@/lib/format';

interface Payment {
    uuid: string;
    user: string | null;
    email: string | null;
    plan: string | null;
    status: string;
    method: string | null;
    razorpay_order_id: string | null;
    razorpay_payment_id: string | null;
    signature_verified: boolean;
    subtotal_paise: number;
    discount_paise: number;
    tax_paise: number;
    amount_paise: number;
    refunded_paise: number;
    refundable_paise: number;
    coupon: string | null;
    invoice: string | null;
    created_at: string | null;
    paid_at: string | null;
    refunds: { uuid: string; amount: number; status: string; reason: string | null; requested_by: string | null }[];
}

export default function AdminPaymentShow({ payment, canRefund }: { payment: Payment; canRefund: boolean }) {
    return (
        <AdminLayout
            title={`Payment ${payment.uuid.slice(0, 8)}`}
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Payments', href: route('admin.payments.index') },
                { title: 'Detail', href: '#' },
            ]}
        >
            <Head title="Payment detail" />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-2xl font-bold">{formatPaise(payment.amount_paise)}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {payment.plan} · {payment.user} ({payment.email})
                                    </p>
                                </div>
                                <Badge className={payment.status === 'captured' ? 'bg-secondary text-secondary-foreground' : ''}>
                                    {payment.status.replace('_', ' ')}
                                </Badge>
                            </div>
                            <div className="mt-3 flex items-center gap-2 text-sm">
                                {payment.signature_verified ? (
                                    <span className="text-secondary flex items-center gap-1">
                                        <BadgeCheck className="size-4" /> Signature verified
                                    </span>
                                ) : (
                                    <span className="text-destructive flex items-center gap-1">
                                        <ShieldAlert className="size-4" /> Not verified
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Row label="Subtotal" value={formatPaise(payment.subtotal_paise)} />
                            {payment.discount_paise > 0 && (
                                <Row
                                    label={`Discount ${payment.coupon ? '(' + payment.coupon + ')' : ''}`}
                                    value={'− ' + formatPaise(payment.discount_paise)}
                                />
                            )}
                            <Row label="Tax" value={formatPaise(payment.tax_paise)} />
                            <Row label="Total" value={formatPaise(payment.amount_paise)} bold />
                            {payment.refunded_paise > 0 && <Row label="Refunded" value={formatPaise(payment.refunded_paise)} />}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Gateway</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm sm:grid-cols-2">
                            <Row label="Order ID" value={payment.razorpay_order_id ?? '—'} />
                            <Row label="Payment ID" value={payment.razorpay_payment_id ?? '—'} />
                            <Row label="Method" value={payment.method ?? '—'} />
                            <Row label="Invoice" value={payment.invoice ?? '—'} />
                            <Row label="Created" value={formatDate(payment.created_at)} />
                            <Row label="Paid" value={formatDate(payment.paid_at)} />
                        </CardContent>
                    </Card>

                    {payment.refunds.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Refunds</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {payment.refunds.map((r) => (
                                    <div key={r.uuid} className="flex items-center justify-between rounded-lg border p-3">
                                        <div>
                                            {formatPaise(r.amount)} <span className="text-muted-foreground text-xs">· {r.reason}</span>
                                        </div>
                                        <Badge>{r.status}</Badge>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {canRefund && payment.refundable_paise > 0 && (
                    <div>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Actions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <RefundDialog uuid={payment.uuid} max={payment.refundable_paise} />
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function Row({ label, value, bold }: { label: string; value: string; bold?: boolean }) {
    return (
        <div className={`flex justify-between ${bold ? 'border-t pt-2 font-semibold' : ''}`}>
            <span className="text-muted-foreground">{label}</span>
            <span className="font-mono text-xs">{value}</span>
        </div>
    );
}

function RefundDialog({ uuid, max }: { uuid: string; max: number }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm<{ amount: string; reason: string }>({ amount: String(max / 100), reason: '' });
    const submit = () => post(route('admin.payments.refund', { payment: uuid }), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full">
                    Create refund
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Create refund</DialogTitle>
                <DialogDescription>
                    Refundable balance: {formatPaise(max)}. This creates and approves a refund; process it from the refunds queue.
                </DialogDescription>
                <div className="space-y-3">
                    <div className="grid gap-2">
                        <Label>Amount (₹)</Label>
                        <Input type="number" value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Reason</Label>
                        <Textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} />
                    </div>
                </div>
                <DialogFooter>
                    <Button onClick={submit} disabled={processing || data.reason.length < 3}>
                        Create refund
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
