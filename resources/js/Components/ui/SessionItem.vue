<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { classColors } from '@/lib/format';
import Icon from './Icon.vue';

const props = defineProps({
    session: { type: Object, required: true },
    showTeacher: { type: Boolean, default: true },
});
const color = computed(() => classColors[props.session.color] ?? classColors.violet);
</script>

<template>
    <div class="flex items-center gap-4 rounded-xl border border-line bg-white p-3 transition hover:border-brand-200">
        <div class="w-14 shrink-0 text-center">
            <p class="font-display text-base font-extrabold text-ink">{{ session.start_time }}</p>
            <p class="text-xs text-muted">{{ session.end_time }}</p>
        </div>
        <span :class="color.solid" class="h-10 w-1 shrink-0 rounded-full" />
        <Link :href="route('classes.show', session.id)" class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink hover:text-brand-600">{{ session.name }}</p>
            <p class="truncate text-sm text-muted">
                {{ session.subject }}<template v-if="showTeacher && session.teacher"> · {{ session.teacher.name }}</template>
            </p>
        </Link>
        <a
            v-if="session.zoom_url"
            :href="session.zoom_url"
            target="_blank"
            rel="noopener"
            class="btn-accent shrink-0 px-3 py-2"
        >
            <Icon name="video" :size="16" /> <span class="hidden sm:inline">Masuk Zoom</span>
        </a>
    </div>
</template>
