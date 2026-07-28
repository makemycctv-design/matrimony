import { Head } from '@inertiajs/react';
import { CreditCard, Download, IndianRupee, RotateCcw, TrendingUp, Users, XCircle } from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { formatPaise } from '@/lib/format';

interface Props {
    metrics: {
        revenue_today: number;
        revenue_month: number;
        revenue_total: number;
        refunded_total: number;
        active_subscriptions: number;
        expired_subscriptions: number;
        failed_payments: number;
        paying_members: number;
    };
    trend: { date: string; amount: number }[];
    byPlan: { plan: string; amount: number; count: number }[];
}

const PLAN_COLORS = ['var(--color-chart-1)', 'var(--color-chart-2)', 'var(--color-chart-3)', 'var(--color-chart-4)'];

export default function Revenue({ metrics, trend, byPlan }: Props) {
    const cards = [
        { label: 'Revenue today', value: formatPaise(metrics.revenue_today), icon: IndianRupee },
        { label: 'Revenue this month', value: formatPaise(metrics.revenue_month), icon: TrendingUp },
        { label: 'Total revenue', value: formatPaise(metrics.revenue_total), icon: IndianRupee },
        { label: 'Refunded', value: formatPaise(metrics.refunded_total), icon: RotateCcw },
        { label: 'Active subscriptions', value: metrics.active_subscriptions, icon: CreditCard },
        { label: 'Paying members', value: metrics.paying_members, icon: Users },
        { label: 'Expired', value: metrics.expired_subscriptions, icon: XCircle },
        { label: 'Failed payments', value: metrics.failed_payments, icon: XCircle },
    ];

    return (
        <AdminLayout
            title="Revenue analytics"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Revenue', href: route('admin.revenue.index') },
            ]}
        >
            <Head title="Revenue analytics" />

            <div className="mb-4 flex flex-wrap justify-end gap-2">
                <Button variant="outline" size="sm" asChild>
                    <a href={route('admin.exports.payments')}>
                        <Download className="size-4" /> Export payments (CSV)
                    </a>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <a href={route('admin.exports.users')}>
                        <Download className="size-4" /> Export users (CSV)
                    </a>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((c) => (
                    <Card key={c.label}>
                        <CardContent className="p-5">
                            <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
                                <c.icon className="size-5" />
                            </span>
                            <p className="mt-3 text-2xl font-bold">{c.value}</p>
                            <p className="text-muted-foreground text-sm">{c.label}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Revenue · last 30 days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-72 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={trend.map((t) => ({ ...t, rupees: t.amount / 100 }))} margin={{ left: -10, right: 8, top: 8 }}>
                                    <defs>
                                        <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.35} />
                                            <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                                    <XAxis dataKey="date" tick={{ fontSize: 10 }} stroke="var(--color-muted-foreground)" interval={4} />
                                    <YAxis tick={{ fontSize: 11 }} stroke="var(--color-muted-foreground)" />
                                    <Tooltip
                                        contentStyle={{
                                            background: 'var(--color-popover)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: 8,
                                            fontSize: 12,
                                        }}
                                        formatter={(v: number) => ['₹' + v.toLocaleString('en-IN'), 'Revenue']}
                                    />
                                    <Area type="monotone" dataKey="rupees" stroke="var(--color-primary)" strokeWidth={2} fill="url(#rev)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Revenue by plan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {byPlan.length === 0 ? (
                            <p className="text-muted-foreground py-12 text-center text-sm">No revenue yet.</p>
                        ) : (
                            <div className="h-72 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={byPlan.map((p) => ({ ...p, rupees: p.amount / 100 }))} margin={{ left: -10, right: 8, top: 8 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                                        <XAxis dataKey="plan" tick={{ fontSize: 11 }} stroke="var(--color-muted-foreground)" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="var(--color-muted-foreground)" />
                                        <Tooltip
                                            contentStyle={{
                                                background: 'var(--color-popover)',
                                                border: '1px solid var(--color-border)',
                                                borderRadius: 8,
                                                fontSize: 12,
                                            }}
                                            formatter={(v: number) => ['₹' + v.toLocaleString('en-IN'), 'Revenue']}
                                        />
                                        <Bar dataKey="rupees" radius={[6, 6, 0, 0]}>
                                            {byPlan.map((_, i) => (
                                                <Cell key={i} fill={PLAN_COLORS[i % PLAN_COLORS.length]} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
