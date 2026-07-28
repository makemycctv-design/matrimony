import { Head, Link, router } from '@inertiajs/react';
import { BadgeCheck, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';

interface ProfileRow {
    uuid: string;
    profile_code: string | null;
    name: string | null;
    email: string | null;
    gender: string | null;
    age: number | null;
    status: string | null;
    is_verified: boolean;
    completion: number;
    created_at: string | null;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    profiles: Paginated<ProfileRow>;
    filters: { status?: string; search?: string; verified?: string };
    statuses: { value: string; label: string }[];
}

const STATUS_COLORS: Record<string, string> = {
    verified: 'bg-secondary text-secondary-foreground',
    submitted: 'bg-accent/20 text-accent-foreground',
    under_review: 'bg-accent/20 text-accent-foreground',
    rejected: 'bg-destructive/15 text-destructive',
    suspended: 'bg-destructive/15 text-destructive',
    draft: 'bg-muted text-muted-foreground',
    deactivated: 'bg-muted text-muted-foreground',
};

export default function AdminProfiles({ profiles, filters, statuses }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    useEffect(() => {
        const t = setTimeout(() => {
            router.get(
                route('admin.profiles.index'),
                {
                    search: search || undefined,
                    status: status !== 'all' ? status : undefined,
                    verified: filters.verified,
                },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 350);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, status]);

    return (
        <AdminLayout
            title="Profile moderation"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Profiles', href: route('admin.profiles.index') },
            ]}
        >
            <Head title="Profile moderation" />

            <Card className="mb-4">
                <CardContent className="flex flex-wrap items-center gap-3 p-4">
                    <div className="relative min-w-56 flex-1">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input className="pl-9" placeholder="Search name, code, email…" value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="All statuses" />
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
                    <span className="text-muted-foreground text-sm">{profiles.total} profiles</span>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="px-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                    <th className="px-4 py-3 font-medium">Code</th>
                                    <th className="px-4 py-3 font-medium">Name</th>
                                    <th className="px-4 py-3 font-medium">Gender / Age</th>
                                    <th className="px-4 py-3 font-medium">Completion</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Joined</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {profiles.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="text-muted-foreground px-4 py-10 text-center">
                                            No profiles match your filters.
                                        </td>
                                    </tr>
                                ) : (
                                    profiles.data.map((p) => (
                                        <tr key={p.uuid} className="hover:bg-muted/40 border-b last:border-0">
                                            <td className="px-4 py-3 font-mono text-xs">{p.profile_code}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1.5 font-medium">
                                                    {p.name}
                                                    {p.is_verified && <BadgeCheck className="text-secondary size-4" />}
                                                </div>
                                                <div className="text-muted-foreground text-xs">{p.email}</div>
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3 capitalize">
                                                {p.gender ?? '—'} {p.age ? `· ${p.age}` : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <div className="bg-muted h-1.5 w-16 overflow-hidden rounded-full">
                                                        <div className="bg-primary h-full rounded-full" style={{ width: `${p.completion}%` }} />
                                                    </div>
                                                    <span className="text-muted-foreground text-xs">{p.completion}%</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={STATUS_COLORS[p.status ?? 'draft']}>
                                                    {(p.status ?? 'draft').replace('_', ' ')}
                                                </Badge>
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">{formatDate(p.created_at)}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link href={route('admin.profiles.show', { profile: p.uuid })}>Review</Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {profiles.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {profiles.links.map((link, i) => (
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
