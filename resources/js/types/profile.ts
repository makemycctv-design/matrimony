export interface Option {
    id: number;
    name: string;
    parent_id?: number | null;
    religion_id?: number | null;
    caste_id?: number | null;
    category?: string | null;
}

export interface ProfileOptions {
    religions: Option[];
    castes: Option[];
    sub_castes: Option[];
    mother_tongues: Option[];
    educations: Option[];
    professions: Option[];
    countries: Option[];
    states: Option[];
    districts: Option[];
    cities: Option[];
}

export interface Photo {
    uuid: string;
    url: string;
    thumb_url: string;
    is_primary: boolean;
    status: 'pending' | 'approved' | 'rejected';
    moderation_reason: string | null;
    sort_order: number;
}

export interface Document {
    uuid: string;
    type: string;
    type_label: string;
    last4: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    original_name: string | null;
    mime_type: string | null;
    url: string;
    uploaded_at: string | null;
}

export interface VerificationEntry {
    scope: string;
    action: string;
    reason: string | null;
    actor: string | null;
    at: string | null;
}

export interface Preferences {
    photo_visibility: string;
    contact_visibility: string;
    horoscope_visibility: string;
    income_visibility: string;
    interest_from: string;
    appear_in_search: boolean;
    visible_to_verified_only: boolean;
    visible_to_premium_only: boolean;
    hide_details_until_interest_accepted: boolean;
    show_online_status: boolean;
    show_last_seen: boolean;
    notify_email: boolean;
    notify_sms: boolean;
    notify_whatsapp: boolean;
    notify_in_app: boolean;
    notify_push: boolean;
    [key: string]: string | boolean;
}

export interface MatchCard {
    uuid: string;
    profile_code: string | null;
    display_name: string;
    age: number | null;
    height_cm: number | null;
    gender: string | null;
    religion: string | null;
    mother_tongue: string | null;
    profession: string | null;
    education: string | null;
    city: string | null;
    state: string | null;
    is_verified: boolean;
    has_photo: boolean;
    photo_url: string | null;
    photo_locked: boolean;
    last_active_at: string | null;
    is_shortlisted: boolean;
    interest_sent: string | null;
    interest_received: string | null;
    score: number | null;
    reasons: string[] | null;
}

// Loosely typed bag of profile column values used to hydrate the wizard form.
export type ProfileFields = Record<string, string | number | boolean | null | string[] | Record<string, unknown>>;

export interface OwnerProfile {
    uuid: string;
    profile_code: string | null;
    status: string | null;
    is_verified: boolean;
    rejection_reason: string | null;
    completion_percentage: number;
    fields: ProfileFields;
    photos: Photo[];
    documents: Document[];
    verifications: VerificationEntry[];
    preferences: Preferences | null;
}
