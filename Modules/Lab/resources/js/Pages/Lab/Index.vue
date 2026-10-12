<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Blocks, Braces, Code, Globe, Presentation } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import ProjectCard from '../../Components/ProjectCard.vue';

const props = defineProps({ projects: Array, filters: Object, types: Array, totals: Object });

const typeIcons = { python: Code, scratch: Blocks, presentation: Presentation, html: Globe, javascript: Braces };
const typeColors = { python: 'bg-sky-700 text-white', scratch: 'bg-amber-400 text-amber-950', presentation: 'bg-brand-600 text-white', html: 'bg-emerald-700 text-white', javascript: 'bg-amber-300 text-amber-950' };

const creating = ref(false);
const form = useForm({ title: '', language: 'python' });
const create = () => form.post(route('lab.projects.store'), { onSuccess: () => (creating.value = false) });
</script>

<template>
    <Head title="Laboratory" />

    <PageHeader eyebrow="Laboratory" title="Your coding lab" description="A free space to try ideas. Write Python, build a Scratch game, make slides or a website, and share the ones you're proud of with the Community.">
        <template #actions>
            <Link :href="route('community.index')" class="btn-ghost"><Icon name="users" :size="16" /> Community</Link>
            <button type="button" class="btn-primary" @click="creating = true"><Icon name="plus" :size="16" /> New project</button>
        </template>
    </PageHeader>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Filter by type">
            <Link :href="route('lab.projects.index')" :class="!filters.language ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'" class="rounded-full px-4 py-1.5 text-sm font-bold">All</Link>
            <Link
                v-for="t in types"
                :key="t.key"
                :href="route('lab.projects.index', { language: t.key })"
                :class="filters.language === t.key ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'"
                class="rounded-full px-4 py-1.5 text-sm font-bold"
            >{{ t.label }}</Link>
        </nav>
        <p class="text-sm text-muted">{{ totals.projects }} project{{ totals.projects === 1 ? '' : 's' }} · {{ totals.shared }} shared</p>
    </div>

    <div v-if="projects.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ProjectCard v-for="p in projects" :key="p.id" :project="p" :href="route('lab.projects.edit', p.id)" :show-author="false" />
    </div>
    <div v-else class="card">
        <EmptyState icon="flask" :title="filters.language ? 'No projects of this type yet' : 'Your lab is empty'" description="Start a project, write a few lines, and press Run.">
            <button type="button" class="btn-primary" @click="creating = true"><Icon name="plus" :size="16" /> New project</button>
        </EmptyState>
    </div>

    <Modal :show="creating" max-width="lg" @close="creating = false">
        <form class="p-6" @submit.prevent="create">
            <h2 class="font-display text-xl font-extrabold text-ink">What do you want to make?</h2>
            <fieldset class="mt-4 grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">Project type</legend>
                <label
                    v-for="t in types"
                    :key="t.key"
                    :class="form.language === t.key ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line hover:bg-chip'"
                    class="flex cursor-pointer items-center gap-4 rounded-2xl border p-3 transition"
                >
                    <input v-model="form.language" type="radio" name="language" :value="t.key" class="sr-only" />
                    <span :class="typeColors[t.key]" class="grid size-12 shrink-0 place-items-center rounded-xl"><component :is="typeIcons[t.key]" :size="22" aria-hidden="true" /></span>
                    <span>
                        <span class="block font-bold text-ink">{{ t.label }}</span>
                        <span class="block text-sm text-muted">{{ t.description }}</span>
                    </span>
                </label>
            </fieldset>
            <label class="label mt-5" for="lab-title">Project name</label>
            <input id="lab-title" v-model="form.title" class="field" maxlength="80" placeholder="e.g. Number guessing game" required />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
            <p v-if="form.errors.language" class="mt-1 text-sm text-red-600">{{ form.errors.language }}</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="creating = false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="form.processing">Create and open</button>
            </div>
        </form>
    </Modal>
</template>
