<script setup>
import { gradeLabel } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({
    students: Object,
    filters: Object,
    stats: Object,
});

const filters = useFilters({ search: props.filters.search ?? '', status: props.filters.status ?? '' }, 'people.students.index');
const confirm = useConfirm();
</script>

<template>
    <Head title="Students" />

    <PageHeader eyebrow="People" title="Students" :description="`${stats.active} active out of ${stats.total} registered students.`">
        <template #actions>
            <Link :href="route('people.students.create')" class="btn-accent"><Icon name="user-plus" :size="18" /> Add student</Link>
        </template>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <SearchInput v-model="filters.search" placeholder="Search by name, username, code…" />
            <select v-model="filters.status" class="field sm:w-44">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div v-if="students.data.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-chip/60 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">School / Grade</th>
                        <th class="px-5 py-3">Parents</th>
                        <th class="px-5 py-3 text-center">Classes</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="student in students.data" :key="student.id" class="hover:bg-chip/40">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <Avatar :initials="student.initials" :seed="student.id" />
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ student.name }}</p>
                                    <p class="font-mono text-xs text-muted">{{ student.code }}<template v-if="student.username"> · @{{ student.username }}</template></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-muted">
                            <p class="text-ink">{{ student.school ?? '–' }}</p>
                            <p v-if="student.grade" class="text-xs">{{ gradeLabel(student.grade) }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p v-for="g in student.guardians" :key="g.id" class="text-ink">{{ g.name }} <span class="text-xs text-muted">{{ g.phone }}</span></p>
                            <span v-if="!student.guardians.length" class="text-xs font-semibold text-amber-700">Not linked yet</span>
                        </td>
                        <td class="px-5 py-3 text-center font-display font-bold text-ink">{{ student.classes_count }}</td>
                        <td class="px-5 py-3"><StatusBadge :status="student.status" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                <Link :href="route('people.students.edit', student.id)" class="rounded-lg p-2 text-muted hover:bg-brand-50 hover:text-brand-600" title="Edit">
                                    <Icon name="pencil" :size="16" />
                                </Link>
                                <button
                                    class="rounded-lg p-2 text-muted hover:bg-rose-50 hover:text-rose-600"
                                    title="Delete"
                                    @click="confirm.ask({ title: `Delete ${student.name}?`, message: 'The student account will be deactivated and hidden from the list.', url: route('people.students.destroy', student.id) })"
                                >
                                    <Icon name="trash" :size="16" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-else
            icon="graduation-cap"
            :title="filters.search || filters.status ? 'No matching students' : 'No students yet'"
            :description="filters.search || filters.status ? 'Try a different keyword or filter.' : 'Add your first student, then link them to their parents.'"
        >
            <Link v-if="!filters.search && !filters.status" :href="route('people.students.create')" class="btn-primary"><Icon name="plus" :size="18" /> Add student</Link>
        </EmptyState>

        <Pagination :meta="students" />
    </div>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
