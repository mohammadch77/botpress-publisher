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

export interface Role {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
}

export interface AppUser {
    uuid: string;
    name: string;
    email: string;
    status: 'active' | 'inactive' | 'banned';
    tenant_uuid?: string | null;
    roles: Role[];
    created_at: string;
    updated_at: string;
}

export interface Bot {
    uuid: string;
    platform: 'telegram' | 'bale';
    name: string;
    username: string | null;
    token_preview: string | null;
    webhook_url: string | null;
    status: 'active' | 'inactive' | 'error' | 'pending';
    last_error: string | null;
    created_at: string;
    updated_at: string;
}

export type DestinationType =
    | 'wordpress_site'
    | 'telegram_channel'
    | 'telegram_group'
    | 'bale_channel'
    | 'bale_group';

export interface Destination {
    uuid: string;
    type: DestinationType;
    name: string;
    slug: string | null;
    status: 'active' | 'inactive' | 'error' | 'pending';
    last_error: string | null;
    wordpress_site?: { url: string; discovery_status: string } | null;
    telegram_destination?: { bot_id: number; external_chat_id: string; title: string | null } | null;
    bale_destination?: { bot_id: number; external_chat_id: string; title: string | null } | null;
    created_at: string;
    updated_at: string;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export type StatusVariant = 'active' | 'inactive' | 'error' | 'warning' | 'pending';
export type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'neutral';
