export interface AdminUser {
    uuid: string;
    name: string;
    email: string;
}

export interface Tenant {
    uuid: string;
    name: string;
    slug: string;
    plan: 'free' | 'pro' | 'enterprise';
    status: 'active' | 'suspended' | 'trial' | 'cancelled';
    created_at: string;
    updated_at: string;
}

export interface DashboardStats {
    tenants: number;
    bots: number;
    destinations: number;
    publications: number;
}

export type StatusVariant = 'active' | 'inactive' | 'error' | 'warning' | 'pending';
export type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'neutral';
