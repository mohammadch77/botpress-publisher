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
  channel_id: number | null
  publish_target: 'wordpress' | 'channel' | 'both'
  status: 'pending' | 'processing' | 'published' | 'failed'
  scheduled_at: string
}

export interface PlatformSettings {
  token_masked: string
  has_token: boolean
  webhook_url: string
  webhook_set: boolean
  connected: boolean
}

export interface BotSettings {
  telegram: PlatformSettings
  bale: PlatformSettings
  authorized_users: string[]
  notify_on_publish: boolean
  notify_on_fail: boolean
}
