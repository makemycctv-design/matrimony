import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';

import BrandLogo from '@/components/brand-logo';
import LanguageSwitcher from '@/components/language-switcher';
import ThemeToggle from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { SharedData } from '@/types';

export default function PublicLayout({ children }: PropsWithChildren) {
    const { t } = useTranslations();
    const { auth, name } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);

    const links = [
        { label: t('nav.how_it_works'), href: '/#how-it-works' },
        { label: t('nav.pricing'), href: route('pricing') },
        { label: t('nav.success_stories'), href: '/#stories' },
        { label: t('nav.about'), href: '/#about' },
    ];

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <header className="border-border/60 bg-background/80 sticky top-0 z-40 border-b backdrop-blur-md">
                <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <Link href={route('home')} aria-label={name}>
                        <BrandLogo />
                    </Link>

                    <nav className="hidden items-center gap-8 md:flex" aria-label="Primary">
                        {links.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                className="text-muted-foreground hover:text-foreground text-sm font-medium transition-colors"
                            >
                                {link.label}
                            </a>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-2 md:flex">
                        <LanguageSwitcher />
                        <ThemeToggle />
                        {auth.user ? (
                            <Button asChild>
                                <Link href={auth.user.is_staff ? route('admin.dashboard') : route('dashboard')}>{t('nav.dashboard')}</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={route('login')}>{t('nav.login')}</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('register')}>{t('nav.register')}</Link>
                                </Button>
                            </>
                        )}
                    </div>

                    <div className="flex items-center gap-1 md:hidden">
                        <ThemeToggle />
                        <Button variant="ghost" size="icon" onClick={() => setOpen((v) => !v)} aria-label="Menu">
                            {open ? <X className="size-5" /> : <Menu className="size-5" />}
                        </Button>
                    </div>
                </div>

                {open && (
                    <div className="border-border/60 bg-background border-t md:hidden">
                        <div className="space-y-1 px-4 py-4">
                            {links.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setOpen(false)}
                                    className="text-muted-foreground hover:bg-muted block rounded-md px-3 py-2 text-sm font-medium"
                                >
                                    {link.label}
                                </a>
                            ))}
                            <div className="flex items-center gap-2 px-3 pt-2">
                                <LanguageSwitcher />
                            </div>
                            <div className="grid gap-2 pt-2">
                                {auth.user ? (
                                    <Button asChild>
                                        <Link href={auth.user.is_staff ? route('admin.dashboard') : route('dashboard')}>{t('nav.dashboard')}</Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button variant="outline" asChild>
                                            <Link href={route('login')}>{t('nav.login')}</Link>
                                        </Button>
                                        <Button asChild>
                                            <Link href={route('register')}>{t('nav.register')}</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-border/60 bg-muted/30 border-t">
                <div className="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
                    <div className="space-y-3">
                        <BrandLogo />
                        <p className="text-muted-foreground max-w-xs text-sm">{t('app.description')}</p>
                    </div>
                    <FooterCol title={t('nav.pricing')} items={[t('nav.pricing'), t('nav.how_it_works'), t('nav.success_stories')]} />
                    <FooterCol title={t('nav.about')} items={[t('nav.about'), t('nav.contact'), t('nav.help')]} />
                    <FooterCol title="Legal" items={['Privacy Policy', 'Terms of Service', 'Refund Policy', 'Safety Tips']} />
                </div>
                <div className="border-border/60 border-t">
                    <div className="text-muted-foreground mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm sm:flex-row sm:px-6 lg:px-8">
                        <span>
                            © {new Date().getFullYear()} {name}. {t('landing.footer_rights')}
                        </span>
                        <span>Made with care in India 🇮🇳</span>
                    </div>
                </div>
            </footer>
        </div>
    );
}

function FooterCol({ title, items }: { title: string; items: string[] }) {
    return (
        <div>
            <h3 className="mb-3 text-sm font-semibold">{title}</h3>
            <ul className="text-muted-foreground space-y-2 text-sm">
                {items.map((item) => (
                    <li key={item}>
                        <a href="#" className="hover:text-foreground transition-colors">
                            {item}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}
