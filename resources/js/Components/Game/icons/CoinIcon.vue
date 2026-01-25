<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 64 64"
    :class="cls"
    aria-hidden="true"
  >
    <defs>
      <radialGradient :id="`coinRim_${uid}`" cx="35%" cy="30%" r="75%">
        <stop offset="0%" stop-color="#FFD37A" />
        <stop offset="55%" stop-color="#F6B73C" />
        <stop offset="100%" stop-color="#D98A12" />
      </radialGradient>

      <radialGradient :id="`coinFace_${uid}`" cx="32%" cy="30%" r="80%">
        <stop offset="0%" stop-color="#FFE29C" />
        <stop offset="55%" stop-color="#FFC24A" />
        <stop offset="100%" stop-color="#E59A1E" />
      </radialGradient>

      <radialGradient :id="`coinHi_${uid}`" cx="28%" cy="24%" r="55%">
        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.3" />
        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
      </radialGradient>
    </defs>

    <!-- rim -->
    <circle cx="32" cy="32" r="28" :fill="`url(#coinRim_${uid})`" />

    <!-- face -->
    <circle cx="32" cy="32" r="22" :fill="`url(#coinFace_${uid})`" />

    <!-- highlight -->
    <circle cx="26" cy="24" r="15" :fill="`url(#coinHi_${uid})`" />

    <!-- 🏛 temple (CENTERED FIX) -->
    <!-- scale sedikit lebih kecil + translate Y ke bawah -->
    <g
      transform="translate(32 30) scale(0.9) translate(-32 -32)"
      fill="#D07A12"
    >
      <!-- roof -->
      <path d="M18 25 L32 18 L46 25 L46 28 L18 28 Z" />
      <!-- columns -->
      <rect x="22" y="28" width="4" height="16" rx="1" />
      <rect x="30" y="28" width="4" height="16" rx="1" />
      <rect x="38" y="28" width="4" height="16" rx="1" />
      <!-- base -->
      <path d="M20 44 H44 V47 H20 Z" />
      <path d="M18 47 H46 V50 H18 Z" />
    </g>

    <!-- inner ring -->
    <circle
      cx="32"
      cy="32"
      r="22"
      fill="none"
      stroke="#F6B73C"
      stroke-opacity="0.6"
      stroke-width="1.25"
    />
  </svg>
</template>

<script setup>
import { ref } from "vue"

defineProps({
  cls: { type: String, default: "w-5 h-5" },
})

const uid = ref(Math.random().toString(36).slice(2, 9))
</script>
