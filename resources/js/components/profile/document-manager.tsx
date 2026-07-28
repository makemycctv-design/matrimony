import { router, useForm } from '@inertiajs/react';
import { FileCheck2, FileClock, FileX2, Loader2, ShieldCheck, Trash2, Upload } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Document } from '@/types/profile';

const STATUS: Record<Document['status'], { label: string; icon: typeof FileCheck2; className: string }> = {
    approved: { label: 'Verified', icon: FileCheck2, className: 'bg-secondary text-secondary-foreground' },
    pending: { label: 'Under review', icon: FileClock, className: 'bg-accent/20 text-accent-foreground' },
    rejected: { label: 'Rejected', icon: FileX2, className: 'bg-destructive/15 text-destructive' },
};

interface DocumentManagerProps {
    documents: Document[];
    documentTypes: Record<string, string>;
}

export default function DocumentManager({ documents, documentTypes }: DocumentManagerProps) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        type: string;
        document_number: string;
        document: File | null;
    }>({ type: '', document_number: '', document: null });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('member.documents.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const remove = (doc: Document) => router.delete(route('member.documents.destroy', { document: doc.uuid }), { preserveScroll: true });

    return (
        <div className="space-y-5">
            <div className="border-secondary/30 bg-secondary/5 flex items-start gap-3 rounded-lg border p-4">
                <ShieldCheck className="text-secondary mt-0.5 size-5 shrink-0" />
                <p className="text-muted-foreground text-sm">
                    Your documents are encrypted and stored privately. Only our verification team can view them, via secure time-limited links. We
                    never share ID details with other members.
                </p>
            </div>

            <form onSubmit={submit} className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="doc-type">Document type</Label>
                    <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                        <SelectTrigger id="doc-type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(documentTypes).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.type && <p className="text-destructive text-sm">{errors.type}</p>}
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="doc-number">Document number (optional)</Label>
                    <Input
                        id="doc-number"
                        value={data.document_number}
                        onChange={(e) => setData('document_number', e.target.value)}
                        placeholder="Stored encrypted"
                    />
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="doc-file">File (JPG, PNG, PDF · max 8 MB)</Label>
                    <Input
                        id="doc-file"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onChange={(e) => setData('document', e.target.files?.[0] ?? null)}
                    />
                    {errors.document && <p className="text-destructive text-sm">{errors.document}</p>}
                </div>
                <div className="sm:col-span-2">
                    <Button type="submit" disabled={processing || !data.type || !data.document}>
                        {processing ? <Loader2 className="size-4 animate-spin" /> : <Upload className="size-4" />}
                        Upload document
                    </Button>
                </div>
            </form>

            <div className="space-y-2">
                {documents.length === 0 ? (
                    <p className="text-muted-foreground py-6 text-center text-sm">No documents uploaded yet.</p>
                ) : (
                    documents.map((doc) => {
                        const meta = STATUS[doc.status];
                        return (
                            <div key={doc.uuid} className="flex items-center gap-4 rounded-lg border p-3">
                                <meta.icon className="text-muted-foreground size-5" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {doc.type_label}
                                        {doc.last4 && <span className="text-muted-foreground"> ····{doc.last4}</span>}
                                    </p>
                                    {doc.status === 'rejected' && doc.rejection_reason && (
                                        <p className="text-destructive text-xs">{doc.rejection_reason}</p>
                                    )}
                                </div>
                                <Badge className={meta.className}>{meta.label}</Badge>
                                <a href={doc.url} target="_blank" rel="noopener noreferrer" className="text-primary text-sm hover:underline">
                                    View
                                </a>
                                {doc.status === 'pending' && (
                                    <Button size="icon" variant="ghost" className="text-destructive" onClick={() => remove(doc)}>
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        );
                    })
                )}
            </div>
        </div>
    );
}
