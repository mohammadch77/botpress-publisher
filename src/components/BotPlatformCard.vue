<template>
  <Card :title="title">
    <template #action>
      <span class="flex items-center gap-2 text-xs text-slate-500">
        <StatusDot :status="overallStatus" />
        {{ overallLabel }}
      </span>
    </template>

    <div class="flex flex-col gap-5">
      <section class="flex flex-col gap-2">
        <label class="text-sm font-medium text-slate-700">توکن ربات</label>
        <div class="flex items-stretch gap-2">
          <div class="relative flex-1">
            <input
              v-model="token"
              dir="ltr"
              :type="showToken ? 'text' : 'password'"
              :placeholder="settings.token_masked || '123456789:ABCdef...'"
              autocomplete="off"
              spellcheck="false"
              class="btn-focus w-full rounded-lg border border-surface-3 py-2 pl-10 pr-3 text-left font-mono text-sm text-slate-800 placeholder:text-slate-400"
              @keydown.enter="saveToken"
            />
            <button
              type="button"
              class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 hover:text-slate-600"
              :title="showToken ? 'مخفی کردن' : 'نمایش'"
              @click="showToken = !showToken"
            >
              <component :is="showToken ? EyeOff : Eye" class="h-4 w-4" />
            </button>
          </div>
          <Button variant="primary" :loading="saving" :disabled="!token.trim()" @click="saveToken">ذخیره توکن</Button>
        </div>
        <p v-if="tokenError" class="text-xs text-red-500">{{ tokenError }}</p>
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" :loading="testing" :disabled="!settings.has_token" @click="testBot">
            تست اتصال ربات
          </Button>
          <span v-if="testResult" class="text-sm" :class="testResult.success ? 'text-emerald-600' : 'text-red-500'">
            {{ testResult.success ? `✅ متصل: @${testResult.bot_username || ''}` : `❌ ${testResult.message}` }}
          </span>
        </div>
      </section>

      <section class="flex flex-col gap-3 border-t border-surface-3 pt-5">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-medium text-slate-700">وب‌هوک</h4>
          <Button variant="ghost" size="sm" :loading="checking" :disabled="!settings.has_token" @click="checkStatus()">
            بررسی وضعیت
          </Button>
        </div>

        <div class="flex items-stretch gap-2">
          <input
            :value="settings.webhook_url"
            dir="ltr"
            readonly
            class="flex-1 rounded-lg border border-surface-3 bg-surface-2 px-3 py-2 text-left font-mono text-xs text-slate-600"
          />
          <Button variant="outline" size="sm" @click="copyUrl">{{ copied ? 'کپی شد' : 'کپی' }}</Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Button variant="primary" :loading="settingWebhook" :disabled="!settings.has_token" @click="setWebhook">
            {{ webhookActive ? 'تنظیم مجدد وب‌هوک' : 'فعال‌سازی وب‌هوک' }}
          </Button>
          <span class="text-sm" :class="webhookActive ? 'text-emerald-600' : 'text-slate-400'">
            {{ webhookActive ? '✅ فعال' : '❌ غیرفعال' }}
          </span>
        </div>

        <p v-if="webhookError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ webhookError }}</p>

        <dl v-if="status" class="grid grid-cols-1 gap-x-6 gap-y-2 rounded-lg bg-surface-1 p-3 text-xs sm:grid-cols-2">
          <div class="flex flex-col gap-0.5">
            <dt class="text-slate-400">آدرس ثبت‌شده در {{ title }}</dt>
            <dd dir="ltr" class="break-all text-left font-mono" :class="status.matches ? 'text-emerald-600' : 'text-red-500'">
              {{ status.registered_url || (status.checked ? '— ثبت نشده —' : 'نامشخص') }}
            </dd>
          </div>
          <div class="flex flex-col gap-0.5">
            <dt class="text-slate-400">آخرین پیام دریافتی از ربات</dt>
            <dd class="text-slate-700">{{ status.last_received_at ? formatDate(status.last_received_at) : 'هنوز پیامی دریافت نشده' }}</dd>
          </div>
          <div v-if="status.pending_updates !== null" class="flex flex-col gap-0.5">
            <dt class="text-slate-400">پیام‌های در صف تحویل</dt>
            <dd class="text-slate-700">{{ status.pending_updates }}</dd>
          </div>
          <div v-if="status.last_error_message" class="flex flex-col gap-0.5 sm:col-span-2">
            <dt class="text-slate-400">آخرین خطای تحویل{{ status.last_error_at ? ` (${formatDate(status.last_error_at)})` : '' }}</dt>
            <dd dir="ltr" class="text-left text-red-500">{{ status.last_error_message }}</dd>
          </div>
        </dl>
      </section>

      <BotActivityLog v-if="settings.has_token" :platform="platform" />
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import StatusDot from '@/components/ui/StatusDot.vue'
import BotActivityLog from '@/components/BotActivityLog.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { PlatformSettings, WebhookStatus } from '@/types'

const props = defineProps<{
  platform: 'telegram' | 'bale'
  title: string
  settings: PlatformSettings
}>()

const emit = defineEmits<{ (e: 'changed'): void }>()

const toast = useToast()
const TOKEN_PATTERN = /^\d+:[A-Za-z0-9_-]+$/

const token = ref('')
const showToken = ref(false)
const saving = ref(false)
const testing = ref(false)
const settingWebhook = ref(false)
const checking = ref(false)
const copied = ref(false)
const tokenError = ref('')
const webhookError = ref('')
const testResult = ref<{ success: boolean; message?: string; bot_username?: string } | null>(null)
const status = ref<WebhookStatus | null>(null)

const webhookActive = computed(() => (status.value?.checked ? status.value.matches : props.settings.webhook_set))

const overallStatus = computed(() => {
  if (!props.settings.has_token) return 'inactive'
  if (status.value?.last_error_message || (status.value?.checked && !status.value.matches)) return 'warning'
  return webhookActive.value ? 'active' : 'warning'
})

const overallLabel = computed(() => {
  if (!props.settings.has_token) return 'توکن ثبت نشده'
  if (!webhookActive.value) return 'وب‌هوک تنظیم نشده'
  if (status.value?.last_error_message) return 'خطای تحویل'
  return 'فعال'
})

function errorMessage(e: any, fallback: string): string {
  return e?.response?.data?.message || e?.message || fallback
}

async function saveToken() {
  const value = token.value.trim()
  tokenError.value = ''
  if (!value) return
  if (!TOKEN_PATTERN.test(value)) {
    tokenError.value = 'فرمت توکن نامعتبر است. توکن باید به شکل 123456789:ABCdef... باشد (بدون فاصله).'
    return
  }

  saving.value = true
  try {
    await api.post('/settings', { [`${props.platform}_token`]: value })
    token.value = ''
    showToken.value = false
    testResult.value = null
    status.value = null
    toast.success('توکن ذخیره شد. حالا «فعال‌سازی وب‌هوک» را بزنید.')
    emit('changed')
  } catch (e) {
    tokenError.value = errorMessage(e, 'خطا در ذخیره توکن.')
  } finally {
    saving.value = false
  }
}

async function testBot() {
  testing.value = true
  try {
    const { data } = await api.post('/settings/test-bot', { platform: props.platform })
    testResult.value = {
      success: !!data.success,
      message: data.message || 'اتصال ناموفق',
      bot_username: data.bot_username,
    }
  } catch (e) {
    testResult.value = { success: false, message: errorMessage(e, 'اتصال ناموفق') }
  } finally {
    testing.value = false
  }
}

async function setWebhook() {
  settingWebhook.value = true
  webhookError.value = ''
  try {
    const { data } = await api.post('/webhook/set', { platform: props.platform })
    if (data.success) {
      status.value = data.webhook ?? null
      toast.success(data.message || 'وب‌هوک با موفقیت تنظیم شد.')
      emit('changed')
    } else {
      webhookError.value = data.message || 'خطا در تنظیم وب‌هوک.'
      await checkStatus(true)
    }
  } catch (e) {
    webhookError.value = errorMessage(e, 'خطا در تنظیم وب‌هوک.')
  } finally {
    settingWebhook.value = false
  }
}

async function checkStatus(silent = false) {
  if (!props.settings.has_token) return
  checking.value = true
  try {
    const { data } = await api.get('/webhook/status', { params: { platform: props.platform } })
    if (data.success) status.value = data.webhook
    else if (!silent) webhookError.value = data.message
  } catch (e) {
    if (!silent) webhookError.value = errorMessage(e, 'خطا در بررسی وضعیت وب‌هوک.')
  } finally {
    checking.value = false
  }
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(props.settings.webhook_url)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch {
    toast.error('کپی ناموفق بود؛ آدرس را دستی کپی کنید.')
  }
}

function formatDate(value: string) {
  return new Date(value).toLocaleString('fa-IR')
}

watch(
  () => props.settings.has_token,
  (has, had) => {
    if (has && !had) checkStatus(true)
  }
)

onMounted(() => checkStatus(true))
</script>
