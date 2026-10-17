export type Role = 'super_admin' | 'tenant_admin' | 'barber';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: Role;
    email_verified_at: string | null;
    tenant_id: number | null;
}

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    status: 'pending' | 'active' | 'suspended' | 'archived';
    logo_path: string | null;
    phone: string | null;
    address: string | null;
    subscription?: Subscription | null;
}

export interface Subscription {
    id: number;
    status: 'pending' | 'active' | 'expired' | 'suspended' | 'cancelled';
    starts_at: string | null;
    ends_at: string | null;
    days_remaining?: number | null;
    plan?: SubscriptionPlan | { id: number; name: string } | null;
}

export interface SubscriptionPlan {
    id: number;
    name: string;
    slug: string;
    price: number;
    duration_days: number;
    features: string[] | null;
    is_active: boolean;
}

export interface Invoice {
    id: number;
    number: string;
    status: 'unpaid' | 'pending' | 'paid' | 'rejected' | 'expired' | 'cancelled';
    amount: number;
    currency: string;
    issued_at: string;
    due_at: string | null;
    paid_at: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: AuthUser | null;
    };
    tenant?: Tenant | null;
    flash: {
        success?: string | null;
        error?: string | null;
    };
    ziggy: {
        location: string;
        url: string;
        [key: string]: unknown;
    };
};

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}
