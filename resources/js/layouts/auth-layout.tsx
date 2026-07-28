import { Link } from '@inertiajs/react';
import { HeartHandshake, ShieldCheck, Sparkles } from 'lucide-react';
import { PropsWithChildren } from 'react';

import BrandLogo from '@/components/brand-logo';
import LanguageSwitcher from '@/components/language-switcher';
import ThemeToggle from '@/components/theme-toggle';
import { useTranslations } from '@/hooks/use-translations';

interface AuthLayoutProps extends PropsWithChildren {
    title: string;
    description: string;
}

/**
 * Split-screen auth shell: a warm brand panel on large screens and a focused
 * form column that works down to mobile. Language + theme controls are always
 * accessible for accessibility and localization.
 */
export default function AuthLayout({ children, title, description }: AuthLayoutProps) {
    const { t } = useTranslations();

    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            {/* Brand panel */}
            <div className="from-primary via-primary to-secondary relative hidden overflow-hidden bg-gradient-to-br lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div className="absolute inset-0 [background-image:radial-gradient(circle_at_20%_30%,white_2px,transparent_2px)] [background-size:36px_36px] opacity-10" />
                <Link href={route('home')} className="relative z-10">
                    <BrandLogo textClassName="text-white" className="text-white" />
                </Link>
                <div className="relative z-10 space-y-6 text-white">
                    <h2 className="max-w-md text-3xl leading-tight font-semibold">{t('app.tagline')}</h2>
                    <ul className="space-y-4 text-sm text-white/90">
                        <li className="flex items-center gap-3">
                            <ShieldCheck className="size-5 shrink-0" /> {t('landing.trust_verified')}
                        </li>
                        <li className="flex items-center gap-3">
                            <HeartHandshake className="size-5 shrink-0" /> {t('landing.trust_private')}
                        </li>
                        <li className="flex items-center gap-3">
                            <Sparkles className="size-5 shrink-0" /> {t('landing.trust_secure')}
                        </li>
                    </ul>
                </div>
                <p className="relative z-10 text-xs text-white/70">
                    © {new Date().getFullYear()} · {t('landing.footer_rights')}
                </p>
            </div>

            {/* Form column */}
            <div className="flex flex-col">
                <div className="flex items-center justify-between p-4 lg:justify-end">
                    <Link href={route('home')} className="lg:hidden">
                        <BrandLogo />
                    </Link>
                    <div className="flex items-center gap-1">
                        <LanguageSwitcher />
                        <ThemeToggle />
                    </div>
                </div>
                <div className="flex flex-1 items-center justify-center p-6 pb-16">
                    <div className="w-full max-w-md space-y-8">
                        <div className="space-y-2 text-center lg:text-left">
                            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                            <p className="text-muted-foreground text-sm">{description}</p>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
