<template>
  <div class="flex flex-col gap-6">
    <div v-if="loadError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600">
      {{ loadError }}
      <Button variant="ghost" size="sm" @click="loadSettings">تلاش مجدد</Button>
    </div>

    <BotPlatformCard platform="telegram" title="ربات تلگرام" :settings="settings.telegram" @changed="loadSettings" />
    <BotPlatformCard platform="bale" title="ربات بله" :settings="settings.bale" @changed="loadSettings" />

    <Card title="اعلان‌های ربات">
      <div class="flex flex-col gap-3">
        <p class="text-xs text-slate-400">
          اعلان‌ها برای کاربرانی ارسال می‌شود که با ربات گفتگو کرده‌اند (اگر لیست کاربران مجاز پر باشد، فقط برای همان‌ها).
        </p>
        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-surface-3 px-4 py-3">
          <span class="flex flex-col">
            <span class="text-sm text-slate-700">اطلاع‌رسانی انتشار و زمان‌بندی</span>
            <span class="text-xs text-slate-400">انتشار موفق، زمان‌بندی یا لغو از پنل و انتشار خودکار صف</span>
          </span>
          <input type="checkbox" class="h-4 w-4" :checked="settings.notify_on_publish" @change="toggleNotify('notify_on_publish', $event)" />
        </label>
        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-surface-3 px-4 py-3">
          <span class="flex flex-col">
            <span class="text-sm text-slate-700">اطلاع‌رسانی خطا</span>
            <span class="text-xs text-slate-400">وقتی انتشار (فوری یا زمان‌بندی‌شده) ناموفق شود</span>
          </span>
          <input type="checkbox" class="h-4 w-4" :checked="settings.notify_on_fail" @change="toggleNotify('notify_on_fail', $event)" />
        </label>
      </div>
    </Card>

    <Card title="کاربران مجاز">
      <div class="flex flex-col gap-3">
        <p class="text-sm text-slate-500">شناسهٔ عددی کاربرانی از تلگرام یا بله که اجازهٔ کنترل ربات را دارند.</p>
        <p class="text-xs text-slate-400">اگر لیست خالی باشد، همه کاربران می‌توانند از ربات استفاده کنند.</p>
        <div class="flex items-end gap-2">
          <Input v-model="newUserId" label="شناسهٔ کاربر (User ID)" placeholder="123456789" class="flex-1" :error="userError" />
          <Button variant="outline" :loading="savingUsers" @click="addUser">افزودن</Button>
        </div>
        <ul class="flex flex-col gap-2">
          <li
            v-for="user in settings.authorized_users"
            :key="user"
            class="flex items-center justify-between rounded-lg border border-surface-3 px-3 py-2 text-sm"
          >
            <span dir="ltr" class="font-mono">{{ user }}</span>
            <Button variant="ghost" size="sm" :disabled="savingUsers" @click="removeUser(user)">حذف</Button>
          </li>
        </ul>
        <EmptyState v-if="settings.authorized_users.length === 0" message="هنوز کاربر مجازی اضافه نشده است" />
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import BotPlatformCard from '@/components/BotPlatformCard.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { BotSettings } from '@/types'

const toast = useToast()

const newUserId = ref('')
const userError = ref('')
const savingUsers = ref(false)
const loadError = ref('')

const settings = reactive<BotSettings>({
  telegram: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
  bale: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
  authorized_users: [],
  notify_on_publish: true,
  notify_on_fail: true,
})

async function loadSettings() {
  try {
    const { data } = await api.get('/settings')
    Object.assign(settings, data)
    loadError.value = ''
  } catch {
    loadError.value = 'بارگذاری تنظیمات ناموفق بود.'
  }
}

async function toggleNotify(key: 'notify_on_publish' | 'notify_on_fail', event: Event) {
  const value = (event.target as HTMLInputElement).checked
  const previous = settings[key]
  settings[key] = value
  try {
    await api.post('/settings', { [key]: value ? 1 : 0 })
    toast.success('ذخیره شد.')
  } catch {
    settings[key] = previous
  }
}

async function saveUsers(users: string[]): Promise<boolean> {
  savingUsers.value = true
  try {
    await api.post('/settings', { authorized_users: users })
    await loadSettings()
    return true
  } catch {
    return false
  } finally {
    savingUsers.value = false
  }
}

async function addUser() {
  const id = newUserId.value.trim()
  userError.value = ''
  if (!id) return
  if (!/^-?\d+$/.test(id)) {
    userError.value = 'شناسهٔ کاربر باید فقط عدد باشد.'
    return
  }
  if (settings.authorized_users.includes(id)) {
    userError.value = 'این کاربر قبلاً اضافه شده است.'
    return
  }
  if (await saveUsers([...settings.authorized_users, id])) {
    newUserId.value = ''
    toast.success('کاربر افزوده شد.')
  }
}

async function removeUser(user: string) {
  if (await saveUsers(settings.authorized_users.filter((u) => u !== user))) {
    toast.success('کاربر حذف شد.')
  }
}

onMounted(loadSettings)
</script>
