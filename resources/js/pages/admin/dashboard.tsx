import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, BadgeCheck, Clock, IndianRupee, TrendingUp, UserPlus, Users, XCircle } from 'lucide-react';
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { formatCurrency, formatDate, formatNumber } from '@/lib/format';

interface AdminDashboardProps {
    metrics: {
        total_users: number;
        new_today: number;
        new_this_month: number;
        verified_profiles: number;
        pending_reviews: number;
        suspended: number;
        active_subscriptions: number;
        revenue_today: number;
        revenue_month: number;
        failed_payments: number;
        open_reports: number;
    };
    registrationTrend: { date: string; count: number }[];
    recentRegistrations: { uuid: string; name: string; email: string; status: string | null; created_at: string | null }[];
    recentAdminActions: { action: string; description: string | null; by: string | null; at: string | null }[];
}

export default function AdminDashboard({ metrics, registrationTrend, recentRegistrations, recentAdminActions }: AdminDashboardProps) {
    const cards = [
        { label: 'Total users', value: formatNumber(metrics.total_users), icon: Users, hint: `${metrics.new_this_month} this month` },
        { label: 'New today', value: formatNumber(metrics.new_today), icon: UserPlus, hint: 'Registrations' },
        { label: 'Verified profiles', value: formatNumber(metrics.verified_profiles), icon: BadgeCheck, hint: 'Trust badge' },
        {
            label: 'Pending reviews',
            value: formatNumber(metrics.pending_reviews),
            icon: Clock,
            hint: 'Awaiting moderation',
            accent: metrics.pending_reviews > 0,
        },
        {
            label: 'Revenue (month)',
            value: formatCurrency(metrics.revenue_month),
            icon: IndianRupee,
            hint: `${formatCurrency(metrics.revenue_today)} today`,
        },
        { label: 'Active subscriptions', value: formatNumber(metrics.active_subscriptions), icon: TrendingUp, hint: 'Paying members' },
        { label: 'Failed payments', value: formatNumber(metrics.failed_payments), icon: XCircle, hint: 'Needs attention' },
        {
            label: 'Open reports',
            value: formatNumber(metrics.open_reports),
            icon: AlertTriangle,
            hint: 'Abuse queue',
            accent: metrics.open_reports > 0,
        },
    ];

    return (
        <AdminLayout title="Dashboard" breadcrumbs={[{ title: 'Admin', href: route('admin.dashboard') }]}>
            <Head title="Admin dashboard" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((c) => (
                    <Card key={c.label}>
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <span
                                    className={`flex size-10 items-center justify-center rounded-lg ${c.accent ? 'bg-accent/20 text-accent-foreground' : 'bg-primary/10 text-primary'}`}
                                >
                                    <c.icon className="size-5" />
                                </span>
                            </div>
                            <p className="mt-3 text-2xl font-bold">{c.value}</p>
                            <p className="text-sm font-medium">{c.label}</p>
                            <p className="text-muted-foreground text-xs">{c.hint}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Registrations · last 14 days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-72 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={registrationTrend} margin={{ left: -20, right: 8, top: 8 }}>
                                    <defs>
                                        <linearGradient id="reg" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.35} />
                                            <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                                    <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke="var(--color-muted-foreground)" />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} stroke="var(--color-muted-foreground)" />
                                    <Tooltip
                                        contentStyle={{
                                            background: 'var(--color-popover)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: 8,
                                            fontSize: 12,
                                        }}
                                    />
                                    <Area type="monotone" dataKey="count" stroke="var(--color-primary)" strokeWidth={2} fill="url(#reg)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Recent admin actions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentAdminActions.length > 0 ? (
                            <ul className="space-y-4">
                                {recentAdminActions.map((a, i) => (
                                    <li key={i} className="text-sm">
                                        <p className="font-medium">{a.action}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {a.by ?? 'System'} · {formatDate(a.at)}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-muted-foreground py-8 text-center text-sm">No admin actions logged yet.</p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="text-base">Recent registrations</CardTitle>
                </CardHeader>
                <CardContent className="px-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                    <th className="px-6 py-3 font-medium">Name</th>
                                    <th className="px-6 py-3 font-medium">Email</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                    <th className="px-6 py-3 font-medium">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentRegistrations.length > 0 ? (
                                    recentRegistrations.map((u) => (
                                        <tr key={u.uuid} className="hover:bg-muted/40 border-b last:border-0">
                                            <td className="px-6 py-3 font-medium">{u.name}</td>
                                            <td className="text-muted-foreground px-6 py-3">{u.email}</td>
                                            <td className="px-6 py-3">
                                                <Badge variant={u.status === 'active' ? 'default' : 'secondary'}>{u.status}</Badge>
                                            </td>
                                            <td className="text-muted-foreground px-6 py-3">{formatDate(u.created_at)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={4} className="text-muted-foreground px-6 py-8 text-center">
                                            No registrations yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-6 pt-4">
                        <Link href="/admin/users" className="text-primary text-sm hover:underline">
                            View all users →
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
