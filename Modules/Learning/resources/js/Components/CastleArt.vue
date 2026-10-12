<script setup>
/**
 * Castle illustration for the end of a level (the client prototype uses a castle emoji;
 * an SVG keeps it sharp and on-brand). `lit` = level finished, `locked` = level not open yet.
 */
defineProps({ lit: Boolean, locked: Boolean });
</script>

<template>
    <svg viewBox="0 0 160 140" role="img" :aria-label="locked ? 'Locked castle' : lit ? 'Castle, level complete' : 'Castle'">
        <g :opacity="locked ? 0.55 : 1">
            <!-- flags -->
            <path d="M80 6v20" stroke="#5b4185" stroke-width="3" stroke-linecap="round" />
            <path d="M81 7l16 6-16 6z" :fill="lit ? '#f5b941' : '#55a6bc'" />
            <path d="M34 32v16M126 32v16" stroke="#5b4185" stroke-width="3" stroke-linecap="round" />
            <path d="M35 33l12 5-12 5zM127 33l12 5-12 5z" :fill="lit ? '#f5b941' : '#55a6bc'" />
            <!-- towers -->
            <path d="M18 70 34 44l16 26z" :fill="locked ? '#9aa6b2' : '#f58464'" />
            <path d="M110 70l16-26 16 26z" :fill="locked ? '#9aa6b2' : '#f58464'" />
            <path d="M62 44 80 18l18 26z" :fill="locked ? '#9aa6b2' : '#f37352'" />
            <rect x="22" y="70" width="24" height="56" rx="3" :fill="locked ? '#c9d1d9' : '#e9e3f3'" />
            <rect x="114" y="70" width="24" height="56" rx="3" :fill="locked ? '#c9d1d9' : '#e9e3f3'" />
            <rect x="66" y="44" width="28" height="40" rx="3" :fill="locked ? '#c9d1d9' : '#d3c7e6'" />
            <!-- wall with battlements -->
            <path d="M40 78h80v48H40z" :fill="locked ? '#d5dbe1' : '#d3c7e6'" />
            <path d="M40 78h10v-8h10v8h10v-8h10v8h10v-8h10v8h10v-8h10v8" :fill="locked ? '#d5dbe1' : '#d3c7e6'" />
            <!-- windows and door -->
            <rect x="75" y="54" width="10" height="14" rx="5" fill="#5b4185" />
            <rect x="30" y="86" width="8" height="12" rx="4" fill="#5b4185" />
            <rect x="122" y="86" width="8" height="12" rx="4" fill="#5b4185" />
            <path d="M66 126v-22a14 14 0 0 1 28 0v22z" :fill="lit ? '#f5b941' : '#5b4185'" />
            <!-- ground -->
            <ellipse cx="80" cy="128" rx="70" ry="8" fill="#ffffff" opacity="0.7" />
        </g>
    </svg>
</template>
