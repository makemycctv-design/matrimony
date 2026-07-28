import { Head, Link } from '@inertiajs/react';
import { MapPin, Search, Sparkles, Star, UserPlus } from 'lucide-react';

import ProfileCard from '@/components/profile/profile-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { MatchCard } from '@/types/profile';

interface Props {
    sections: {
        recommended: MatchCard[];
        new_matches: MatchCard[];
        near_you: MatchCard[];
        verified: MatchCard[];
    };
}

export default function Matches({ sections }: Props) {
    return (
        <MemberLayout title="Matches">
            <Head title="Matches" />

            <Section
                title="Recommended for You"
                icon={Sparkles}
                description="Ranked by your preferences — scores are guidance, never a guarantee."
                profiles={sections.recommended}
            />
            <Section title="Recently Joined" icon={UserPlus} profiles={sections.new_matches} />
            <Section title="Near You" icon={MapPin} profiles={sections.near_you} />
            <Section title="Verified Matches" icon={Star} profiles={sections.verified} />
        </MemberLayout>
    );
}

function Section({
    title,
    description,
    icon: Icon,
    profiles,
}: {
    title: string;
    description?: string;
    icon: typeof Sparkles;
    profiles: MatchCard[];
}) {
    return (
        <section className="mb-10">
            <div className="mb-4 flex items-center gap-2">
                <Icon className="text-primary size-5" />
                <div>
                    <h2 className="text-lg font-semibold">{title}</h2>
                    {description && <p className="text-muted-foreground text-xs">{description}</p>}
                </div>
            </div>

            {profiles.length === 0 ? (
                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-3 py-10 text-center text-sm">
                        No profiles here yet. Complete your profile and preferences to see better matches.
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('member.search')}>
                                <Search className="size-4" /> Search profiles
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {profiles.map((p) => (
                        <ProfileCard key={p.uuid} profile={p} />
                    ))}
                </div>
            )}
        </section>
    );
}
