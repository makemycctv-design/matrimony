import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileText, Receipt } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import MemberLayout from '@/layouts/member-layout';
import { formatDate, formatPaise } from '@/lib/format';

interface Row {
    uuid: string;
    plan: string | null;
    amount: number;
    status: string;
    method: string | null;
    created_at: string | null;
    paid_at: string | null;
    invoice: { uuid: string; number: string } | null;
    refundable: boolean;
    refunded: number;
}

interface Props {
    payments: { data: Row[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
}

const STATUS: Record<string, string> = {
    captured: 'bg-secondary text-secondary-foreground',
    created: 'bg-muted text-muted-foreground',
    failed: 'bg-destructive/15 text-destructive',
    refunded: 'bg-accent/20 text-accent-foreground',
    partially_refunded: 'bg-accent/20 text-accent-foreground',
};

export default function PaymentHistory({ payments }: Props) {
    return (
        <MemberLayout title="Billing history">
            <Head title="Billing history" />

            <Card>
                <CardContent className="px-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                    <th className="px-4 py-3 font-medium">Plan</th>
                                    <th className="px-4 py-3 font-medium">Amount</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Date</th>
                                    <th className="px-4 py-3 font-medium">Invoice</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {payments.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="text-muted-foreground px-4 py-12 text-center">
                                            <Receipt className="text-primary/40 mx-auto mb-2 size-8" /> No payments yet.
                                        </td>
                                    </tr>
                                ) : (
                                    payments.data.map((p) => (
                                        <tr key={p.uuid} className="hover:bg-muted/40 border-b last:border-0">
                                            <td className="px-4 py-3 font-medium">{p.plan ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                {formatPaise(p.amount)}
                                                {p.refunded > 0 && (
                                                    <span className="text-muted-foreground block text-xs">− {formatPaise(p.refunded)} refunded</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={STATUS[p.status] ?? ''}>{p.status.replace('_', ' ')}</Badge>
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">{formatDate(p.paid_at ?? p.created_at)}</td>
                                            <td className="px-4 py-3">
                                                {p.invoice ? (
                                                    <Link
                                                        href={route('member.billing.invoice', { invoice: p.invoice.uuid })}
                                                        className="text-primary inline-flex items-center gap-1 hover:underline"
                                                    >
                                                        <FileText className="size-4" /> {p.invoice.number}
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">{p.refundable && <RefundDialog uuid={p.uuid} />}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {payments.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {payments.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                            className={`rounded-md px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'} ${!link.url ? 'opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </MemberLayout>
    );
}

function RefundDialog({ uuid }: { uuid: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ reason: '' });
    const submit = () => post(route('member.billing.refund', { payment: uuid }), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Request refund
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Request a refund</DialogTitle>
                <DialogDescription>Tell us why you'd like a refund. Our team will review your request.</DialogDescription>
                <div className="grid gap-2">
                    <Label>Reason</Label>
                    <Textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} />
                    {errors.reason && <p className="text-destructive text-sm">{errors.reason}</p>}
                </div>
                <DialogFooter>
                    <Button onClick={submit} disabled={processing || data.reason.length < 5}>
                        Submit request
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
