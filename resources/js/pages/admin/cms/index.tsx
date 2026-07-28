import { Head, Link, router, useForm } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';

interface Page {
    uuid: string;
    slug: string;
    title: string;
    body: string | null;
    meta_title: string | null;
    meta_description: string | null;
    is_published: boolean;
    updated_at: string | null;
}

export default function AdminCms({ pages }: { pages: Page[] }) {
    return (
        <AdminLayout
            title="CMS pages"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'CMS', href: route('admin.cms.index') },
            ]}
        >
            <Head title="CMS pages" />

            <div className="mb-4 flex justify-end">
                <PageDialog
                    trigger={
                        <Button>
                            <Plus className="size-4" /> New page
                        </Button>
                    }
                />
            </div>

            <Card>
                <CardContent className="divide-y p-0">
                    {pages.map((p) => (
                        <div key={p.uuid} className="flex items-center justify-between p-4">
                            <div>
                                <div className="flex items-center gap-2">
                                    <p className="font-medium">{p.title}</p>
                                    <Badge
                                        className={p.is_published ? 'bg-secondary text-secondary-foreground' : ''}
                                        variant={p.is_published ? 'default' : 'secondary'}
                                    >
                                        {p.is_published ? 'Published' : 'Draft'}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground font-mono text-xs">
                                    /pages/{p.slug} · updated {formatDate(p.updated_at)}
                                </p>
                            </div>
                            <div className="flex gap-1">
                                {p.is_published && (
                                    <Button size="icon" variant="ghost" asChild>
                                        <Link href={`/pages/${p.slug}`} target="_blank">
                                            <ExternalLink className="size-4" />
                                        </Link>
                                    </Button>
                                )}
                                <PageDialog
                                    page={p}
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
                                        confirm('Delete this page?') &&
                                        router.delete(route('admin.cms.destroy', { cmsPage: p.slug }), { preserveScroll: true })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

function PageDialog({ page, trigger }: { page?: Page; trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const isEdit = !!page;
    const { data, setData, processing, errors } = useForm({
        slug: page?.slug ?? '',
        title: page?.title ?? '',
        body: page?.body ?? '',
        meta_title: page?.meta_title ?? '',
        meta_description: page?.meta_description ?? '',
        is_published: page?.is_published ?? false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (isEdit) {
            router.put(route('admin.cms.update', { cmsPage: page!.slug }), data as never, opts);
        } else {
            router.post(route('admin.cms.store'), data as never, opts);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogTitle>{isEdit ? 'Edit page' : 'New page'}</DialogTitle>
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Title</Label>
                            <Input value={data.title} onChange={(e) => setData('title', e.target.value)} />
                            {errors.title && <p className="text-destructive text-xs">{errors.title}</p>}
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Slug</Label>
                            <Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} placeholder="privacy-policy" />
                            {errors.slug && <p className="text-destructive text-xs">{errors.slug}</p>}
                        </div>
                    </div>
                    <div className="grid gap-1.5">
                        <Label className="text-xs">Body</Label>
                        <Textarea rows={10} value={data.body} onChange={(e) => setData('body', e.target.value)} />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Meta title</Label>
                            <Input value={data.meta_title} onChange={(e) => setData('meta_title', e.target.value)} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Meta description</Label>
                            <Input value={data.meta_description} onChange={(e) => setData('meta_description', e.target.value)} />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={data.is_published} onClick={() => setData('is_published', !data.is_published)} /> Published
                    </label>
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
