<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({
    classes: Object,
    filters: Object,
    subjects: Array,
    statuses: Object,
    can: Object,
});

const filters = useFilters(
    { search: props.filters.search ?? '', status: props.filters.status ?? '', subject: props.filters.subject ?? '' },
    'classes.index',
);
</script>

<template>
    <Head title="Classes" />

    <PageHeader eyebrow="Academic" title="Classes" :description="can.manage ? 'Online Zoom classes with their teachers and students.' : 'The classes you take part in.'">
        <template v-if="can.manage" #actions>
            <Link :href="route('classes.create')" class="btn-accent"><Icon name="plus" :size="18" /> Create class</Link>
        </template>
    </PageHeader>

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <SearchInput v-model="filters.search" placeholder="Search by class name or code…" />
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
        <select v-if="subjects.length > 1" v-model="filters.subject" class="field lg:ml-auto lg:w-52">
            <option value="">All subjects</option>
            <option v-for="subject in subjects" :key="subject" :value="subject">{{ subject }}</option>
        </select>
    </div>

    <div v-if="classes.data.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes.data" :key="c.id" :class-room="c" />
    </div>

    <div v-else class="card">
        <EmptyState
            icon="school"
            :title="can.manage ? 'No classes yet' : 'You are not in any class yet'"
            :description="can.manage ? 'Create a class, set its schedule and Zoom link, then add students.' : 'Ask an admin to add you to a class.'"
        >
            <Link v-if="can.manage" :href="route('classes.create')" class="btn-primary"><Icon name="plus" :size="18" /> Create class</Link>
        </EmptyState>
    </div>

    <div v-if="classes.last_page > 1" class="card mt-5 overflow-hidden"><Pagination :meta="classes" /></div>
</template>
