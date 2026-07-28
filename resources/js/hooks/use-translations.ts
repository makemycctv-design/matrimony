import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Lightweight i18n helper backed by the `translations` prop shared from
 * Laravel (lang/{locale}.json). Supports :name / {name} placeholder
 * replacement and a fallback string.
 *
 * Usage: const { t, locale } = useTranslations(); t('nav.home')
 */
export function useTranslations() {
    const page = usePage<SharedData>();
    const messages = page.props.translations ?? {};
    const locale = page.props.locale ?? 'en';
    const locales = page.props.locales ?? ['en'];

    const t = (key: string, replacements: Record<string, string | number> = {}, fallback?: string): string => {
        let message = messages[key] ?? fallback ?? key;

        for (const [token, value] of Object.entries(replacements)) {
            message = message.replaceAll(`:${token}`, String(value)).replaceAll(`{${token}}`, String(value));
        }

        return message;
    };

    return { t, locale, locales };
}
