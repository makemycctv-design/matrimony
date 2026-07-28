import { LucideIcon } from 'lucide-react';

export interface ProfileSummary {
    uuid: string;
    profile_code: string | null;
    display_name: string;
    status: string | null;
    completion_percentage: number;
    is_verified: boolean;
}

export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    mobile: string | null;
    status: string | null;
    locale: string;
    email_verified: boolean;
    mobile_verified: boolean;
    two_factor_enabled: boolean;
    is_staff: boolean;
    roles: string[];
    permissions: string[];
    profile: ProfileSummary | null;
    avatar?: string;
    [key: string]: unknown;
}

export interface Auth {
    user: User | null;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
    status?: string | null;
}

export interface Branding {
    currency: string;
    currency_symbol: string;
    support_email: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    permission?: string;
    badge?: string | number;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: Flash;
    locale: string;
    locales: string[];
    translations: Record<string, string>;
    branding: Branding;
    [key: string]: unknown;
}
