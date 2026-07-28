import { Head, Link, router, useForm } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';

interface ReportRow {
    uuid: string;
    reason: string;
    reason_label: string;
    details: string | null;
    status: string;
    reporter_code: string | null;
    reported_code: string | null;
    reported_uuid: string | null;
    handler: string | null;
    created_at: string | null;
    resolution_notes: string | null;
}

interface Paginated<T> {
    data: T[];
    total: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    reports: Paginated<ReportRow>;
    filters: { status: string };
    statuses: { value: string; label: string }[];
}

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-accent/20 text-accent-foreground',
    reviewing: 'bg-primary/10 text-primary',
    actioned: 'bg-secondary text-secondary-foreground',
    dismissed: 'bg-muted text-muted-foreground',
};

export default function AdminReports({ reports, filters, statuses }: Props) {
    return (
        <AdminLayout
            title="Abuse reports"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Reports', href: route('admin.reports.index') },
            ]}
        >
            <Head title="Abuse reports" />

            <Card className="mb-4">
                <CardContent className="flex flex-wrap items-center gap-3 p-4">
                    <Select
                        value={filters.status}
                        onValueChange={(v) => router.get(route('admin.reports.index'), { status: v }, { preserveState: true, preserveScroll: true })}
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
                    <span className="text-muted-foreground text-sm">{reports.total} reports</span>
                </CardContent>
            </Card>

            {reports.data.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                        <ShieldAlert className="text-primary/40 size-8" /> No reports in this view.
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-3">
                    {reports.data.map((r) => (
                        <Card key={r.uuid}>
                            <CardContent className="flex flex-wrap items-start justify-between gap-4 p-4">
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <Badge className={STATUS_COLORS[r.status]}>{r.status}</Badge>
                                        <span className="font-medium">{r.reason_label}</span>
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Reported:{' '}
                                        {r.reported_uuid ? (
                                            <Link
                                                href={route('admin.profiles.show', { profile: r.reported_uuid })}
                                                className="text-primary hover:underline"
                                            >
                                                {r.reported_code}
                                            </Link>
                                        ) : (
                                            r.reported_code
                                        )}
                                        {' · '}by {r.reporter_code} · {formatDate(r.created_at)}
                                    </p>
                                    {r.details && <p className="bg-muted/40 mt-2 rounded-lg px-3 py-2 text-sm">{r.details}</p>}
                                    {r.resolution_notes && (
                                        <p className="text-muted-foreground mt-2 text-xs">
                                            Resolution: {r.resolution_notes} {r.handler ? `(${r.handler})` : ''}
                                        </p>
                                    )}
                                </div>
                                <HandleDialog report={r} />
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {reports.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {reports.links.map((link, i) => (
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

function HandleDialog({ report }: { report: ReportRow }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm<{ status: string; resolution_notes: string; suspend_reported: boolean }>({
        status: 'reviewing',
        resolution_notes: report.resolution_notes ?? '',
        suspend_reported: false,
    });

    const submit = () => post(route('admin.reports.update', { report: report.uuid }), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Handle
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Handle report</DialogTitle>
                <DialogDescription>Set an outcome for this report. Actioning can optionally suspend the reported profile.</DialogDescription>
                <div className="space-y-3">
                    <div className="grid gap-2">
                        <Label>Status</Label>
                        <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="reviewing">Reviewing</SelectItem>
                                <SelectItem value="actioned">Action taken</SelectItem>
                                <SelectItem value="dismissed">Dismiss</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-2">
                        <Label>Resolution notes</Label>
                        <Textarea value={data.resolution_notes} onChange={(e) => setData('resolution_notes', e.target.value)} />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={data.suspend_reported} onClick={() => setData('suspend_reported', !data.suspend_reported)} />
                        Suspend the reported profile
                    </label>
                </div>
                <DialogFooter>
                    <Button onClick={submit} disabled={processing}>
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
