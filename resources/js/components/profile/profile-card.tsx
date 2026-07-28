import { Link, router } from '@inertiajs/react';
import { BadgeCheck, Heart, Lock, MapPin, Sparkles, Star, User as UserIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { heightFromCm } from '@/lib/format';
import { cn } from '@/lib/utils';
import { MatchCard } from '@/types/profile';

/**
 * Reusable, privacy-aware profile card used across search, matches, shortlist
 * and interests. Photos respect visibility (locked overlay when hidden), and
 * compatibility score/reasons render only when provided.
 */
export default function ProfileCard({ profile, compact = false }: { profile: MatchCard; compact?: boolean }) {
    const detailUrl = route('member.profiles.show', { profile: profile.uuid });

    const shortlist = () => router.post(route('member.shortlist.toggle'), { profile: profile.uuid }, { preserveScroll: true });

    const sendInterest = () => router.post(route('member.interests.store'), { profile: profile.uuid }, { preserveScroll: true });

    return (
        <Card className="group overflow-hidden pt-0 transition-shadow hover:shadow-md">
            <Link href={detailUrl} className="relative block">
                <div className="from-primary/10 to-secondary/10 relative flex aspect-[4/3] items-center justify-center bg-gradient-to-br">
                    {profile.photo_url ? (
                        <img src={profile.photo_url} alt={profile.display_name} className="h-full w-full object-cover" />
                    ) : (
                        <div className="text-primary/40 flex flex-col items-center">
                            {profile.photo_locked ? <Lock className="size-8" /> : <UserIcon className="size-10" />}
                            {profile.photo_locked && <span className="mt-1 text-xs">Photo protected</span>}
                        </div>
                    )}
                    {profile.score !== null && (
                        <span className="bg-background/90 text-primary absolute top-2 right-2 flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold shadow">
                            <Sparkles className="size-3" /> {profile.score}%
                        </span>
                    )}
                    {profile.is_verified && (
                        <span className="bg-secondary text-secondary-foreground absolute top-2 left-2 flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium">
                            <BadgeCheck className="size-3" /> Verified
                        </span>
                    )}
                </div>
            </Link>

            <CardContent className="space-y-3 px-4">
                <div>
                    <Link href={detailUrl} className="hover:text-primary font-semibold">
                        {profile.display_name}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {profile.profile_code}
                        {profile.age ? ` · ${profile.age} yrs` : ''}
                        {profile.height_cm ? ` · ${heightFromCm(profile.height_cm)}` : ''}
                    </p>
                </div>

                <div className="text-muted-foreground space-y-1 text-sm">
                    {profile.profession && <p className="truncate">{profile.profession}</p>}
                    {(profile.city || profile.state) && (
                        <p className="flex items-center gap-1">
                            <MapPin className="size-3.5" /> {[profile.city, profile.state].filter(Boolean).join(', ')}
                        </p>
                    )}
                    {profile.religion && (
                        <p className="truncate">
                            {profile.religion}
                            {profile.mother_tongue ? ` · ${profile.mother_tongue}` : ''}
                        </p>
                    )}
                </div>

                {!compact && profile.reasons && profile.reasons.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {profile.reasons.slice(0, 3).map((r) => (
                            <span key={r} className="bg-primary/5 text-primary rounded-full px-2 py-0.5 text-[11px]">
                                {r}
                            </span>
                        ))}
                    </div>
                )}

                <div className="flex items-center gap-2 pt-1">
                    {profile.interest_sent === 'sent' ? (
                        <Badge variant="secondary" className="flex-1 justify-center py-1.5">
                            Interest sent
                        </Badge>
                    ) : profile.interest_sent === 'accepted' ? (
                        <Badge className="bg-secondary text-secondary-foreground flex-1 justify-center py-1.5">Connected</Badge>
                    ) : (
                        <Button size="sm" className="flex-1" onClick={sendInterest} disabled={profile.interest_sent === 'declined'}>
                            <Heart className="size-4" /> Interest
                        </Button>
                    )}

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button size="icon" variant={profile.is_shortlisted ? 'default' : 'outline'} onClick={shortlist} aria-label="Shortlist">
                                <Star className={cn('size-4', profile.is_shortlisted && 'fill-current')} />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>{profile.is_shortlisted ? 'Remove from shortlist' : 'Add to shortlist'}</TooltipContent>
                    </Tooltip>
                </div>
            </CardContent>
        </Card>
    );
}
