<template>
  <div class="flex flex-col gap-6">
    <div
      v-if="account && account.mode === 'server' && !account.connected"
      class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
    >
      <span>{{ account.error || 'سرویس تولید مقاله هنوز فعال نشده است.' }}</span>
      <router-link to="/ai-settings" class="font-medium text-brand-600 hover:underline">رفتن به تنظیمات هوش مصنوعی ←</router-link>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <StatCard label="اعتبار باقی‌مانده" :value="creditsLabel" :icon="Coins" :loading="loading" />
      <StatCard label="هزینهٔ مقالهٔ استاندارد" value="۱ اعتبار" :icon="FileText" />
      <StatCard label="هزینهٔ مقالهٔ حرفه‌ای" :value="`${(account?.pro_credit_cost ?? 2).toLocaleString('fa-IR')} اعتبار`" :icon="Crown" />
    </div>

    <Card title="مقالهٔ جدید">
      <div class="flex flex-col gap-4">
        <Input v-model="topic" label="موضوع مقاله" placeholder="مثلاً: بهترین روش نگهداری ابزار برقی در زمستان" :disabled="!ready" />
        <Input v-model="keyword" label="کلمهٔ کلیدی اصلی (اختیاری)" placeholder="اگر خالی بماند، هوش مصنوعی پیشنهاد می‌دهد" :disabled="!ready" />
        <div class="flex flex-wrap gap-3">
          <label
            v-for="tier in tiers"
            :key="tier.value"
            class="flex flex-1 cursor-pointer flex-col gap-1 rounded-xl border-2 p-3 text-sm"
            :class="quality === tier.value ? 'border-brand-500 bg-brand-50' : 'border-surface-3'"
          >
            <span class="flex items-center gap-2 font-semibold text-slate-800">
              <input v-model="quality" type="radio" :value="tier.value" :disabled="!ready" />
              {{ tier.label }}
            </span>
            <span class="text-xs leading-6 text-slate-500">{{ tier.description }}</span>
          </label>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="primary" :icon-left="Sparkles" disabled>شروع تحقیق و ساخت بریف</Button>
          <span class="text-xs text-slate-400">موتور تولید مقاله در فازهای بعدی فعال می‌شود.</span>
        </div>
      </div>
    </Card>

    <Card title="مراحل تولید هر مقاله">
      <ol class="flex flex-col gap-3">
        <li v-for="(step, i) in steps" :key="step.title" class="flex items-start gap-3">
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-600">
            {{ (i + 1).toLocaleString('fa-IR') }}
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-800">
              {{ step.title }}
              <Badge v-if="step.approval" variant="warning">تأیید شما</Badge>
            </p>
            <p class="text-xs leading-6 text-slate-500">{{ step.description }}</p>
          </div>
        </li>
      </ol>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Coins, FileText, Crown, Sparkles } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import StatCard from '@/components/ui/StatCard.vue'
import { api } from '@/utils/api'
import type { AiAccount } from '@/types'

const loading = ref(true)
const account = ref<AiAccount | null>(null)
const topic = ref('')
const keyword = ref('')
const quality = ref<'standard' | 'pro'>('standard')

const ready = computed(() => account.value?.mode === 'byok' || !!account.value?.connected)
const creditsLabel = computed(() => {
  if (account.value?.mode === 'byok') return 'کلید شخصی'
  return account.value?.connected ? (account.value.credits ?? 0).toLocaleString('fa-IR') : '—'
})

const tiers = [
  { value: 'standard' as const, label: 'استاندارد', description: 'نگارش با Claude Sonnet، سه تصویر، ویراستاری و ممیزی سئو.' },
  { value: 'pro' as const, label: 'حرفه‌ای', description: 'نگارش با قوی‌ترین مدل و تصاویر با کیفیت بالاتر؛ برای صفحه‌های کلیدی و پول‌ساز.' },
]

const steps = [
  { title: 'تحلیل سایت', description: 'یک بار انجام می‌شود: حوزه، مخاطب، لحن برند، خدمات و صفحه‌های مهم سایت شما شناسایی می‌شود.' },
  { title: 'تحقیق رقبا و ترندها', description: 'نتایج اول گوگل، سؤال‌های پرتکرار (People Also Ask)، ترندها و حجم جست‌وجو بررسی می‌شود.' },
  { title: 'بریف و پیشنهادها', description: 'نیت جست‌وجو، کلیدواژهٔ اصلی و مرتبط، ساختار تیترها، برچسب‌ها، دسته‌بندی و لینک‌های داخلی پیشنهاد می‌شود.', approval: true },
  { title: 'نگارش مقاله', description: 'مقاله بر اساس بریف تأییدشده و پروفایل سایت نوشته می‌شود.' },
  { title: 'ویراستاری و ممیزی سئو', description: 'یک مدل دیگر متن را از نظر طبیعی بودن، سئو و اعتمادسازی بازبینی و اصلاح می‌کند.' },
  { title: 'ساخت تصاویر و پیش‌نویس', description: 'تصاویر ساخته و در کتابخانهٔ رسانه آپلود می‌شوند و مقاله به‌صورت پیش‌نویس ذخیره می‌شود.', approval: true },
  { title: 'انتشار یا زمان‌بندی', description: 'از همین پنل یا ربات، مقاله را منتشر یا زمان‌بندی کنید و به کانال‌ها بفرستید.' },
]

onMounted(async () => {
  try {
    const { data } = await api.get<AiAccount>('/ai/account')
    account.value = data
  } finally {
    loading.value = false
  }
})
</script>
