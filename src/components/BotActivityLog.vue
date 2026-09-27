<template>
  <section class="flex flex-col gap-3 border-t border-surface-3 pt-5">
    <div class="flex items-center justify-between">
      <h4 class="text-sm font-medium text-slate-700">فعالیت اخیر ربات</h4>
      <div class="flex items-center gap-1">
        <span class="text-xs text-slate-400">به‌روزرسانی خودکار</span>
        <Button variant="ghost" size="sm" :loading="loading" @click="load">↻</Button>
        <Button variant="ghost" size="sm" :disabled="!entries.length" @click="clear">پاک کردن</Button>
      </div>
    </div>

    <p v-if="!entries.length" class="rounded-lg bg-surface-1 px-3 py-4 text-center text-xs text-slate-400">
      هنوز فعالیتی ثبت نشده. یک پیام یا دکمه در ربات بزنید.
    </p>

    <ul v-else class="flex max-h-80 flex-col gap-1 overflow-y-auto rounded-lg border border-surface-3 p-1">
      <li v-for="(entry, i) in entries" :key="i" class="rounded-md px-2 py-1.5 text-xs hover:bg-surface-1">
        <button type="button" class="flex w-full items-start gap-2 text-right" @click="toggle(i)">
          <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="dot(entry.level)" />
          <span class="flex-1" :class="entry.level === 'error' ? 'text-red-600' : 'text-slate-700'">{{ entry.event }}</span>
          <span class="shrink-0 text-slate-400">{{ time(entry.time) }}</span>
        </button>
        <pre
          v-if="entry.detail && open.has(i)"
          dir="ltr"
          class="mt-1 whitespace-pre-wrap break-all rounded bg-slate-50 p-2 text-left font-mono text-[11px] text-slate-600"
        >{{ entry.detail }}</pre>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import { api } from '@/utils/api'

interface Entry {
  time: string
  platform: string
  level: 'info' | 'warn' | 'error'
  event: string
  detail: string
}

const props = defineProps<{ platform: 'telegram' | 'bale' }>()

const entries = ref<Entry[]>([])
const loading = ref(false)
const open = reactive(new Set<number>())
let timer: ReturnType<typeof setInterval> | undefined

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/bot/debug-log', { params: { platform: props.platform } })
    entries.value = data.entries ?? []
  } catch {
    // interceptor already shows a toast
  } finally {
    loading.value = false
  }
}

async function clear() {
  try {
    await api.delete('/bot/debug-log')
    entries.value = []
    open.clear()
  } catch {
    // interceptor already shows a toast
  }
}

function toggle(i: number) {
  if (open.has(i)) open.delete(i)
  else open.add(i)
}

function dot(level: Entry['level']) {
  return { info: 'bg-emerald-500', warn: 'bg-amber-500', error: 'bg-red-500' }[level] ?? 'bg-slate-300'
}

function time(iso: string) {
  return new Date(iso).toLocaleTimeString('fa-IR')
}

onMounted(() => {
  load()
  timer = setInterval(() => {
    if (document.visibilityState === 'visible') load()
  }, 5000)
})

onBeforeUnmount(() => clearInterval(timer))
</script>
