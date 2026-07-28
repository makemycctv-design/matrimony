import { Head, router, useForm } from '@inertiajs/react';
import { Flag, MessageCircle, Send } from 'lucide-react';
import { useEffect, useRef } from 'react';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import MemberLayout from '@/layouts/member-layout';
import { cn } from '@/lib/utils';

interface ConversationSummary {
    uuid: string;
    other: { uuid: string; display_name: string; photo_url: string | null } | null;
    last_message: string | null;
    last_message_at: string | null;
    unread: boolean;
}

interface ActiveThread {
    uuid: string;
    other: { uuid: string; display_name: string } | null;
    messages: { uuid: string; body: string; mine: boolean; created_at: string | null; flagged: boolean }[];
}

interface Props {
    conversations: ConversationSummary[];
    active: ActiveThread | null;
}

export default function Messages({ conversations, active }: Props) {
    const initials = useInitials();
    const bottomRef = useRef<HTMLDivElement>(null);
    const { data, setData, post, processing, reset } = useForm<{ body: string }>({ body: '' });

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [active?.messages.length]);

    const send = (e: React.FormEvent) => {
        e.preventDefault();
        if (!active || !data.body.trim()) return;
        post(route('member.messages.send', { conversation: active.uuid }), { preserveScroll: true, onSuccess: () => reset('body') });
    };

    return (
        <MemberLayout title="Messages">
            <Head title="Messages" />

            <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
                {/* Conversation list */}
                <Card className="overflow-hidden">
                    <CardContent className="p-0">
                        {conversations.length === 0 ? (
                            <div className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                                <MessageCircle className="text-primary/40 size-8" /> No conversations yet. Connect with a match to start chatting.
                            </div>
                        ) : (
                            <div className="divide-y">
                                {conversations.map((c) => (
                                    <button
                                        key={c.uuid}
                                        onClick={() =>
                                            router.get(route('member.messages.show', { conversation: c.uuid }), {}, { preserveScroll: true })
                                        }
                                        className={cn(
                                            'hover:bg-muted flex w-full items-center gap-3 p-3 text-left',
                                            active?.uuid === c.uuid && 'bg-muted',
                                        )}
                                    >
                                        <Avatar className="size-10">
                                            <AvatarFallback className="bg-primary/10 text-primary text-xs">
                                                {initials(c.other?.display_name ?? '?')}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <p className="flex items-center gap-1 font-medium">
                                                {c.other?.display_name}
                                                {c.unread && <span className="bg-primary size-2 rounded-full" />}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">{c.last_message ?? 'Say hello'}</p>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Thread */}
                <Card className="flex min-h-[60vh] flex-col overflow-hidden">
                    {active ? (
                        <>
                            <div className="flex items-center justify-between border-b p-4">
                                <p className="font-semibold">{active.other?.display_name}</p>
                            </div>
                            <div className="flex-1 space-y-3 overflow-y-auto p-4">
                                {active.messages.map((m) => (
                                    <div key={m.uuid} className={cn('flex', m.mine ? 'justify-end' : 'justify-start')}>
                                        <div className="group max-w-[75%]">
                                            <div
                                                className={cn(
                                                    'rounded-2xl px-4 py-2 text-sm',
                                                    m.mine ? 'bg-primary text-primary-foreground' : 'bg-muted',
                                                )}
                                            >
                                                {m.body}
                                            </div>
                                            {!m.mine && !m.flagged && (
                                                <button
                                                    onClick={() =>
                                                        router.post(
                                                            route('member.messages.report', { message: m.uuid }),
                                                            {},
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                    className="text-muted-foreground hover:text-destructive mt-1 hidden items-center gap-1 text-[10px] group-hover:flex"
                                                >
                                                    <Flag className="size-3" /> Report
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                <div ref={bottomRef} />
                            </div>
                            <form onSubmit={send} className="flex items-center gap-2 border-t p-3">
                                <Input
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    placeholder="Type a message…"
                                    maxLength={2000}
                                />
                                <Button type="submit" size="icon" disabled={processing || !data.body.trim()}>
                                    <Send className="size-4" />
                                </Button>
                            </form>
                        </>
                    ) : (
                        <div className="text-muted-foreground flex flex-1 flex-col items-center justify-center gap-2 text-sm">
                            <MessageCircle className="text-primary/30 size-10" />
                            Select a conversation to start messaging.
                        </div>
                    )}
                </Card>
            </div>
        </MemberLayout>
    );
}
