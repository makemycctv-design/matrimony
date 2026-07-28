import { Head, router, useForm } from '@inertiajs/react';
import { Check, FileText, ShieldCheck, X } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';

interface PendingPhoto {
    uuid: string;
    url: string;
    thumb_url: string;
    profile_code: string | null;
    uploaded_at: string | null;
}

interface PendingDocument {
    uuid: string;
    type: string;
    type_label: string;
    last4: string | null;
    url: string;
    mime_type: string | null;
    profile_code: string | null;
    uploaded_at: string | null;
}

interface Props {
    photos: PendingPhoto[];
    documents: PendingDocument[];
}

export default function VerificationQueue({ photos, documents }: Props) {
    return (
        <AdminLayout
            title="Verification queue"
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Verifications', href: route('admin.verifications.index') },
            ]}
        >
            <Head title="Verification queue" />

            <div className="space-y-8">
                <section>
                    <h2 className="mb-3 flex items-center gap-2 text-lg font-semibold">
                        <ShieldCheck className="text-primary size-5" /> Photos awaiting review
                        <Badge variant="secondary">{photos.length}</Badge>
                    </h2>
                    {photos.length === 0 ? (
                        <EmptyState label="No photos awaiting moderation." />
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            {photos.map((photo) => (
                                <Card key={photo.uuid} className="overflow-hidden">
                                    <a href={photo.url} target="_blank" rel="noopener noreferrer">
                                        <img src={photo.thumb_url} alt="" className="aspect-square w-full object-cover" />
                                    </a>
                                    <CardContent className="space-y-2 p-3">
                                        <p className="text-muted-foreground font-mono text-xs">{photo.profile_code}</p>
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                className="bg-secondary text-secondary-foreground hover:bg-secondary/90 flex-1"
                                                onClick={() =>
                                                    router.post(
                                                        route('admin.photos.moderate', { photo: photo.uuid }),
                                                        { decision: 'approve' },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <Check className="size-4" />
                                            </Button>
                                            <RejectDialog
                                                title="Reject photo"
                                                onConfirm={(reason) =>
                                                    router.post(
                                                        route('admin.photos.moderate', { photo: photo.uuid }),
                                                        { decision: 'reject', reason },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                                trigger={
                                                    <Button size="sm" variant="outline" className="text-destructive flex-1">
                                                        <X className="size-4" />
                                                    </Button>
                                                }
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>

                <section>
                    <h2 className="mb-3 flex items-center gap-2 text-lg font-semibold">
                        <FileText className="text-primary size-5" /> Documents awaiting review
                        <Badge variant="secondary">{documents.length}</Badge>
                    </h2>
                    {documents.length === 0 ? (
                        <EmptyState label="No documents awaiting verification." />
                    ) : (
                        <Card>
                            <CardContent className="divide-y p-0">
                                {documents.map((doc) => (
                                    <div key={doc.uuid} className="flex flex-wrap items-center gap-4 p-4">
                                        <FileText className="text-muted-foreground size-8" />
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {doc.type_label}
                                                {doc.last4 && <span className="text-muted-foreground"> ····{doc.last4}</span>}
                                            </p>
                                            <p className="text-muted-foreground text-xs">{doc.profile_code}</p>
                                        </div>
                                        <a href={doc.url} target="_blank" rel="noopener noreferrer" className="text-primary text-sm hover:underline">
                                            Open document
                                        </a>
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                className="bg-secondary text-secondary-foreground hover:bg-secondary/90"
                                                onClick={() =>
                                                    router.post(
                                                        route('admin.documents.review', { document: doc.uuid }),
                                                        { decision: 'approve' },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <Check className="size-4" /> Approve
                                            </Button>
                                            <RejectDialog
                                                title="Reject document"
                                                onConfirm={(reason) =>
                                                    router.post(
                                                        route('admin.documents.review', { document: doc.uuid }),
                                                        { decision: 'reject', reason },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                                trigger={
                                                    <Button size="sm" variant="outline" className="text-destructive">
                                                        <X className="size-4" /> Reject
                                                    </Button>
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}

function EmptyState({ label }: { label: string }) {
    return (
        <Card>
            <CardContent className="text-muted-foreground py-10 text-center text-sm">{label}</CardContent>
        </Card>
    );
}

function RejectDialog({ title, onConfirm, trigger }: { title: string; onConfirm: (reason: string) => void; trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, processing, reset } = useForm({ reason: '' });

    const confirm = () => {
        onConfirm(data.reason);
        reset();
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>The member will see this reason and can upload a replacement.</DialogDescription>
                <div className="grid gap-2">
                    <Label>Reason</Label>
                    <Textarea
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        placeholder="e.g. Photo is blurry / does not clearly show the person."
                    />
                </div>
                <DialogFooter>
                    <Button variant="destructive" onClick={confirm} disabled={processing || !data.reason}>
                        Confirm rejection
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
