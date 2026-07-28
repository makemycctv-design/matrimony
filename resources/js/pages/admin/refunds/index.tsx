import { Head, router } from '@inertiajs/react';
import { Check, RotateCcw, X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate, formatPaise } from '@/lib/format';

interface Row {
    uuid: string;
    amount: number;
    status: string;
    reason: string | null;
    requested_by: string | null;
    payment_uuid: string | null;
    created_at: string | null;
}

interface Props {
    refunds: { data: Row[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
    filters: { status: string };
    statuses: { value: string; label: string }[];
    canManage: boolean;
}

const STATUS: Record<string, string> = {
    requested: 'bg-accent/20 text-accent-foreground',
    approved: 'bg-primary/10 text-primary',
    processed: 'bg-secondary text-secondary-foreground',
    rejected: 'bg-destructive/15 text-destructive',
    failed: 'bg-destructive/15 text-destructive',
};

export default function AdminRefunds({ refunds, filters, statuses, canManage }: Props) {
    const act = (uuid: string, action: string) => router.post(route(`admin.refunds.${action}`, { refund: uuid }), {}, { preserveScroll: true });

    return (
        <AdminLayout
            title="Refunds"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Refunds', href: route('admin.refunds.index') },
            ]}
        >
            <Head title="Refunds" />

            <Card className="mb-4">
                <CardContent className="flex items-center gap-3 p-4">
                    <Select
                        value={filters.status}
                        onValueChange={(v) => router.get(route('admin.refunds.index'), { status: v }, { preserveState: true, preserveScroll: true })}
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            {statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <div className="space-y-3">
                {refunds.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-12 text-center text-sm">No refunds in this view.</CardContent>
                    </Card>
                ) : (
                    refunds.data.map((r) => (
                        <Card key={r.uuid}>
                            <CardContent className="flex flex-wrap items-center justify-between gap-4 p-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-lg font-bold">{formatPaise(r.amount)}</span>
                                        <Badge className={STATUS[r.status]}>{r.status}</Badge>
                                    </div>
                                    <p className="text-muted-foreground text-sm">{r.reason ?? '—'}</p>
                                    <p className="text-muted-foreground text-xs">
                                        Requested by {r.requested_by ?? 'member'} · {formatDate(r.created_at)}
                                    </p>
                                </div>
                                {canManage && (
                                    <div className="flex gap-2">
                                        {r.status === 'requested' && (
                                            <>
                                                <Button size="sm" onClick={() => act(r.uuid, 'approve')}>
                                                    <Check className="size-4" /> Approve
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="text-destructive"
                                                    onClick={() => act(r.uuid, 'reject')}
                                                >
                                                    <X className="size-4" /> Reject
                                                </Button>
                                            </>
                                        )}
                                        {r.status === 'approved' && (
                                            <Button
                                                size="sm"
                                                className="bg-secondary text-secondary-foreground hover:bg-secondary/90"
                                                onClick={() => act(r.uuid, 'process')}
                                            >
                                                <RotateCcw className="size-4" /> Process refund
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>

            {refunds.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {refunds.links.map((link, i) => (
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
        </AdminLayout>
    );
}
