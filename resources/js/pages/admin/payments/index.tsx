import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate, formatPaise } from '@/lib/format';

interface Row {
    uuid: string;
    user: string | null;
    email: string | null;
    plan: string | null;
    amount: number;
    refunded: number;
    status: string;
    method: string | null;
    verified: boolean;
    created_at: string | null;
}

interface Props {
    payments: { data: Row[]; total: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
    filters: { status: string; search: string | null };
    statuses: { value: string; label: string }[];
    totals: { captured_paise: number; refunded_paise: number };
}

const STATUS: Record<string, string> = {
    captured: 'bg-secondary text-secondary-foreground',
    failed: 'bg-destructive/15 text-destructive',
    created: 'bg-muted text-muted-foreground',
    refunded: 'bg-accent/20 text-accent-foreground',
    partially_refunded: 'bg-accent/20 text-accent-foreground',
};

export default function AdminPayments({ payments, filters, statuses, totals }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyStatus = (v: string) =>
        router.get(route('admin.payments.index'), { status: v, search: search || undefined }, { preserveState: true, preserveScroll: true });
    const applySearch = () =>
        router.get(
            route('admin.payments.index'),
            { status: filters.status, search: search || undefined },
            { preserveState: true, preserveScroll: true },
        );

    return (
        <AdminLayout
            title="Payments"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Payments', href: route('admin.payments.index') },
            ]}
        >
            <Head title="Payments" />

            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardContent className="p-5">
                        <p className="text-muted-foreground text-sm">Captured revenue</p>
                        <p className="text-2xl font-bold">{formatPaise(totals.captured_paise)}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-5">
                        <p className="text-muted-foreground text-sm">Total refunded</p>
                        <p className="text-2xl font-bold">{formatPaise(totals.refunded_paise)}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="mb-4">
                <CardContent className="flex flex-wrap items-center gap-3 p-4">
                    <div className="relative min-w-56 flex-1">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            className="pl-9"
                            placeholder="Search payment/order id or email…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applySearch()}
                        />
                    </div>
                    <Select value={filters.status} onValueChange={applyStatus}>
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <span className="text-muted-foreground text-sm">{payments.total} payments</span>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="overflow-x-auto px-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                <th className="px-4 py-3 font-medium">Member</th>
                                <th className="px-4 py-3 font-medium">Plan</th>
                                <th className="px-4 py-3 font-medium">Amount</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Date</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {payments.data.map((p) => (
                                <tr key={p.uuid} className="hover:bg-muted/40 border-b last:border-0">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{p.user}</div>
                                        <div className="text-muted-foreground text-xs">{p.email}</div>
                                    </td>
                                    <td className="px-4 py-3">{p.plan ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        {formatPaise(p.amount)}
                                        {p.refunded > 0 && <span className="text-muted-foreground block text-xs">−{formatPaise(p.refunded)}</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={STATUS[p.status] ?? ''}>{p.status.replace('_', ' ')}</Badge>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">{formatDate(p.created_at)}</td>
                                    <td className="px-4 py-3 text-right">
                                        <Button size="sm" variant="outline" asChild>
                                            <Link href={route('admin.payments.show', { payment: p.uuid })}>View</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            {payments.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {payments.links.map((link, i) => (
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
