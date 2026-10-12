<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

defineProps({ levels: Array });
</script>

<template>
    <Head title="Archive" />
    <PageHeader eyebrow="Completed lessons" title="Archive" description="Lessons you have finished are kept here so you can come back to them any time." />

    <div v-if="levels.length" class="space-y-6">
        <section v-for="level in levels" :key="level.id" class="card overflow-hidden" :aria-labelledby="`archive-${level.id}`">
            <header class="flex flex-wrap items-center gap-3 border-b border-line p-5">
                <span :class="(classColors[level.color] ?? classColors.violet).solid" class="size-3 rounded-full" />
                <div class="min-w-0 flex-1">
                    <p v-if="level.program" class="text-xs font-bold tracking-wide text-muted uppercase">{{ level.program.name }}</p>
                    <h2 :id="`archive-${level.id}`" class="font-display text-lg font-extrabold text-ink">{{ level.title }}</h2>
                </div>
                <span v-if="level.percent === 100" class="inline-flex items-center gap-1.5 rounded-full bg-success-soft px-3 py-1 text-sm font-bold text-success-ink"><Icon name="trophy" :size="15" /> Level complete</span>
                <span v-else class="text-sm font-semibold text-muted">{{ level.percent }}% done</span>
            </header>
            <ul class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="lesson in level.lessons" :key="lesson.key">
                    <Link :href="route('learning.lessons.show', lesson.first_task_id)" class="group flex h-full items-center gap-3 rounded-2xl border border-line bg-white p-3 transition hover:border-brand-300 hover:shadow-sm">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-brand-300 font-display text-lg font-extrabold text-white shadow-[0_4px_0_var(--color-brand-500)]">{{ lesson.number }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-ink group-hover:text-brand-600">{{ lesson.is_unit ? `Lesson ${lesson.number}: ${lesson.title}` : lesson.title }}</span>
                            <span class="block text-xs text-muted">{{ lesson.tasks }} {{ lesson.tasks === 1 ? 'task' : 'tasks' }}<template v-if="lesson.finished_at"> · finished {{ lesson.finished_at }}</template></span>
                        </span>
                        <Icon name="rotate-ccw" :size="16" class="text-muted group-hover:text-brand-600" aria-label="Review" />
                    </Link>
                </li>
            </ul>
        </section>
    </div>
    <div v-else class="card">
        <EmptyState icon="archive" title="Nothing archived yet" description="Your finished lessons will show up on this page.">
            <Link :href="route('learning.courses.index')" class="btn-primary"><Icon name="book-open" :size="16" /> Go to my course</Link>
        </EmptyState>
    </div>
</template>
