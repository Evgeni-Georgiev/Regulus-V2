<template>
  <div class="flex items-center space-x-2">
    <input
      :id="id"
      type="checkbox"
      :class="cn(
        'peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
        $attrs.class
      )"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      v-bind="$attrs"
    />
    <Check 
      v-if="modelValue" 
      class="absolute h-4 w-4 text-primary-foreground pointer-events-none"
    />
  </div>
</template>

<script setup>
import { Check } from 'lucide-vue-next'
import { cn } from '@/utils/cn'

defineProps({
  id: {
    type: String,
    required: true
  },
  modelValue: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])
</script> 