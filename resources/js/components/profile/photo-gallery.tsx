import { router, useForm } from '@inertiajs/react';
import { ImagePlus, Loader2, Star, Trash2 } from 'lucide-react';
import { useRef } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Photo } from '@/types/profile';

const MAX_PHOTOS = 10;

const STATUS_STYLES: Record<Photo['status'], string> = {
    approved: 'bg-secondary text-secondary-foreground',
    pending: 'bg-accent/20 text-accent-foreground',
    rejected: 'bg-destructive/15 text-destructive',
};

export default function PhotoGallery({ photos }: { photos: Photo[] }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { setData, post, processing, reset, errors } = useForm<{ photo: File | null }>({ photo: null });

    const onFileSelected = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setData('photo', file);
        post(route('member.photos.store'), {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                reset('photo');
                if (fileInput.current) fileInput.current.value = '';
            },
        });
    };

    const setPrimary = (photo: Photo) => router.post(route('member.photos.primary', { photo: photo.uuid }), {}, { preserveScroll: true });

    const remove = (photo: Photo) => router.delete(route('member.photos.destroy', { photo: photo.uuid }), { preserveScroll: true });

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="font-semibold">Photos</h3>
                    <p className="text-muted-foreground text-sm">Upload up to {MAX_PHOTOS} photos. Each is reviewed before it becomes visible.</p>
                </div>
                <Button type="button" onClick={() => fileInput.current?.click()} disabled={processing || photos.length >= MAX_PHOTOS}>
                    {processing ? <Loader2 className="size-4 animate-spin" /> : <ImagePlus className="size-4" />}
                    Add photo
                </Button>
                <input ref={fileInput} type="file" accept="image/*" className="hidden" onChange={onFileSelected} />
            </div>

            {errors.photo && <p className="text-destructive text-sm">{errors.photo}</p>}

            {photos.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed py-12 text-center">
                    <ImagePlus className="text-muted-foreground size-8" />
                    <p className="text-muted-foreground mt-2 text-sm">No photos yet. Add your first photo to build trust.</p>
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    {photos.map((photo) => (
                        <div key={photo.uuid} className="group bg-muted relative overflow-hidden rounded-xl border">
                            <img
                                src={photo.thumb_url}
                                alt="Profile"
                                className={`aspect-square w-full object-cover ${photo.status === 'rejected' ? 'opacity-50' : ''}`}
                            />
                            <div className="absolute top-2 left-2 flex gap-1">
                                {photo.is_primary && (
                                    <Badge className="bg-primary text-primary-foreground">
                                        <Star className="mr-1 size-3" /> Primary
                                    </Badge>
                                )}
                                <Badge className={STATUS_STYLES[photo.status]}>{photo.status}</Badge>
                            </div>

                            {photo.status === 'rejected' && photo.moderation_reason && (
                                <p className="bg-destructive/10 text-destructive px-2 py-1 text-xs">{photo.moderation_reason}</p>
                            )}

                            <div className="flex items-center justify-between gap-1 p-2">
                                {!photo.is_primary && photo.status === 'approved' ? (
                                    <Button type="button" size="sm" variant="ghost" onClick={() => setPrimary(photo)}>
                                        <Star className="size-4" /> Set primary
                                    </Button>
                                ) : (
                                    <span />
                                )}
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button type="button" size="icon" variant="ghost" className="text-destructive">
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>Remove this photo?</DialogTitle>
                                        <DialogDescription>This action cannot be undone.</DialogDescription>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="secondary">Cancel</Button>
                                            </DialogClose>
                                            <Button variant="destructive" onClick={() => remove(photo)}>
                                                Remove
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
