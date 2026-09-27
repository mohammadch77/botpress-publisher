<template>
  <Modal :model-value="modelValue" :title="mode === 'publish' ? 'انتشار فوری' : 'زمان‌بندی انتشار'" @update:model-value="close">
    <div v-if="post" class="flex flex-col gap-5">
      <div class="flex items-center gap-3 rounded-lg bg-surface-1 p-3">
        <img v-if="post.thumbnail" :src="post.thumbnail" alt="" class="h-12 w-12 shrink-0 rounded-md object-cover" />
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-slate-800">{{ post.title }}</p>
          <p class="text-xs text-slate-400">{{ postStatusLabels[post.status] ?? post.status }} · شناسه {{ post.id }}</p>
        </div>
      </div>

      <Select v-model="target" label="مقصد انتشار" :options="targetOptions" />

      <template v-if="mode === 'schedule'">
        <div v-if="post.queue" class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
          این مقاله برای {{ formatSiteDate(post.queue.scheduled_at) }} زمان‌بندی شده است. زمان جدید جایگزین آن می‌شود.
        </div>

        <div class="flex flex-col gap-2">
          <span class="text-sm font-medium text-slate-700">زمان‌های سریع (همانند ربات)</span>
          <div v-if="presetsLoading" class="grid grid-cols-2 gap-2">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-lg bg-surface-2" />
          </div>
          <div v-else class="grid grid-cols-2 gap-2">
            <button
              v-for="preset in presets"
              :key="preset.key"
              type="button"
              class="flex flex-col items-start rounded-lg border px-3 py-2 text-right transition-colors"
              :class="selectedPreset === preset.key ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-surface-3 hover:bg-surface-1'"
              @click="choosePreset(preset.key)"
            >
              <span class="text-sm text-slate-800">{{ preset.label }}</span>
              <span class="text-xs text-slate-400">{{ formatSiteDate(preset.at) }}</span>
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-slate-700">یا زمان دلخواه</label>
          <input
            v-model="customAt"
            type="datetime-local"
            :min="minCustom"
            class="btn-focus rounded-lg border px-3 py-2 text-sm text-slate-800"
            :class="selectedPreset === '' && customAt ? 'border-brand-500 ring-1 ring-brand-500' : 'border-surface-3'"
            @input="selectedPreset = ''"
          />
          <p v-if="customAt" class="text-xs text-slate-400">{{ formatSiteDate(customAt.replace('T', ' ')) }}</p>
        </div>
      </template>

      <p v-else class="text-sm text-slate-600">
        مقاله همین حالا
        {{ target === 'wordpress' ? 'در وردپرس منتشر می‌شود.' : target === 'channel' ? 'به کانال‌ها ارسال می‌شود.' : 'در وردپرس منتشر و به کانال‌ها ارسال می‌شود.' }}
        <span v-if="post.queue" class="block pt-1 text-xs text-amber-600">زمان‌بندی قبلی این مقاله لغو خواهد شد.</span>
      </p>

      <p v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

      <div class="flex justify-end gap-2 border-t border-surface-3 pt-4">
        <Button variant="ghost" :disabled="submitting" @click="close">انصراف</Button>
        <Button variant="primary" :loading="submitting" :disabled="mode === 'schedule' && !canSchedule" @click="submit">
          {{ mode === 'publish' ? 'انتشار' : 'ثبت زمان‌بندی' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import { formatSiteDate, postStatusLabels, targetOptions } from '@/utils/format'
import type { PostItem, PublishTarget, SchedulePreset } from '@/types'

const props = defineProps<{
  modelValue: boolean
  mode: 'publish' | 'schedule'
  post: PostItem | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'done'): void
}>()

const toast = useToast()
const target = ref<PublishTarget>('both')
const presets = ref<SchedulePreset[]>([])
const presetsLoading = ref(false)
const serverNow = ref('')
const selectedPreset = ref('')
const customAt = ref('')
const submitting = ref(false)
const error = ref('')

const minCustom = computed(() => (serverNow.value ? serverNow.value.slice(0, 16).replace(' ', 'T') : undefined))
const canSchedule = computed(() => selectedPreset.value !== '' || customAt.value !== '')

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    error.value = ''
    selectedPreset.value = ''
    customAt.value = ''
    target.value = props.post?.queue?.target ?? 'both'
    if (props.mode === 'schedule') loadPresets()
  }
)

async function loadPresets() {
  presetsLoading.value = true
  try {
    const { data } = await api.get('/schedule/presets')
    presets.value = data.presets
    serverNow.value = data.now
  } catch {
    error.value = 'بارگذاری زمان‌های پیشنهادی ناموفق بود.'
  } finally {
    presetsLoading.value = false
  }
}

function choosePreset(key: string) {
  selectedPreset.value = key
  customAt.value = ''
}

function close() {
  if (!submitting.value) emit('update:modelValue', false)
}

async function submit() {
  if (!props.post) return
  submitting.value = true
  error.value = ''
  try {
    if (props.mode === 'publish') {
      const { data } = await api.post(`/posts/${props.post.id}/publish`, { target: target.value })
      if (!data.success) {
        error.value = data.message || 'انتشار ناموفق بود.'
        return
      }
      toast.success(data.message || 'انتشار انجام شد.')
    } else {
      const payload: Record<string, unknown> = { post_id: props.post.id, target: target.value }
      if (selectedPreset.value) payload.preset = selectedPreset.value
      else payload.scheduled_at = customAt.value.replace('T', ' ') + ':00'
      const { data } = await api.post('/queue', payload)
      toast.success(data.message || 'زمان‌بندی ثبت شد.')
    }
    emit('update:modelValue', false)
    emit('done')
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'خطا در انجام عملیات.'
  } finally {
    submitting.value = false
  }
}
</script>
