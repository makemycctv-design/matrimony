import { Head, useForm } from '@inertiajs/react';
import { Loader2, RotateCcw, SlidersHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';

interface Props {
    weights: Record<string, number>;
    defaults: Record<string, number>;
}

export default function MatchingSettings({ weights, defaults }: Props) {
    const { data, setData, put, processing } = useForm<{ weights: Record<string, number> }>({ weights: { ...weights } });

    const factors = Object.keys(defaults);
    const total = Object.values(data.weights).reduce((a, b) => a + Number(b || 0), 0);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.matching.update'), { preserveScroll: true });
    };

    const resetDefaults = () => setData('weights', { ...defaults });

    return (
        <AdminLayout
            title="Matching configuration"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Matching', href: route('admin.matching.edit') },
            ]}
        >
            <Head title="Matching configuration" />

            <form onSubmit={submit} className="max-w-3xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <SlidersHorizontal className="text-primary size-5" /> Global compatibility weights
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <p className="text-muted-foreground text-sm">
                            These weights set the default importance of each factor in the match score. Members can further tune their own weighting.
                            Scores are guidance and never a guarantee of compatibility.
                        </p>

                        {factors.map((factor) => (
                            <div key={factor}>
                                <div className="mb-1 flex items-center justify-between text-sm">
                                    <span className="font-medium capitalize">{factor.replace(/_/g, ' ')}</span>
                                    <span className="text-muted-foreground">{data.weights[factor] ?? 0}</span>
                                </div>
                                <input
                                    type="range"
                                    min={0}
                                    max={100}
                                    value={data.weights[factor] ?? 0}
                                    onChange={(e) => setData('weights', { ...data.weights, [factor]: Number(e.target.value) })}
                                    className="w-full accent-[var(--color-primary)]"
                                />
                            </div>
                        ))}

                        <div className="bg-muted/40 flex items-center justify-between rounded-lg px-4 py-3 text-sm">
                            <span>Total weight (relative)</span>
                            <span className="font-semibold">{total}</span>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-between">
                    <Button type="button" variant="outline" onClick={resetDefaults}>
                        <RotateCcw className="size-4" /> Reset to defaults
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="size-4 animate-spin" />}
                        Save weights
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
