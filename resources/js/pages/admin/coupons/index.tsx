import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';

interface Coupon {
    uuid: string;
    code: string;
    type: string;
    value: number;
    value_display: string;
    min_amount_paise: number;
    max_redemptions: number | null;
    redeemed_count: number;
    per_user_limit: number;
    is_referral: boolean;
    is_active: boolean;
    starts_at: string | null;
    expires_at: string | null;
}

export default function AdminCoupons({ coupons, canManage }: { coupons: Coupon[]; canManage: boolean }) {
    return (
        <AdminLayout
            title="Coupons"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Coupons', href: route('admin.coupons.index') },
            ]}
        >
            <Head title="Coupons" />

            {canManage && (
                <div className="mb-4 flex justify-end">
                    <CouponDialog
                        trigger={
                            <Button>
                                <Plus className="size-4" /> New coupon
                            </Button>
                        }
                    />
                </div>
            )}

            <Card>
                <CardContent className="overflow-x-auto px-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-muted-foreground border-b text-left text-xs tracking-wide uppercase">
                                <th className="px-4 py-3 font-medium">Code</th>
                                <th className="px-4 py-3 font-medium">Discount</th>
                                <th className="px-4 py-3 font-medium">Usage</th>
                                <th className="px-4 py-3 font-medium">Validity</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {coupons.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-4 py-12 text-center">
                                        No coupons yet.
                                    </td>
                                </tr>
                            ) : (
                                coupons.map((c) => (
                                    <tr key={c.uuid} className="hover:bg-muted/40 border-b last:border-0">
                                        <td className="px-4 py-3 font-mono font-medium">
                                            {c.code}{' '}
                                            {c.is_referral && (
                                                <Badge variant="secondary" className="ml-1">
                                                    referral
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">{c.value_display}</td>
                                        <td className="px-4 py-3">
                                            {c.redeemed_count}
                                            {c.max_redemptions ? ` / ${c.max_redemptions}` : ''}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {c.expires_at ? `till ${formatDate(c.expires_at)}` : 'No expiry'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                className={c.is_active ? 'bg-secondary text-secondary-foreground' : ''}
                                                variant={c.is_active ? 'default' : 'secondary'}
                                            >
                                                {c.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {canManage && (
                                                <div className="flex justify-end gap-1">
                                                    <CouponDialog
                                                        coupon={c}
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
                                                            confirm('Deactivate coupon?') &&
                                                            router.delete(route('admin.coupons.destroy', { coupon: c.uuid }), {
                                                                preserveScroll: true,
                                                            })
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

function CouponDialog({ coupon, trigger }: { coupon?: Coupon; trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const isEdit = !!coupon;
    const { data, setData, processing, errors } = useForm({
        code: coupon?.code ?? '',
        type: coupon?.type ?? 'percent',
        value: coupon ? (coupon.type === 'percent' ? coupon.value : coupon.value / 100) : 10,
        max_discount: '',
        min_amount: coupon ? coupon.min_amount_paise / 100 : 0,
        max_redemptions: coupon?.max_redemptions ?? null,
        per_user_limit: coupon?.per_user_limit ?? 1,
        is_referral: coupon?.is_referral ?? false,
        is_active: coupon?.is_active ?? true,
        expires_at: coupon?.expires_at ? coupon.expires_at.slice(0, 10) : '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const url = isEdit ? route('admin.coupons.update', { coupon: coupon!.uuid }) : route('admin.coupons.store');
        const method = isEdit ? router.put : router.post;
        method(url, data as never, { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{isEdit ? 'Edit coupon' : 'New coupon'}</DialogTitle>
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Code</Label>
                            <Input value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} disabled={isEdit} />
                            {errors.code && <p className="text-destructive text-xs">{errors.code}</p>}
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Type</Label>
                            <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="percent">Percentage</SelectItem>
                                    <SelectItem value="fixed">Fixed (₹)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">{data.type === 'percent' ? 'Percent (%)' : 'Amount (₹)'}</Label>
                            <Input type="number" value={data.value} onChange={(e) => setData('value', Number(e.target.value))} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Max discount (₹)</Label>
                            <Input type="number" value={data.max_discount} onChange={(e) => setData('max_discount', e.target.value)} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Min order (₹)</Label>
                            <Input type="number" value={data.min_amount} onChange={(e) => setData('min_amount', Number(e.target.value))} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Max redemptions</Label>
                            <Input
                                type="number"
                                value={data.max_redemptions ?? ''}
                                onChange={(e) => setData('max_redemptions', e.target.value ? Number(e.target.value) : null)}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Per user limit</Label>
                            <Input type="number" value={data.per_user_limit} onChange={(e) => setData('per_user_limit', Number(e.target.value))} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Expires</Label>
                            <Input type="date" value={data.expires_at} onChange={(e) => setData('expires_at', e.target.value)} />
                        </div>
                    </div>
                    <div className="flex gap-4">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.is_referral} onClick={() => setData('is_referral', !data.is_referral)} /> Referral code
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.is_active} onClick={() => setData('is_active', !data.is_active)} /> Active
                        </label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Save' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
