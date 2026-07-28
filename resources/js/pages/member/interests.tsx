import { Head, router } from '@inertiajs/react';
import { Check, Heart, Inbox, Send, X } from 'lucide-react';
import { useState } from 'react';

import ProfileCard from '@/components/profile/profile-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { cn } from '@/lib/utils';
import { MatchCard } from '@/types/profile';

interface InterestRow {
    uuid: string;
    status: string;
    message: string | null;
    created_at: string | null;
    responded_at: string | null;
    is_pending: boolean;
    profile: MatchCard | null;
}

interface Props {
    sent: InterestRow[];
    received: InterestRow[];
}

const STATUS_STYLES: Record<string, string> = {
    sent: 'bg-accent/20 text-accent-foreground',
    accepted: 'bg-secondary text-secondary-foreground',
    declined: 'bg-destructive/15 text-destructive',
    withdrawn: 'bg-muted text-muted-foreground',
};

export default function Interests({ sent, received }: Props) {
    const [tab, setTab] = useState<'received' | 'sent'>('received');
    const rows = tab === 'received' ? received : sent;

    return (
        <MemberLayout title="Interests">
            <Head title="Interests" />

            <div className="bg-background mb-6 inline-flex rounded-lg border p-1">
                <TabButton active={tab === 'received'} onClick={() => setTab('received')} icon={Inbox} label={`Received (${received.length})`} />
                <TabButton active={tab === 'sent'} onClick={() => setTab('sent')} icon={Send} label={`Sent (${sent.length})`} />
            </div>

            {rows.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                        <Heart className="text-primary/40 size-8" />
                        {tab === 'received' ? 'No interests received yet.' : 'You have not sent any interests yet.'}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {rows.map((row) => (
                        <div key={row.uuid} className="space-y-2">
                            {row.profile ? (
                                <ProfileCard profile={row.profile} compact />
                            ) : (
                                <Card>
                                    <CardContent className="text-muted-foreground py-6 text-sm">Profile unavailable</CardContent>
                                </Card>
                            )}
                            <div className="bg-muted/30 flex items-center justify-between rounded-lg border px-3 py-2">
                                <Badge className={STATUS_STYLES[row.status]}>{row.status}</Badge>
                                {tab === 'received' && row.is_pending && (
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            className="bg-secondary text-secondary-foreground hover:bg-secondary/90"
                                            onClick={() =>
                                                router.post(route('member.interests.accept', { interest: row.uuid }), {}, { preserveScroll: true })
                                            }
                                        >
                                            <Check className="size-4" /> Accept
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="text-destructive"
                                            onClick={() =>
                                                router.post(route('member.interests.decline', { interest: row.uuid }), {}, { preserveScroll: true })
                                            }
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                )}
                                {tab === 'sent' && row.is_pending && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.post(route('member.interests.withdraw', { interest: row.uuid }), {}, { preserveScroll: true })
                                        }
                                    >
                                        Withdraw
                                    </Button>
                                )}
                            </div>
                            {row.message && <p className="bg-muted/30 text-muted-foreground rounded-lg px-3 py-2 text-xs">"{row.message}"</p>}
                        </div>
                    ))}
                </div>
            )}
        </MemberLayout>
    );
}

function TabButton({ active, onClick, icon: Icon, label }: { active: boolean; onClick: () => void; icon: typeof Inbox; label: string }) {
    return (
        <button
            onClick={onClick}
            className={cn(
                'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
            )}
        >
            <Icon className="size-4" /> {label}
        </button>
    );
}
