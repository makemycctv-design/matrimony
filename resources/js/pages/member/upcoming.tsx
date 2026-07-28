import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';

interface UpcomingProps {
    section: string;
    phase: number;
}

/**
 * Honest navigational scaffold for modules delivered in later build phases.
 * Not a fake feature — it clearly states what is arriving and when.
 */
export default function Upcoming({ section, phase }: UpcomingProps) {
    return (
        <MemberLayout title={section}>
            <Head title={section} />
            <Card>
                <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                    <div className="bg-primary/10 text-primary flex size-14 items-center justify-center rounded-2xl">
                        <Construction className="size-7" />
                    </div>
                    <div>
                        <h2 className="text-lg font-semibold">{section} is on the way</h2>
                        <p className="text-muted-foreground mx-auto mt-1 max-w-md text-sm">
                            This module ships in Phase {phase} of the rollout. The data model, routing, and layout are already in place — the
                            interactive screens are being built next.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </MemberLayout>
    );
}
