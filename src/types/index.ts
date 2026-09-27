export type Platform = 'telegram' | 'bale'

export interface Channel {
  id: number
  name: string
  platform: Platform
  chat_id: string
  bot_username?: string | null
  is_active: boolean
  last_error?: string | null
  last_used_at?: string | null
}

export interface DashboardStats {
  total_channels: number
  published_today: number
  pending_in_queue: number
  failed_today: number
  recent_activity: LogEntry[]
}

export interface LogEntry {
  id: number
  post_id: number | null
  channel_id: number | null
  action: string
  platform: Platform | 'wordpress' | null
  status: 'success' | 'failed' | 'info'
  message: string | null
  created_at: string
}

export interface QueueItem {
  id: number
  post_id: number
  post_title?: string | null
  channel_id: number | null
  publish_target: 'wordpress' | 'channel' | 'both'
  status: 'pending' | 'processing' | 'published' | 'failed' | 'cancelled'
  scheduled_at: string
  published_at?: string | null
  attempts: number
  last_error?: string | null
}

export interface PlatformSettings {
  token_masked: string
  has_token: boolean
  webhook_url: string
  webhook_set: boolean
  connected: boolean
}

export type PublishTarget = 'wordpress' | 'channel' | 'both'

export interface PostItem {
  id: number
  title: string
  status: 'publish' | 'draft' | 'future' | 'pending' | string
  date: string
  modified: string
  author: string
  thumbnail: string | null
  edit_url: string
  view_url: string
  queue: { id: number; scheduled_at: string; target: PublishTarget } | null
}

export interface SchedulePreset {
  key: string
  label: string
  at: string
}

export interface WebhookStatus {
  checked: boolean
  matches: boolean
  expected_url: string
  registered_url: string | null
  pending_updates: number | null
  last_error_message: string | null
  last_error_at?: string | null
  last_received_at: string | null
}

export interface BotSettings {
  telegram: PlatformSettings
  bale: PlatformSettings
  authorized_users: string[]
  notify_on_publish: boolean
  notify_on_fail: boolean
}

export type AiMode = 'server' | 'byok'

export interface AiSettings {
  mode: AiMode
  server_url: string
  has_license: boolean
  license_masked: string
  byok_base_url: string
  byok_auth: 'apikey' | 'bearer'
  has_byok_key: boolean
  byok_key_masked: string
  byok_model: string
}

export interface AiAccount {
  mode: AiMode
  connected?: boolean
  error?: string
  customer?: string
  site?: string
  status?: string
  credits?: number
  pro_credit_cost?: number
  features?: { article_generation: boolean }
}
