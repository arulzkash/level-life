<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  label: { type: String, default: '' },
  value: { type: [String, Number], required: true },
  icon: { type: String, default: null },
  colorClass: { type: String, default: 'text-white' },
  href: { type: String, default: null }
});
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    class="rounded-2xl border border-slate-700 bg-slate-800/50 p-3 md:p-5 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg"
  >
    <div v-if="label" class="text-xs text-slate-400 uppercase tracking-widest mb-1">
      {{ label }}
    </div>
    <slot>
      <div class="text-2xl font-bold" :class="colorClass">
        <span v-if="icon">{{ icon }}</span> {{ value }}
      </div>
    </slot>
  </component>
</template>
