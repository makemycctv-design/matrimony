export interface Plan {
    uuid: string;
    name: string;
    tier: string;
    description: string | null;
    price_paise: number;
    price: number;
    gst_percent: number;
    duration_days: number;
    trial_days: number;
    is_free: boolean;
    is_featured: boolean;
    features: string[];
    limits: {
        contact_view_access: boolean;
        messaging_access: boolean;
        advanced_search: boolean;
        profile_boost: boolean;
        profile_highlight: boolean;
        verification_priority: boolean;
        max_interests_per_day: number | null;
        max_profile_views_per_day: number | null;
    };
}

export interface CheckoutBreakdown {
    subtotal_paise: number;
    discount_paise: number;
    tax_paise: number;
    total_paise: number;
    gst_percent: number;
}
