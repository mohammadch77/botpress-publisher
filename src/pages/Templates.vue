<template>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <Card title="Message Templates">
      <div class="flex flex-col gap-4">
        <div class="flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="rounded-lg px-3 py-1.5 text-sm font-medium"
            :class="activeTab === tab.key ? 'bg-brand-500 text-white' : 'bg-surface-2 text-slate-600'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </div>

        <textarea
          v-model="templates[activeTab]"
          rows="10"
          class="btn-focus rounded-lg border border-surface-3 p-3 font-mono text-sm text-slate-800"
        />
        <p class="text-xs" :class="charCount > 4096 ? 'text-red-500' : 'text-slate-400'">
          {{ charCount }} / 4096 کاراکتر (محدودیت تلگرام)
        </p>

        <div class="flex flex-wrap gap-2">
          <Badge
            v-for="(description, variable) in variables"
            :key="variable"
            variant="info"
            class="cursor-pointer"
            :title="`${description} — کلیک برای کپی`"
            @click="copyVariable(variable)"
          >
            {{ variable }}
          </Badge>
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" :loading="previewing" @click="handlePreview">پیش‌نمایش</Button>
          <Button variant="primary" :loading="saving" @click="handleSave">ذخیره</Button>
        </div>
        <p v-if="copiedVariable" class="text-xs text-emerald-600">{{ copiedVariable }} کپی شد</p>
      </div>
    </Card>

    <Card title="Preview">
      <div class="rounded-lg border border-surface-3 bg-surface-1 p-4">
        <div class="mx-auto max-w-sm rounded-2xl bg-[#5288c1] p-3">
          <div class="whitespace-pre-wrap rounded-lg rounded-tl-none bg-white p-3 text-sm text-slate-800 shadow-sm" v-html="preview" />
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const tabs = [
  { key: 'default', label: 'پیش‌فرض' },
  { key: 'telegram', label: 'تلگرام' },
  { key: 'bale', label: 'بله' },
] as const

type TabKey = (typeof tabs)[number]['key']

const activeTab = ref<TabKey>('default')
const templates = reactive<Record<TabKey, string>>({ default: '', telegram: '', bale: '' })
const variables = ref<Record<string, string>>({})
const preview = ref('')
const saving = ref(false)
const previewing = ref(false)
const copiedVariable = ref('')

const charCount = computed(() => templates[activeTab.value]?.length ?? 0)

onMounted(async () => {
  const { data } = await api.get('/templates')
  templates.default = data.default ?? ''
  templates.telegram = data.telegram ?? ''
  templates.bale = data.bale ?? ''
  variables.value = data.variables ?? {}
  await handlePreview()
})

async function handlePreview() {
  previewing.value = true
  try {
    const { data } = await api.post('/templates/preview', { template: templates[activeTab.value] || templates.default })
    preview.value = data.preview
  } finally {
    previewing.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await api.post('/templates', { ...templates })
    toast.success('قالب‌ها ذخیره شدند.')
    await handlePreview()
  } finally {
    saving.value = false
  }
}

async function copyVariable(variable: string) {
  try {
    await navigator.clipboard.writeText(variable)
    copiedVariable.value = variable
    setTimeout(() => {
      copiedVariable.value = ''
    }, 1500)
  } catch {
    // clipboard unavailable, ignore
  }
}
</script>
