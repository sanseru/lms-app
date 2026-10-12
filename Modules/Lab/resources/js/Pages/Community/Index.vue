<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import ProjectCard from '../../Components/ProjectCard.vue';

const props = defineProps({ projects: Object, trending: Array, filters: Object, languages: Object, can: Object });

const search = ref(props.filters.q ?? '');
const go = (params) => router.get(route('community.index'), { ...props.filters, ...params }, { preserveState: true, preserveScroll: true, replace: true });
const periods = [
    { key: 'today', label: 'Today', hint: 'Most liked today' },
    { key: 'week', label: 'Week', hint: 'Most liked in the last 7 days' },
    { key: 'month', label: 'Month', hint: 'Most liked in the last 30 days' },
];
const sorts = [
    { key: 'new', label: 'New' },
    { key: 'popular', label: 'Popular' },
    { key: 'discussed', label: 'Most discussed' },
];
</script>

<template>
    <Head title="Community" />

    <section class="panel-brand mb-8 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div class="max-w-xl">
            <p class="text-[11px] font-extrabold tracking-[0.12em] text-brand-100 uppercase">Community</p>
            <h1 class="mt-1 font-display text-3xl font-extrabold tracking-tight">See what everyone is building</h1>
            <p class="mt-2 text-sm text-white/85">Projects shared by students from every class. Run them, leave a kind comment, and get ideas for your next one.</p>
        </div>
        <Link v-if="can.create" :href="route('lab.projects.index')" class="btn-accent shrink-0"><Icon name="plus" :size="16" /> Share a project</Link>
    </section>

    <section v-if="trending.length || projects.total" class="mb-10" aria-labelledby="trending-title">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="trending-title" class="font-display text-xl font-extrabold text-ink">Trending</h2>
                <p class="text-sm text-muted">{{ periods.find((p) => p.key === filters.period)?.hint }}</p>
            </div>
            <div class="inline-flex rounded-xl bg-chip p-1" role="tablist" aria-label="Trending period">
                <button
                    v-for="p in periods"
                    :key="p.key"
                    type="button"
                    role="tab"
                    :aria-selected="filters.period === p.key"
                    :class="filters.period === p.key ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"
                    class="rounded-lg px-4 py-1.5 text-sm font-bold transition"
                    @click="go({ period: p.key })"
                >{{ p.label }}</button>
            </div>
        </div>
        <div v-if="trending.length" class="-mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-2">
            <div v-for="p in trending" :key="p.id" class="w-64 shrink-0 snap-start [&>a]:h-full">
                <ProjectCard :project="p" :href="route('community.show', p.id)" />
            </div>
        </div>
        <p v-else class="card px-5 py-6 text-center text-sm text-muted">No likes {{ filters.period === 'today' ? 'today' : `this ${filters.period}` }} yet. Like a project you enjoy and it will show up here.</p>
    </section>

    <section aria-labelledby="all-title">
        <h2 id="all-title" class="sr-only">All projects</h2>
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="inline-flex w-fit rounded-xl bg-chip p-1" role="tablist" aria-label="Sort projects">
                <button
                    v-for="s in sorts"
                    :key="s.key"
                    type="button"
                    role="tab"
                    :aria-selected="filters.sort === s.key"
                    :class="filters.sort === s.key ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"
                    class="rounded-lg px-4 py-1.5 text-sm font-bold transition"
                    @click="go({ sort: s.key, page: undefined })"
                >{{ s.label }}</button>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex flex-wrap gap-2" aria-label="Filter by type">
                    <button type="button" :class="!filters.language ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'" class="rounded-full px-3.5 py-1.5 text-sm font-bold" :aria-pressed="!filters.language" @click="go({ language: undefined, page: undefined })">All</button>
                    <button
                        v-for="(label, key) in languages"
                        :key="key"
                        type="button"
                        :aria-pressed="filters.language === key"
                        :class="filters.language === key ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'"
                        class="rounded-full px-3.5 py-1.5 text-sm font-bold"
                        @click="go({ language: key, page: undefined })"
                    >{{ label }}</button>
                </div>
                <form class="relative" role="search" @submit.prevent="go({ q: search || undefined, page: undefined })">
                    <label for="community-search" class="sr-only">Search projects</label>
                    <Icon name="search" :size="16" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                    <input id="community-search" v-model="search" type="search" class="field w-full pl-9 sm:w-56" placeholder="Search projects" />
                </form>
            </div>
        </div>

        <div v-if="projects.data.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <ProjectCard v-for="p in projects.data" :key="p.id" :project="p" :href="route('community.show', p.id)" />
        </div>
        <div v-else class="card">
            <EmptyState icon="users" :title="filters.q || filters.language ? 'Nothing matches that' : 'No shared projects yet'" :description="can.create ? 'Be the first: make something in the Laboratory and press Save & share.' : 'Shared student projects will show up here.'" />
        </div>

        <Pagination :meta="projects" class="mt-6" />
    </section>
</template>
