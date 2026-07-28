import { Head, router } from '@inertiajs/react';
import { BellOff, Check, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    read: boolean;
    created_at: string | null;
}

interface Props {
    notifications: { data: Notification[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
    unread: number;
}

export default function Notifications({ notifications, unread }: Props) {
    return (
        <MemberLayout title="Notifications">
            <Head title="Notifications" />

            <div className="mb-4 flex items-center justify-between">
                <p className="text-muted-foreground text-sm">{unread} unread</p>
                {unread > 0 && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.post(route('member.notifications.read-all'), {}, { preserveScroll: true })}
                    >
                        <Check className="size-4" /> Mark all read
                    </Button>
                )}
            </div>

            {notifications.data.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                        <BellOff className="text-primary/40 size-8" /> You have no notifications yet.
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-2">
                    {notifications.data.map((n) => (
                        <Card key={n.id} className={cn(!n.read && 'border-primary/40 bg-primary/5')}>
                            <CardContent className="flex items-start justify-between gap-4 p-4">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        {!n.read && <span className="bg-primary size-2 shrink-0 rounded-full" />}
                                        <p className="font-medium">{n.title}</p>
                                    </div>
                                    <p className="text-muted-foreground text-sm">{n.message}</p>
                                    <p className="text-muted-foreground mt-1 text-xs">{formatDate(n.created_at)}</p>
                                </div>
                                <div className="flex gap-1">
                                    {!n.read && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label="Mark read"
                                            onClick={() =>
                                                router.post(route('member.notifications.read', { id: n.id }), {}, { preserveScroll: true })
                                            }
                                        >
                                            <Check className="size-4" />
                                        </Button>
                                    )}
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="text-destructive"
                                        aria-label="Delete"
                                        onClick={() => router.delete(route('member.notifications.destroy', { id: n.id }), { preserveScroll: true })}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {notifications.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {notifications.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                            className={`rounded-md px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'} ${!link.url ? 'opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </MemberLayout>
    );
}
