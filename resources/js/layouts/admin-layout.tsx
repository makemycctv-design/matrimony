import { Link, router, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    BarChart3,
    Bell,
    CreditCard,
    FileText,
    LayoutDashboard,
    LogOut,
    type LucideIcon,
    Menu,
    Settings,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Ticket,
    Users,
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
import { cn } from '@/lib/utils';
import { BreadcrumbItem, SharedData } from '@/types';

interface AdminNavItem {
    title: string;
    url: string;
    icon: LucideIcon;
    permission?: string;
}

interface AdminLayoutProps extends PropsWithChildren {
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
}

export default function AdminLayout({ children, title, breadcrumbs }: AdminLayoutProps) {
    const { auth } = usePage<SharedData>().props;
    const initials = useInitials();
    const [mobileOpen, setMobileOpen] = useState(false);
    const permissions = auth.user?.permissions ?? [];
    const current = typeof window !== 'undefined' ? window.location.pathname : '';

    const can = (permission?: string) =>
        !permission || permissions.includes(permission) || (auth.user?.roles ?? []).some((r) => ['Super Admin', 'Platform Owner'].includes(r));

    const nav: { section: string; items: AdminNavItem[] }[] = [
        {
            section: 'Overview',
            items: [{ title: 'Dashboard', url: route('admin.dashboard'), icon: LayoutDashboard }],
        },
        {
            section: 'Members',
            items: [
                { title: 'Users', url: '/admin/users', icon: Users, permission: 'users.view' },
                { title: 'Profile moderation', url: '/admin/profiles', icon: ShieldCheck, permission: 'profiles.view' },
                { title: 'Verification queue', url: '/admin/verifications', icon: BadgeCheck, permission: 'profiles.verify_documents' },
                { title: 'Abuse reports', url: '/admin/reports', icon: ShieldAlert, permission: 'abuse_reports.view' },
            ],
        },
        {
            section: 'Revenue',
            items: [
                { title: 'Plans', url: '/admin/plans', icon: CreditCard, permission: 'plans.manage' },
                { title: 'Payments', url: '/admin/payments', icon: BarChart3, permission: 'payments.view' },
                { title: 'Coupons', url: '/admin/coupons', icon: Ticket, permission: 'coupons.view' },
            ],
        },
        {
            section: 'Platform',
            items: [
                { title: 'Matching', url: '/admin/matching', icon: Sparkles, permission: 'matching.configure' },
                { title: 'CMS pages', url: '/admin/cms', icon: FileText, permission: 'cms.manage' },
                { title: 'Audit logs', url: '/admin/audit-logs', icon: FileText, permission: 'audit_logs.view' },
                { title: 'Settings', url: '/admin/settings', icon: Settings, permission: 'settings.manage' },
            ],
        },
    ];

    const NavContent = ({ onNavigate }: { onNavigate?: () => void }) => (
        <div className="space-y-6">
            {nav.map((group) => {
                const visible = group.items.filter((i) => can(i.permission));
                if (visible.length === 0) return null;
                return (
                    <div key={group.section}>
                        <p className="text-muted-foreground px-3 pb-2 text-xs font-semibold tracking-wider uppercase">{group.section}</p>
                        <nav className="space-y-1">
                            {visible.map((item) => {
                                const active = current === new URL(item.url, window.location.origin).pathname;
                                return (
                                    <Link
                                        key={item.url}
                                        href={item.url}
                                        onClick={onNavigate}
                                        className={cn(
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                            active
                                                ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                                                : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                        )}
                                    >
                                        <item.icon className="size-5" />
                                        {item.title}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>
                );
            })}
        </div>
    );

    return (
        <div className="bg-muted/20 min-h-svh">
            {/* Fixed sidebar (desktop) */}
            <aside className="border-sidebar-border bg-sidebar fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r lg:flex">
                <div className="border-sidebar-border flex h-16 items-center border-b px-5">
                    <BrandLogo />
                </div>
                <div className="flex-1 overflow-y-auto p-3">
                    <NavContent />
                </div>
                <div className="border-sidebar-border text-muted-foreground border-t p-3 text-xs">Admin console</div>
            </aside>

            <div className="lg:pl-64">
                <header className="border-border/60 bg-background/90 sticky top-0 z-20 border-b backdrop-blur">
                    <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
                        <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open menu">
                                    <Menu className="size-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="bg-sidebar w-72 p-0">
                                <SheetTitle className="sr-only">Admin menu</SheetTitle>
                                <div className="border-sidebar-border flex h-16 items-center border-b px-5">
                                    <BrandLogo />
                                </div>
                                <div className="overflow-y-auto p-3">
                                    <NavContent onNavigate={() => setMobileOpen(false)} />
                                </div>
                            </SheetContent>
                        </Sheet>

                        <div className="min-w-0">
                            {breadcrumbs && breadcrumbs.length > 0 && (
                                <nav className="text-muted-foreground hidden text-xs sm:block" aria-label="Breadcrumb">
                                    {breadcrumbs.map((b, i) => (
                                        <span key={b.href}>
                                            {i > 0 && <span className="px-1">/</span>}
                                            <Link href={b.href} className="hover:text-foreground">
                                                {b.title}
                                            </Link>
                                        </span>
                                    ))}
                                </nav>
                            )}
                        </div>

                        <div className="ml-auto flex items-center gap-1">
                            <LanguageSwitcher />
                            <ThemeToggle />
                            <Button variant="ghost" size="icon" aria-label="Notifications">
                                <Bell className="size-5" />
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="gap-2 px-2">
                                        <Avatar className="size-8">
                                            <AvatarFallback className="bg-secondary/15 text-secondary text-xs">
                                                {initials(auth.user?.name ?? 'A')}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="hidden text-sm font-medium sm:inline">{auth.user?.name}</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56">
                                    <DropdownMenuLabel>
                                        <div className="flex flex-col">
                                            <span className="text-sm font-medium">{auth.user?.name}</span>
                                            <span className="text-muted-foreground text-xs">{(auth.user?.roles ?? []).join(', ')}</span>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href={route('profile.edit')}>
                                            <Settings className="mr-2 size-4" /> Settings
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onClick={() => router.post(route('logout'))}>
                                        <LogOut className="mr-2 size-4" /> Log out
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </header>

                <main className="p-4 sm:p-6 lg:p-8">
                    {title && <h1 className="mb-6 text-2xl font-semibold tracking-tight">{title}</h1>}
                    {children}
                </main>
            </div>
        </div>
    );
}
