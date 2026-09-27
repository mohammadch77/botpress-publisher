// Server datetimes are site-local 'YYYY-MM-DD HH:mm:ss' strings; parse them as local wall-clock time.
export function parseSiteDate(value: string): Date {
  return new Date(value.replace(' ', 'T'))
}

export function formatSiteDate(value: string | null | undefined): string {
  if (!value) return '—'
  const date = parseSiteDate(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

export function relativeFromNow(value: string, now: Date = new Date()): string {
  const diff = parseSiteDate(value).getTime() - now.getTime()
  const minutes = Math.round(diff / 60000)
  if (Math.abs(minutes) < 1) return 'همین حالا'
  const rtf = new Intl.RelativeTimeFormat('fa', { numeric: 'auto' })
  if (Math.abs(minutes) < 60) return rtf.format(minutes, 'minute')
  const hours = Math.round(minutes / 60)
  if (Math.abs(hours) < 48) return rtf.format(hours, 'hour')
  return rtf.format(Math.round(hours / 24), 'day')
}

export const postStatusLabels: Record<string, string> = {
  publish: 'منتشرشده',
  draft: 'پیش‌نویس',
  future: 'زمان‌بندی وردپرس',
  pending: 'در انتظار بررسی',
}

export const targetOptions = [
  { label: 'وردپرس + کانال‌ها', value: 'both' },
  { label: 'فقط کانال‌ها', value: 'channel' },
  { label: 'فقط وردپرس', value: 'wordpress' },
]

export const platformLabels: Record<string, string> = {
  telegram: 'تلگرام',
  bale: 'بله',
  wordpress: 'وردپرس',
}

export const logStatusLabels: Record<string, string> = {
  success: 'موفق',
  failed: 'ناموفق',
  info: 'اطلاع',
}

export const actionLabels: Record<string, string> = {
  publish_wordpress: 'انتشار در وردپرس',
  publish_channel: 'ارسال به کانال',
  publish_post: 'انتشار مقاله',
  publish_now: 'انتشار فوری',
  schedule_post: 'زمان‌بندی',
  cancel_queue: 'لغو زمان‌بندی',
  cancel_queue_item: 'لغو زمان‌بندی',
  test_channel: 'تست کانال',
  test_bot: 'تست ربات',
  webhook: 'وب‌هوک',
  queue: 'صف انتشار',
}

export function labelOf(map: Record<string, string>, key: string | null | undefined): string {
  if (!key) return '—'
  return map[key] ?? key
}
