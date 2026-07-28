import { Head, useForm } from '@inertiajs/react';
import { BadgeCheck, Ban, Check, Mail, Phone, X } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';
import { Document, OwnerProfile, Photo, VerificationEntry } from '@/types/profile';

interface AdminProfile extends OwnerProfile {
    age: number | null;
    user: { uuid: string; name: string; email: string; mobile: string; status: string; joined_at: string | null };
}

export default function AdminProfileShow({ profile }: { profile: AdminProfile }) {
    const f = profile.fields;

    return (
        <AdminLayout
            title={`Review · ${profile.profile_code}`}
            breadcrumbs={[
                { title: 'Admin', href: route('admin.dashboard') },
                { title: 'Profiles', href: route('admin.profiles.index') },
                { title: profile.profile_code ?? 'Profile', href: '#' },
            ]}
        >
            <Head title={`Review ${profile.profile_code}`} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardContent className="flex flex-wrap items-start justify-between gap-4 p-5">
                            <div className="flex items-center gap-4">
                                <div className="bg-primary/10 text-primary flex size-14 items-center justify-center rounded-xl text-2xl font-semibold">
                                    {profile.user.name?.charAt(0)}
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h2 className="text-lg font-semibold">{profile.user.name}</h2>
                                        {profile.is_verified && <BadgeCheck className="text-secondary size-5" />}
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {profile.profile_code} · {profile.age ?? '—'} yrs · {f.gender as string}
                                    </p>
                                    <div className="text-muted-foreground mt-1 flex flex-wrap gap-3 text-xs">
                                        <span className="flex items-center gap-1">
                                            <Mail className="size-3.5" /> {profile.user.email}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Phone className="size-3.5" /> {profile.user.mobile}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <Badge>{(profile.status ?? 'draft').replace('_', ' ')}</Badge>
                        </CardContent>
                    </Card>

                    <DetailCard title="Basic">
                        <Detail label="Name" value={`${f.first_name ?? ''} ${f.last_name ?? ''}`} />
                        <Detail label="Date of birth" value={f.date_of_birth as string} />
                        <Detail label="Marital status" value={f.marital_status as string} />
                        <Detail label="Height" value={f.height_cm ? `${f.height_cm} cm` : '—'} />
                        <Detail label="Physical status" value={f.physical_status as string} />
                        <Detail label="Complexion" value={f.complexion as string} />
                    </DetailCard>

                    <DetailCard title="Community & career">
                        <Detail label="Religion" value={f.religion_id ? `#${f.religion_id}` : '—'} />
                        <Detail label="Mother tongue" value={f.mother_tongue_id ? `#${f.mother_tongue_id}` : '—'} />
                        <Detail label="Education" value={(f.education_detail as string) || (f.education_id ? `#${f.education_id}` : '—')} />
                        <Detail label="Profession" value={(f.profession_detail as string) || (f.profession_id ? `#${f.profession_id}` : '—')} />
                        <Detail label="Employment" value={f.employment_type as string} />
                        <Detail label="Company" value={f.company_name as string} />
                    </DetailCard>

                    {(f.about_me || f.partner_expectations_note) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">About & expectations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {!!f.about_me && <p>{f.about_me as string}</p>}
                                {!!f.partner_expectations_note && (
                                    <div>
                                        <p className="font-medium">Partner expectations</p>
                                        <p className="text-muted-foreground">{f.partner_expectations_note as string}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <PhotosCard photos={profile.photos} />
                    <DocumentsCard documents={profile.documents} />
                </div>

                <div className="space-y-6">
                    <ActionsCard profile={profile} />
                    <TimelineCard verifications={profile.verifications} />
                </div>
            </div>
        </AdminLayout>
    );
}

function DetailCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-x-6 gap-y-3 sm:grid-cols-2">{children}</CardContent>
        </Card>
    );
}

function Detail({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm font-medium capitalize">{value ? String(value).replace(/_/g, ' ') : '—'}</p>
        </div>
    );
}

function PhotosCard({ photos }: { photos: Photo[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Photos ({photos.length})</CardTitle>
            </CardHeader>
            <CardContent>
                {photos.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No photos uploaded.</p>
                ) : (
                    <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                        {photos.map((p) => (
                            <a
                                key={p.uuid}
                                href={p.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="relative overflow-hidden rounded-lg border"
                            >
                                <img src={p.thumb_url} alt="" className="aspect-square w-full object-cover" />
                                <Badge className="absolute top-1 left-1 text-[10px]">{p.status}</Badge>
                            </a>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function DocumentsCard({ documents }: { documents: Document[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">KYC documents ({documents.length})</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                {documents.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No documents uploaded.</p>
                ) : (
                    documents.map((d) => (
                        <div key={d.uuid} className="flex items-center justify-between rounded-lg border p-3 text-sm">
                            <div>
                                <p className="font-medium">
                                    {d.type_label}
                                    {d.last4 && <span className="text-muted-foreground"> ····{d.last4}</span>}
                                </p>
                                <Badge className="mt-1 text-[10px]">{d.status}</Badge>
                            </div>
                            <a href={d.url} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                                View document
                            </a>
                        </div>
                    ))
                )}
            </CardContent>
        </Card>
    );
}

function ActionsCard({ profile }: { profile: AdminProfile }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Decision</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                <ApproveDialog uuid={profile.uuid} />
                <ReasonDialog
                    uuid={profile.uuid}
                    action="reject"
                    routeName="admin.profiles.reject"
                    title="Reject profile"
                    description="The member will see this reason and can resubmit."
                    trigger={
                        <Button variant="outline" className="w-full justify-start">
                            <X className="size-4" /> Reject & request changes
                        </Button>
                    }
                />
                <ReasonDialog
                    uuid={profile.uuid}
                    action="suspend"
                    routeName="admin.profiles.suspend"
                    title="Suspend profile"
                    description="Suspending removes this profile from discovery."
                    trigger={
                        <Button variant="outline" className="text-destructive w-full justify-start">
                            <Ban className="size-4" /> Suspend profile
                        </Button>
                    }
                />
            </CardContent>
        </Card>
    );
}

function ApproveDialog({ uuid }: { uuid: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm({ notes: '' });
    const submit = () => post(route('admin.profiles.approve', { profile: uuid }), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="bg-secondary text-secondary-foreground hover:bg-secondary/90 w-full justify-start">
                    <Check className="size-4" /> Approve & verify
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Approve & verify profile?</DialogTitle>
                <DialogDescription>The member will receive a verified badge and a notification.</DialogDescription>
                <div className="grid gap-2">
                    <Label>Internal notes (optional)</Label>
                    <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                </div>
                <DialogFooter>
                    <Button onClick={submit} disabled={processing}>
                        Confirm approval
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ReasonDialog({
    uuid,
    routeName,
    title,
    description,
    trigger,
}: {
    uuid: string;
    action: string;
    routeName: string;
    title: string;
    description: string;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ reason: '' });
    const submit = () => post(route(routeName, { profile: uuid }), { preserveScroll: true, onSuccess: () => setOpen(false) });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <div className="grid gap-2">
                    <Label>Reason</Label>
                    <Textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} placeholder="Provide a clear reason…" />
                    {errors.reason && <p className="text-destructive text-sm">{errors.reason}</p>}
                </div>
                <DialogFooter>
                    <Button variant="destructive" onClick={submit} disabled={processing}>
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function TimelineCard({ verifications }: { verifications: VerificationEntry[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Verification history</CardTitle>
            </CardHeader>
            <CardContent>
                {verifications.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No history yet.</p>
                ) : (
                    <ul className="space-y-4">
                        {verifications.map((v, i) => (
                            <li key={i} className="relative border-l pl-4">
                                <span className="bg-primary absolute top-1 -left-1.5 size-3 rounded-full" />
                                <p className="text-sm font-medium capitalize">
                                    {v.scope} · {v.action.replace(/_/g, ' ')}
                                </p>
                                {v.reason && <p className="text-muted-foreground text-xs">{v.reason}</p>}
                                <p className="text-muted-foreground text-xs">
                                    {v.actor ?? 'System'} · {formatDate(v.at)}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
