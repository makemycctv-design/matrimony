import { Head, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AdminLayout from '@/layouts/admin-layout';

interface SettingField {
    value: string | number | boolean | null;
    type: string;
    is_secret: boolean;
}

type Grouped = Record<string, Record<string, SettingField>>;

const GROUP_LABELS: Record<string, string> = {
    general: 'General',
    seo: 'SEO',
    matching: 'Matching weights',
    razorpay: 'Razorpay / Payments',
    notifications: 'Notifications',
};

export default function Settings({ settings }: { settings: Grouped }) {
    // Flatten to { group: { key: value } } for the form.
    const initial: Record<string, Record<string, unknown>> = {};
    Object.entries(settings).forEach(([group, fields]) => {
        initial[group] = {};
        Object.entries(fields).forEach(([key, f]) => {
            initial[group][key] = f.type === 'boolean' ? Boolean(f.value) : (f.value ?? '');
        });
    });

    const { data, setData, put, processing } = useForm<{ settings: Record<string, Record<string, unknown>> }>({ settings: initial });

    const update = (group: string, key: string, value: unknown) =>
        setData('settings', { ...data.settings, [group]: { ...data.settings[group], [key]: value } });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.settings.update'), { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="System settings"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Settings', href: route('admin.settings.edit') },
            ]}
        >
            <Head title="System settings" />

            <form onSubmit={submit} className="max-w-3xl space-y-6">
                {Object.entries(settings).map(([group, fields]) => (
                    <Card key={group}>
                        <CardHeader>
                            <CardTitle className="text-base">{GROUP_LABELS[group] ?? group}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {Object.entries(fields).map(([key, field]) => (
                                <div key={key} className="flex items-center justify-between gap-4">
                                    <Label className="text-sm capitalize">
                                        {key.replace(/_/g, ' ')} {field.is_secret && <span className="text-muted-foreground text-xs">(secret)</span>}
                                    </Label>
                                    {field.type === 'boolean' ? (
                                        <Switch
                                            checked={Boolean(data.settings[group]?.[key])}
                                            onCheckedChange={(v) => update(group, key, v)}
                                            aria-label={key}
                                        />
                                    ) : (
                                        <Input
                                            type={field.is_secret ? 'password' : field.type === 'integer' ? 'number' : 'text'}
                                            className="max-w-xs"
                                            value={String(data.settings[group]?.[key] ?? '')}
                                            onChange={(e) => update(group, key, e.target.value)}
                                            placeholder={field.is_secret ? 'Leave unchanged' : ''}
                                        />
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                ))}

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="size-4 animate-spin" />}
                        Save settings
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
