<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { classColors } from '@/lib/format';
import Icon from './Icon.vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({ classRoom: { type: Object, required: true } });
const color = computed(() => classColors[props.classRoom.color] ?? classColors.violet);
</script>

<template>
    <Link
        :href="route('classes.show', classRoom.id)"
        class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-900/5"
    >
        <div :class="color.grad" class="relative h-20 bg-gradient-to-br p-4 text-white">
            <span class="font-mono text-xs font-medium opacity-90">{{ classRoom.code }}</span>
            <p class="font-display text-lg font-extrabold leading-tight">{{ classRoom.subject }}</p>
            <span class="absolute -right-4 -bottom-6 font-mono text-7xl font-black opacity-15 select-none">{ }</span>
        </div>
        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-display font-bold leading-snug text-ink group-hover:text-brand-600">{{ classRoom.name }}</h3>
                <StatusBadge :status="classRoom.status" :label="classRoom.status_label" />
            </div>
            <div class="space-y-1.5 text-sm text-muted">
                <p class="flex items-center gap-2"><Icon name="clock" :size="15" /> {{ classRoom.schedule }}</p>
                <p v-if="classRoom.teacher" class="flex items-center gap-2"><Icon name="presentation" :size="15" /> {{ classRoom.teacher.name }}</p>
                <p v-if="classRoom.students_count !== null" class="flex items-center gap-2">
                    <Icon name="users" :size="15" /> {{ classRoom.students_count }}<span v-if="classRoom.capacity">/{{ classRoom.capacity }}</span> siswa
                </p>
            </div>
            <div class="mt-auto pt-1">
                <span :class="[color.bg, color.text]" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ classRoom.level_label }}</span>
            </div>
        </div>
    </Link>
</template>
