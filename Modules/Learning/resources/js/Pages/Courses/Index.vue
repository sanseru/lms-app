<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';
import CourseCover from '../../Components/CourseCover.vue';

const props = defineProps({ courses: Array, filters: Object, statuses: Object });
const filters = useFilters({ search: props.filters.search ?? '', status: props.filters.status ?? '' }, 'learning.courses.index');
</script>

<template>
    <Head title="Courses & lessons" />

    <PageHeader eyebrow="Learning" title="Courses & lessons" description="Build a curriculum of lessons, quizzes and assignments, then attach it to a class. Students in that class get access automatically.">
        <template #actions>
            <Link :href="route('learning.courses.create')" class="btn-accent"><Icon name="plus" :size="18" /> New course</Link>
        </template>
    </PageHeader>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchInput v-model="filters.search" placeholder="Search title or subject…" />
        <div class="flex flex-wrap gap-2">
            <button
                v-for="(label, key) in { '': 'All', ...statuses }"
                :key="key"
                :class="filters.status === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                @click="filters.status = key"
            >
                {{ label }}
            </button>
        </div>
    </div>

    <div v-if="courses.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <Link v-for="c in courses" :key="c.id" :href="route('learning.courses.show', c.id)" class="card group overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
            <CourseCover :course="c">
                <span class="absolute top-3 right-3"><Badge :tone="c.status === 'published' ? 'green' : 'gray'">{{ c.status_label }}</Badge></span>
            </CourseCover>
            <div class="p-5">
                <p class="text-xs font-semibold text-muted">{{ c.level_label }}</p>
                <h3 class="mt-0.5 font-display text-lg font-bold text-ink group-hover:text-brand-600">{{ c.title }}</h3>
                <p v-if="c.description" class="mt-1 line-clamp-2 text-sm text-muted">{{ c.description }}</p>
                <div class="mt-4 flex items-center gap-4 text-sm text-muted">
                    <span class="flex items-center gap-1.5"><Icon name="book-open" :size="15" /> {{ c.lessons_count }} {{ c.lessons_count === 1 ? 'lesson' : 'lessons' }}</span>
                    <span class="flex items-center gap-1.5"><Icon name="school" :size="15" /> {{ c.classes_count }} {{ c.classes_count === 1 ? 'class' : 'classes' }}</span>
                </div>
            </div>
        </Link>
    </div>

    <div v-else class="card">
        <EmptyState icon="book-open" title="No courses yet" description="Create your first course, fill it with lessons, quizzes and assignments, then attach it to a class.">
            <Link :href="route('learning.courses.create')" class="btn-primary"><Icon name="plus" :size="18" /> New course</Link>
        </EmptyState>
    </div>
</template>
