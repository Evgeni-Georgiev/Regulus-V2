<template>
  <div
    :class="cn(alertVariants({ variant }), $attrs.class)"
    role="alert"
    v-bind="$attrs"
  >
    <slot />
  </div>
</template>

<script setup>
import { cva } from 'class-variance-authority'
import { cn } from '@/utils/cn'

defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'destructive'].includes(value)
  }
})

const alertVariants = cva(
  'relative w-full rounded-lg border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground',
        destructive: 'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive'
      }
    },
    defaultVariants: {
      variant: 'default'
    }
  }
)
</script> 