<template>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <Card title="Message Template">
      <div class="flex flex-col gap-4">
        <textarea
          v-model="template"
          rows="10"
          class="btn-focus rounded-lg border border-surface-3 p-3 font-mono text-sm text-slate-800"
        />
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
        <div class="mx-auto max-w-sm rounded-2xl bg-[#efeae2] p-3">
          <div class="whitespace-pre-wrap rounded-lg bg-white p-3 text-sm text-slate-800 shadow-sm" v-html="preview" />
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { api } from '@/utils/api'

const template = ref('')
const variables = ref<Record<string, string>>({})
const preview = ref('')
const saving = ref(false)
const previewing = ref(false)
const copiedVariable = ref('')

onMounted(async () => {
  const [templateRes, variablesRes] = await Promise.all([
    api.get('/templates'),
    api.get('/templates/variables'),
  ])
  template.value = templateRes.data.template
  variables.value = variablesRes.data.variables
  await handlePreview()
})

async function handlePreview() {
  previewing.value = true
  try {
    const { data } = await api.post('/templates/preview', { template: template.value })
    preview.value = data.preview
  } finally {
    previewing.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await api.post('/templates', { template: template.value })
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
