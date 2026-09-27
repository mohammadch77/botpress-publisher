<template>
  <div class="flex flex-col gap-6">
    <div v-if="loadError" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600">
      {{ loadError }}
      <Button variant="ghost" size="sm" @click="load">تلاش مجدد</Button>
    </div>

    <Card title="روش اتصال به هوش مصنوعی">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <button
          v-for="option in modeOptions"
          :key="option.value"
          type="button"
          class="flex flex-col items-start gap-1 rounded-xl border-2 p-4 text-start transition-colors"
          :class="form.mode === option.value ? 'border-brand-500 bg-brand-50' : 'border-surface-3 hover:border-brand-200'"
          @click="form.mode = option.value"
        >
          <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <component :is="option.icon" class="h-4 w-4 text-brand-600" />
            {{ option.label }}
            <Badge v-if="option.recommended" variant="info">پیشنهادی</Badge>
          </span>
          <span class="text-xs leading-6 text-slate-500">{{ option.description }}</span>
        </button>
      </div>
    </Card>

    <Card v-if="form.mode === 'server'" title="لایسنس سرویس تولید مقاله">
      <div class="flex flex-col gap-4">
        <p class="text-sm leading-7 text-slate-500">
          کلید لایسنسی را که بعد از خرید دریافت کرده‌اید وارد کنید. همهٔ پردازش‌ها روی سرور ما انجام می‌شود و به ازای هر مقاله از اعتبار شما کم می‌شود.
        </p>
        <Input
          v-model="form.license_key"
          label="کلید لایسنس"
          :placeholder="settings.has_license ? `${settings.license_masked} (برای تغییر، کلید جدید وارد کنید)` : 'BPAI-XXXXX-XXXXX-XXXXX-XXXXX'"
          dir="ltr"
        />
        <details class="rounded-lg border border-surface-3 px-3 py-2 text-sm">
          <summary class="cursor-pointer text-slate-600">تنظیمات پیشرفته</summary>
          <div class="mt-3">
            <Input v-model="form.server_url" label="آدرس سرور هوش مصنوعی" dir="ltr" placeholder="https://example.com" />
            <p class="mt-1 text-xs text-slate-400">فقط در صورتی تغییر دهید که پشتیبانی از شما خواسته باشد.</p>
          </div>
        </details>
      </div>
    </Card>

    <Card v-else title="اتصال با کلید شخصی">
      <div class="flex flex-col gap-4">
        <p class="text-sm leading-7 text-slate-500">
          از درگاه هوش مصنوعی خودتان (مثلاً AI Gateway آروان‌کلاد یا OpenRouter) استفاده کنید. هزینهٔ توکن‌ها مستقیم از حساب خودتان کم می‌شود.
        </p>
        <Input v-model="form.byok_base_url" label="آدرس درگاه (Base URL)" dir="ltr" placeholder="https://.../v1" />
        <Select
          v-model="form.byok_auth"
          label="نوع احراز هویت"
          :options="[
            { label: 'apikey (آروان‌کلاد)', value: 'apikey' },
            { label: 'Bearer (OpenAI / OpenRouter)', value: 'bearer' },
          ]"
        />
        <Input
          v-model="form.byok_key"
          label="کلید API"
          type="password"
          dir="ltr"
          :placeholder="settings.has_byok_key ? `${settings.byok_key_masked} (برای تغییر، کلید جدید وارد کنید)` : ''"
        />
        <Input v-model="form.byok_model" label="مدل نگارش" dir="ltr" placeholder="Claude-Sonnet-4.6" />
      </div>
    </Card>

    <div class="flex flex-wrap items-center gap-3">
      <Button variant="primary" :loading="saving" @click="save">ذخیرهٔ تنظیمات</Button>
      <Button variant="outline" :loading="testing" @click="test">تست اتصال</Button>
      <span v-if="testMessage" class="text-sm" :class="testSuccess ? 'text-emerald-600' : 'text-red-500'">{{ testMessage }}</span>
    </div>

    <Card v-if="form.mode === 'server' && account" title="وضعیت حساب">
      <div v-if="account.connected" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard label="اعتبار باقی‌مانده" :value="(account.credits ?? 0).toLocaleString('fa-IR')" :icon="Coins" />
        <StatCard label="مشتری" :value="account.customer || '—'" :icon="User" />
        <StatCard label="سایت ثبت‌شده" :value="account.site || '—'" :icon="Globe" />
      </div>
      <p v-else class="text-sm text-red-500">{{ account.error }}</p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Server, KeyRound, Coins, User, Globe } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import StatCard from '@/components/ui/StatCard.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { AiAccount, AiMode, AiSettings } from '@/types'

const toast = useToast()

const modeOptions: { value: AiMode; label: string; description: string; icon: any; recommended?: boolean }[] = [
  {
    value: 'server',
    label: 'سرویس آماده (با لایسنس)',
    description: 'بدون نیاز به حساب هوش مصنوعی یا DataForSEO. تحقیق رقبا، ترندها، تصویر و ویراستاری همه آماده است.',
    icon: Server,
    recommended: true,
  },
  {
    value: 'byok',
    label: 'کلید شخصی (BYOK)',
    description: 'برای کاربران حرفه‌ای که خودشان حساب درگاه هوش مصنوعی دارند و هزینهٔ توکن را مستقیم پرداخت می‌کنند.',
    icon: KeyRound,
  },
]

const settings = reactive<AiSettings>({
  mode: 'server',
  server_url: '',
  has_license: false,
  license_masked: '',
  byok_base_url: '',
  byok_auth: 'apikey',
  has_byok_key: false,
  byok_key_masked: '',
  byok_model: '',
})

const form = reactive({
  mode: 'server' as AiMode,
  server_url: '',
  license_key: '',
  byok_base_url: '',
  byok_auth: 'apikey',
  byok_key: '',
  byok_model: '',
})

const account = ref<AiAccount | null>(null)
const loadError = ref('')
const saving = ref(false)
const testing = ref(false)
const testMessage = ref('')
const testSuccess = ref(false)

function syncForm() {
  form.mode = settings.mode
  form.server_url = settings.server_url
  form.byok_base_url = settings.byok_base_url
  form.byok_auth = settings.byok_auth
  form.byok_model = settings.byok_model
  form.license_key = ''
  form.byok_key = ''
}

async function loadAccount() {
  if (settings.mode !== 'server' || !settings.has_license) {
    account.value = null
    return
  }
  const { data } = await api.get<AiAccount>('/ai/account')
  account.value = data
}

async function load() {
  try {
    const { data } = await api.get<AiSettings>('/ai/settings')
    Object.assign(settings, data)
    syncForm()
    loadError.value = ''
    await loadAccount()
  } catch {
    loadError.value = 'بارگذاری تنظیمات هوش مصنوعی ناموفق بود.'
  }
}

async function save() {
  saving.value = true
  testMessage.value = ''
  try {
    const { data } = await api.post<AiSettings>('/ai/settings', { ...form })
    Object.assign(settings, data)
    syncForm()
    toast.success('تنظیمات ذخیره شد.')
    await loadAccount()
  } catch {
    // Error toast is shown by the API interceptor.
  } finally {
    saving.value = false
  }
}

async function test() {
  testing.value = true
  testMessage.value = ''
  try {
    const { data } = await api.post('/ai/test')
    testSuccess.value = !!data.success
    if (!data.success) {
      testMessage.value = `❌ ${data.message}`
    } else if (data.mode === 'server') {
      testMessage.value = `✅ اتصال به سرور برقرار است — اعتبار: ${(data.result.credits ?? 0).toLocaleString('fa-IR')}`
      account.value = { mode: 'server', connected: true, ...data.result }
    } else {
      testMessage.value = `✅ مدل ${data.result.model} پاسخ داد: «${data.result.reply}»`
    }
  } finally {
    testing.value = false
  }
}

onMounted(load)
</script>
