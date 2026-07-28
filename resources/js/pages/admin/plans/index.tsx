import { Head, router, useForm } from '@inertiajs/react';
import { Check, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatCurrency } from '@/lib/format';

interface AdminPlan {
    uuid: string;
    name: string;
    tier: string;
    description: string | null;
    price: number;
    gst_percent: number;
    duration_days: number;
    trial_days: number;
    contact_view_access: boolean;
    messaging_access: boolean;
    advanced_search: boolean;
    profile_boost: boolean;
    profile_highlight: boolean;
    verification_priority: boolean;
    max_interests_per_day: number | null;
    max_profile_views_per_day: number | null;
    features: string[];
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    active_subscribers: number;
}

const FEATURE_FLAGS: { key: keyof AdminPlan; label: string }[] = [
    { key: 'contact_view_access', label: 'Contact view' },
    { key: 'messaging_access', label: 'Messaging' },
    { key: 'advanced_search', label: 'Advanced search' },
    { key: 'profile_boost', label: 'Profile boost' },
    { key: 'profile_highlight', label: 'Profile highlight' },
    { key: 'verification_priority', label: 'Verification priority' },
];

export default function AdminPlans({ plans }: { plans: AdminPlan[] }) {
    return (
        <AdminLayout
            title="Subscription plans"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Plans', href: route('admin.plans.index') },
            ]}
        >
            <Head title="Subscription plans" />

            <div className="mb-4 flex justify-end">
                <PlanDialog
                    trigger={
                        <Button>
                            <Plus className="size-4" /> New plan
                        </Button>
                    }
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                {plans.map((plan) => (
                    <Card key={plan.uuid}>
                        <CardContent className="p-5">
                            <div className="flex items-start justify-between">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-semibold">{plan.name}</h3>
                                        {plan.is_featured && <Badge className="bg-primary text-primary-foreground">Featured</Badge>}
                                        <Badge
                                            variant={plan.is_active ? 'default' : 'secondary'}
                                            className={plan.is_active ? 'bg-secondary text-secondary-foreground' : ''}
                                        >
                                            {plan.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 text-2xl font-bold">
                                        {plan.price === 0 ? 'Free' : formatCurrency(plan.price)}{' '}
                                        <span className="text-muted-foreground text-sm font-normal">
                                            +{plan.gst_percent}% GST /{plan.duration_days}d
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground text-xs">{plan.active_subscribers} active subscribers</p>
                                </div>
                                <div className="flex gap-1">
                                    <PlanDialog
                                        plan={plan}
                                        trigger={
                                            <Button size="icon" variant="ghost">
                                                <Pencil className="size-4" />
                                            </Button>
                                        }
                                    />
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="text-destructive"
                                        onClick={() =>
                                            confirm('Archive this plan?') &&
                                            router.delete(route('admin.plans.destroy', { plan: plan.uuid }), { preserveScroll: true })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {FEATURE_FLAGS.map((f) => (
                                    <span
                                        key={f.key}
                                        className={`flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${plan[f.key] ? 'bg-secondary/15 text-secondary' : 'bg-muted text-muted-foreground'}`}
                                    >
                                        {plan[f.key] ? <Check className="size-3" /> : <X className="size-3" />} {f.label}
                                    </span>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AdminLayout>
    );
}

function PlanDialog({ plan, trigger }: { plan?: AdminPlan; trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const isEdit = !!plan;
    const { data, setData, processing, errors } = useForm({
        name: plan?.name ?? '',
        tier: plan?.tier ?? 'basic',
        description: plan?.description ?? '',
        price: plan?.price ?? 0,
        gst_percent: plan?.gst_percent ?? 18,
        duration_days: plan?.duration_days ?? 30,
        trial_days: plan?.trial_days ?? 0,
        contact_view_access: plan?.contact_view_access ?? false,
        messaging_access: plan?.messaging_access ?? false,
        advanced_search: plan?.advanced_search ?? false,
        profile_boost: plan?.profile_boost ?? false,
        profile_highlight: plan?.profile_highlight ?? false,
        verification_priority: plan?.verification_priority ?? false,
        max_interests_per_day: plan?.max_interests_per_day ?? null,
        max_profile_views_per_day: plan?.max_profile_views_per_day ?? null,
        features: (plan?.features ?? []).join('\n'),
        is_active: plan?.is_active ?? true,
        is_featured: plan?.is_featured ?? false,
        sort_order: plan?.sort_order ?? 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const payload = {
            ...data,
            features: data.features
                .split('\n')
                .map((s) => s.trim())
                .filter(Boolean),
        };
        if (isEdit) {
            router.put(route('admin.plans.update', { plan: plan!.uuid }), payload as never, {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            router.post(route('admin.plans.store'), payload as never, { preserveScroll: true, onSuccess: () => setOpen(false) });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <DialogTitle>{isEdit ? 'Edit plan' : 'New plan'}</DialogTitle>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Name" error={errors.name}>
                            <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </Field>
                        <Field label="Tier">
                            <Select value={data.tier} onValueChange={(v) => setData('tier', v)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {['free', 'basic', 'premium', 'vip', 'custom'].map((t) => (
                                        <SelectItem key={t} value={t}>
                                            {t}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="Price (₹)" error={errors.price}>
                            <Input type="number" value={data.price} onChange={(e) => setData('price', Number(e.target.value))} />
                        </Field>
                        <Field label="GST %">
                            <Input type="number" value={data.gst_percent} onChange={(e) => setData('gst_percent', Number(e.target.value))} />
                        </Field>
                        <Field label="Duration (days)">
                            <Input type="number" value={data.duration_days} onChange={(e) => setData('duration_days', Number(e.target.value))} />
                        </Field>
                        <Field label="Trial (days)">
                            <Input type="number" value={data.trial_days} onChange={(e) => setData('trial_days', Number(e.target.value))} />
                        </Field>
                        <Field label="Max interests/day">
                            <Input
                                type="number"
                                value={data.max_interests_per_day ?? ''}
                                onChange={(e) => setData('max_interests_per_day', e.target.value ? Number(e.target.value) : null)}
                            />
                        </Field>
                        <Field label="Max views/day">
                            <Input
                                type="number"
                                value={data.max_profile_views_per_day ?? ''}
                                onChange={(e) => setData('max_profile_views_per_day', e.target.value ? Number(e.target.value) : null)}
                            />
                        </Field>
                    </div>
                    <Field label="Description">
                        <Textarea value={data.description} onChange={(e) => setData('description', e.target.value)} />
                    </Field>
                    <Field label="Feature bullets (one per line)">
                        <Textarea value={data.features} onChange={(e) => setData('features', e.target.value)} />
                    </Field>
                    <div className="grid grid-cols-2 gap-2">
                        {FEATURE_FLAGS.map((f) => (
                            <label key={f.key} className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data[f.key as keyof typeof data] as boolean}
                                    onClick={() => setData(f.key as keyof typeof data, !data[f.key as keyof typeof data] as never)}
                                />{' '}
                                {f.label}
                            </label>
                        ))}
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.is_active} onClick={() => setData('is_active', !data.is_active)} /> Active
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.is_featured} onClick={() => setData('is_featured', !data.is_featured)} /> Featured
                        </label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Save changes' : 'Create plan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="grid gap-1.5">
            <Label className="text-xs">{label}</Label>
            {children}
            {error && <p className="text-destructive text-xs">{error}</p>}
        </div>
    );
}
