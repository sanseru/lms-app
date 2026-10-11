<script setup>
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
import { rupiah } from '@/lib/format';

const props = defineProps({ teachers: Object, filters: Object });

const filters = useFilters({ search: props.filters.search ?? '', status: props.filters.status ?? '' }, 'people.teachers.index');
const confirm = useConfirm();
</script>

<template>
    <Head title="Teachers" />

    <PageHeader eyebrow="People" title="Teachers" description="Manage teachers and their expertise.">
        <template #actions>
            <Link :href="route('people.teachers.create')" class="btn-accent"><Icon name="user-plus" :size="18" /> Add teacher</Link>
        </template>
    </PageHeader>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row">
        <SearchInput v-model="filters.search" placeholder="Search by name or expertise…" />
        <select v-model="filters.status" class="field sm:w-44">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div v-if="teachers.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="teacher in teachers.data" :key="teacher.id" class="card flex flex-col p-5 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-900/5">
            <div class="flex items-start gap-4">
                <Avatar :initials="teacher.initials" :seed="teacher.id" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="truncate font-display font-bold text-ink">{{ teacher.name }}</p>
                        <StatusBadge :status="teacher.status" />
                    </div>
                    <p class="font-mono text-xs text-muted">{{ teacher.code }}</p>
                    <p class="mt-1 text-sm text-accent-700">{{ teacher.expertise ?? 'No expertise added yet' }}</p>
                </div>
            </div>
            <div class="mt-4 space-y-1.5 text-sm text-muted">
                <p v-if="teacher.email" class="flex items-center gap-2 truncate"><Icon name="mail" :size="15" /> {{ teacher.email }}</p>
                <p v-if="teacher.phone" class="flex items-center gap-2"><Icon name="phone" :size="15" /> {{ teacher.phone }}</p>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-line pt-4">
                <p class="text-sm">
                    <b class="font-display text-ink">{{ teacher.classes_count }}</b> <span class="text-muted">classes ·</span>
                    <b class="font-display text-ink">{{ rupiah(teacher.rate_per_session) }}</b><span class="text-muted">/session</span>
                </p>
                <div class="flex gap-1">
                    <Link :href="route('people.teachers.edit', teacher.id)" class="rounded-lg p-2 text-muted hover:bg-brand-50 hover:text-brand-600" title="Edit"><Icon name="pencil" :size="16" /></Link>
                    <button
                        class="rounded-lg p-2 text-muted hover:bg-rose-50 hover:text-rose-600"
                        title="Delete"
                        @click="confirm.ask({ title: `Delete ${teacher.name}?`, message: 'Teachers who still teach active classes cannot be deleted.', url: route('people.teachers.destroy', teacher.id) })"
                    >
                        <Icon name="trash" :size="16" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="card">
        <EmptyState icon="presentation" title="No teachers yet" description="Add teachers so they can be assigned to classes.">
            <Link :href="route('people.teachers.create')" class="btn-primary"><Icon name="plus" :size="18" /> Add teacher</Link>
        </EmptyState>
    </div>

    <div class="card mt-5 overflow-hidden" v-if="teachers.last_page > 1"><Pagination :meta="teachers" /></div>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
