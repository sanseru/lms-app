<script setup>
defineProps({ name: { type: String, required: true }, size: { type: Number, default: 24 } });
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <template v-if="name === 'users'"><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0" /><path d="M17 11a3 3 0 1 0 0-6M21.5 20a5.5 5.5 0 0 0-4-5.3" /></template>
        <template v-else-if="name === 'project'"><path d="M14 3h7v7" /><path d="M21 3l-9 9" /><path d="M19 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5" /></template>
        <template v-else-if="name === 'check'"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></template>
        <template v-else-if="name === 'calendar'"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></template>
        <template v-else-if="name === 'login'"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="M10 17l5-5-5-5" /><path d="M15 12H3" /></template>
        <template v-else-if="name === 'menu'"><path d="M4 7h16M4 12h16M4 17h16" /></template>
        <template v-else-if="name === 'close'"><path d="M6 6l12 12M18 6L6 18" /></template>
        <template v-else-if="name === 'whatsapp'"><path d="M3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 8 19.4z" /><path d="M9 9.5c.3 1.7 1.8 3.7 4 4.5l1-1.2 2 .9c-.2 1-1.1 1.6-2.1 1.5C10.8 14.8 8.6 12.5 8.4 10c0-1 .5-1.8 1.4-2.1l1 2-.8 1" /></template>
    </svg>
</template>
