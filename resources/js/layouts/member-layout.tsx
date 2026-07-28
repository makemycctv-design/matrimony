import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Heart,
    LayoutDashboard,
    LogOut,
    type LucideIcon,
    Menu,
    MessageCircle,
    Search,
    Settings,
    Sparkles,
    Star,
    User as UserIcon,
} from 'lucide-react';
import { PropsWithChildren, useState } from 'react';

import BrandLogo from '@/components/brand-logo';
import LanguageSwitcher from '@/components/language-switcher';
import ThemeToggle from '@/components/theme-toggle';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useInitials } from '@/hooks/use-initials';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import { BreadcrumbItem, SharedData } from '@/types';

interface MemberLayoutProps extends PropsWithChildren {
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
}

export default function MemberLayout({ children, title }: MemberLayoutProps) {
    const { t } = useTranslations();
    const { auth } = usePage<SharedData>().props;
    const initials = useInitials();
    const [mobileOpen, setMobileOpen] = useState(false);
    const current = typeof window !== 'undefined' ? window.location.pathname : '';

    const nav: { title: string; url: string; icon: LucideIcon }[] = [
        { title: t('nav.dashboard'), url: route('dashboard'), icon: LayoutDashboard },
        { title: t('nav.matches'), url: '/matches', icon: Sparkles },
        { title: t('nav.search'), url: '/search', icon: Search },
        { title: t('nav.interests'), url: '/interests', icon: Heart },
        { title: t('nav.shortlist'), url: '/shortlist', icon: Star },
        { title: t('nav.messages'), url: '/messages', icon: MessageCircle },
        { title: t('nav.my_profile'), url: '/my-profile', icon: UserIcon },
    ];

    const NavLinks = ({ onNavigate }: { onNavigate?: () => void }) => (
        <nav className="space-y-1" aria-label="Member">
            {nav.map((item) => {
                const active = current === new URL(item.url, window.location.origin).pathname;
                return (
                    <Link
                        key={item.url}
                        href={item.url}
                        onClick={onNavigate}
                        className={cn(
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            active ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                    >
                        <item.icon className="size-5" />
                        {item.title}
                    </Link>
                );
            })}
        </nav>
    );

    return (
        <div className="bg-muted/20 min-h-svh">
            {/* Top bar */}
            <header className="border-border/60 bg-background/90 sticky top-0 z-40 border-b backdrop-blur">
                <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open menu">
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-72 p-0">
                            <SheetTitle className="sr-only">Menu</SheetTitle>
                            <div className="flex h-16 items-center border-b px-4">
                                <BrandLogo />
                            </div>
                            <div className="p-3">
                                <NavLinks onNavigate={() => setMobileOpen(false)} />
                            </div>
                        </SheetContent>
                    </Sheet>

                    <Link href={route('dashboard')} className="lg:hidden">
                        <BrandLogo showText={false} />
                    </Link>

                    <div className="ml-auto flex items-center gap-1">
                        <LanguageSwitcher />
                        <ThemeToggle />
                        <Button variant="ghost" size="icon" aria-label="Notifications" asChild>
                            <Link href="/notifications">
                                <Bell className="size-5" />
                            </Link>
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="gap-2 px-2">
                                    <Avatar className="size-8">
                                        <AvatarFallback className="bg-primary/10 text-primary text-xs">
                                            {initials(auth.user?.name ?? 'M')}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="hidden text-sm font-medium sm:inline">{auth.user?.name}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>
                                    <div className="flex flex-col">
                                        <span className="text-sm font-medium">{auth.user?.name}</span>
                                        <span className="text-muted-foreground text-xs">{auth.user?.profile?.profile_code ?? auth.user?.email}</span>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/my-profile">
                                        <UserIcon className="mr-2 size-4" /> {t('nav.my_profile')}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={route('profile.edit')}>
                                        <Settings className="mr-2 size-4" /> {t('nav.settings')}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => router.post(route('logout'))}>
                                    <LogOut className="mr-2 size-4" /> {t('nav.logout')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <div className="mx-auto flex w-full max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
                {/* Desktop sidebar */}
                <aside className="hidden w-60 shrink-0 lg:block">
                    <div className="sticky top-24">
                        <NavLinks />
                        <div className="from-primary/5 to-secondary/5 mt-6 rounded-xl border bg-gradient-to-br p-4">
                            <p className="text-sm font-semibold">{t('nav.subscription')}</p>
                            <p className="text-muted-foreground mt-1 text-xs">Upgrade to unlock contact details and unlimited interests.</p>
                            <Button size="sm" className="mt-3 w-full" asChild>
                                <Link href="/subscription">{t('landing.hero_secondary')}</Link>
                            </Button>
                        </div>
                    </div>
                </aside>

                <main className="min-w-0 flex-1">
                    {title && <h1 className="mb-4 text-2xl font-semibold tracking-tight">{title}</h1>}
                    {children}
                </main>
            </div>
        </div>
    );
}
