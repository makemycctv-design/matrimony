import { Head, router } from '@inertiajs/react';
import { ShieldOff } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatDate } from '@/lib/format';

interface BlockedRow {
    uuid: string | null;
    profile_code: string | null;
    display_name: string | null;
    reason: string | null;
    blocked_at: string | null;
}

export default function Blocked({ blocked }: { blocked: BlockedRow[] }) {
    return (
        <MemberLayout title="Blocked profiles">
            <Head title="Blocked profiles" />

            {blocked.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                        <ShieldOff className="text-primary/40 size-8" />
                        You have not blocked anyone.
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="divide-y p-0">
                        {blocked.map((b) => (
                            <div key={b.uuid ?? b.profile_code} className="flex items-center justify-between p-4">
                                <div>
                                    <p className="font-medium">{b.display_name ?? b.profile_code}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {b.profile_code}
                                        {b.reason ? ` · ${b.reason}` : ''} · Blocked {formatDate(b.blocked_at)}
                                    </p>
                                </div>
                                {b.uuid && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.delete(route('member.blocked.destroy', { profile: b.uuid }), { preserveScroll: true })}
                                    >
                                        Unblock
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            )}
        </MemberLayout>
    );
}
