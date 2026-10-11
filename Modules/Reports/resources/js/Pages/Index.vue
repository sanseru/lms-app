<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({ month: Object, months: Array, filters: Object, staff: Boolean, classOptions: Array, students: Object });

const filters = useFilters({ month: props.month.key, search: props.filters.search ?? '', class: props.filters.class ?? '' }, 'reports.index');
const open = (s) => router.visit(route('reports.show', [s.id, props.month.key]));
</script>

<template>
    <Head title="Monthly reports" />

    <PageHeader
        :eyebrow="staff ? 'Academic' : 'Reports'"
        title="Monthly learning reports"
        :description="staff
            ? 'A summary of each student\'s attendance, lessons, scores and achievements. Write a short note per class, then parents can read it or save it as a PDF.'
            : 'One month at a glance: attendance, teacher notes, scores and achievements. Print it or save it as a PDF.'"
    >
        <template #actions>
            <select v-model="filters.month" class="field w-48" aria-label="Month">
                <option v-for="m in months" :key="m.key" :value="m.key">{{ m.label }}</option>
            </select>
        </template>
    </PageHeader>

    <!-- Parents & students: one card per child -->
    <div v-if="!staff" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <Link v-for="s in students.data" :key="s.id" :href="route('reports.show', [s.id, month.key])" class="card group flex flex-col p-5 transition hover:border-brand-300">
            <div class="flex items-center gap-3">
                <Avatar :initials="s.initials" :seed="s.id" />
                <div class="min-w-0">
                    <p class="truncate font-display font-bold text-ink">{{ s.name }}</p>
                    <p class="truncate text-sm text-muted">{{ s.classes.join(', ') || 'No class yet' }}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-muted">{{ month.label }}</p>
            <p class="font-semibold text-ink">{{ s.sessions ? `Attended ${s.present} of ${s.sessions} sessions` : 'No completed sessions yet' }}</p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-600 group-hover:underline">Open report <Icon name="arrow-right" :size="15" /></span>
        </Link>
        <EmptyState v-if="!students.data.length" icon="file-text" title="No reports yet" description="Reports appear once your child is enrolled in a class." class="card sm:col-span-2 xl:col-span-3" />
    </div>

    <!-- Teachers & admins: student table -->
    <div v-else class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <SearchInput v-model="filters.search" placeholder="Search student name…" />
            <select v-model="filters.class" class="field sm:w-60" aria-label="Class">
                <option value="">All classes</option>
                <option v-for="c in classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>
        <div v-if="students.data.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-chip/60 text-xs font-bold tracking-wide text-muted uppercase">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Attendance {{ month.label }}</th>
                        <th class="px-5 py-3">Teacher notes</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="s in students.data" :key="s.id" class="cursor-pointer hover:bg-chip/40" @click="open(s)">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <Avatar :initials="s.initials" :seed="s.id" size="sm" />
                                <div><p class="font-semibold text-ink">{{ s.name }}</p><p v-if="s.code" class="text-xs text-muted">{{ s.code }}</p></div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-muted">{{ s.classes.join(', ') || '–' }}</td>
                        <td class="px-5 py-3">
                            <span v-if="s.sessions" class="font-semibold text-ink">{{ s.present }}/{{ s.sessions }}</span>
                            <span v-else class="text-muted">No sessions yet</span>
                        </td>
                        <td class="px-5 py-3">
                            <span v-if="s.notes_missing" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200">{{ s.notes_missing }} not written yet</span>
                            <span v-else-if="s.classes.length" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200"><Icon name="check" :size="13" /> Complete</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link :href="route('reports.show', [s.id, month.key])" class="text-xs font-semibold text-brand-600 hover:underline" @click.stop>Open</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else icon="file-text" title="No students found" description="Try a different search or class." />
        <Pagination :meta="students" />
    </div>
</template>
