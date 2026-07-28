import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';

import ProfileCard from '@/components/profile/profile-card';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { MatchCard } from '@/types/profile';

export default function Shortlist({ profiles }: { profiles: MatchCard[] }) {
    return (
        <MemberLayout title="Shortlist">
            <Head title="Shortlist" />

            {profiles.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                        <Star className="text-primary/40 size-8" />
                        You have not shortlisted anyone yet. Tap the star on a profile to save it here.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {profiles.map((p) => (
                        <ProfileCard key={p.uuid} profile={p} />
                    ))}
                </div>
            )}
        </MemberLayout>
    );
}
