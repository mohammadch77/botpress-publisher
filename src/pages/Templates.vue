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
          <Badge v-for="variable in variables" :key="variable" variant="info">{{ wrapVariable(variable) }}</Badge>
        </div>
        <div>
          <Button variant="primary">Save Template</Button>
        </div>
      </div>
    </Card>

    <Card title="Preview">
      <div class="rounded-lg border border-surface-3 bg-surface-1 p-4 text-sm text-slate-800 whitespace-pre-wrap">
        {{ preview }}
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { api } from '@/utils/api'

const template = ref('')
const variables = ref<string[]>([])

const sampleData: Record<string, string> = {
  title: 'Sample Post Title',
  excerpt: 'This is a short excerpt of the post content used for preview purposes.',
  url: 'https://example.com/sample-post',
  date: new Date().toLocaleDateString(),
  author: 'Admin',
}

const preview = computed(() => {
  let result = template.value
  for (const [key, value] of Object.entries(sampleData)) {
    result = result.replaceAll(`{${key}}`, value)
  }
  return result
})

onMounted(async () => {
  const { data } = await api.get('/templates')
  template.value = data.template
  variables.value = data.variables
})

function wrapVariable(name: string) {
  return '{' + name + '}'
}
</script>
