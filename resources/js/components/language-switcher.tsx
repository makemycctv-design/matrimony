import { router, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/hooks/use-translations';
import { SharedData } from '@/types';

const LABELS: Record<string, string> = {
    en: 'English',
    ml: 'മലയാളം',
};

/**
 * Switches the UI locale by POSTing to the locale route; the server persists
 * the choice on the user/session and reloads shared translations.
 */
export default function LanguageSwitcher() {
    const { locale, locales } = useTranslations();
    const { props } = usePage<SharedData>();

    const change = (value: string) => {
        if (value === locale) return;
        router.post(route('locale.switch'), { locale: value }, { preserveScroll: true });
    };

    const available = (props.locales as string[]) ?? locales;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="gap-2" aria-label="Change language">
                    <Languages className="size-4" />
                    <span className="hidden sm:inline">{LABELS[locale] ?? locale.toUpperCase()}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {available.map((code) => (
                    <DropdownMenuItem key={code} onClick={() => change(code)} className={code === locale ? 'font-semibold' : ''}>
                        {LABELS[code] ?? code.toUpperCase()}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
