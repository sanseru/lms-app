<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/ui/Badge.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { useConfirm } from '@/composables/useConfirm';
import { classColors } from '@/lib/format';

const props = defineProps({ programs: Array, unassigned: Array, colors: Array });

const confirm = useConfirm();
const editing = ref(null); // program id being edited, or 'new'
const form = useForm({ name: '', description: '', color: 'violet' });

const startNew = () => {
    form.reset();
    form.clearErrors();
    editing.value = 'new';
};
const startEdit = (p) => {
    form.name = p.name;
    form.description = p.description ?? '';
    form.color = p.color;
    form.clearErrors();
    editing.value = p.id;
};
const save = () => {
    const done = { preserveScroll: true, onSuccess: () => (editing.value = null) };
    editing.value === 'new' ? form.post(route('learning.programs.store'), done) : form.put(route('learning.programs.update', editing.value), done);
};
</script>

<template>
    <Head title="Programs" />
    <PageHeader eyebrow="Learning" title="Programs" description="A program is one programming language or track, like Python. Its courses are the levels students go through one by one: Python Start 1, then Python Start 2, and so on.">
        <template #actions>
            <button v-if="editing !== 'new'" type="button" class="btn-primary" @click="startNew"><Icon name="plus" :size="18" /> New program</button>
        </template>
    </PageHeader>

    <!-- Create / edit form -->
    <form v-if="editing" class="card mb-6 grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="save">
        <h2 class="font-display font-bold text-ink sm:col-span-2">{{ editing === 'new' ? 'New program' : 'Edit program' }}</h2>
        <Field id="p-name" label="Name" :error="form.errors.name" required>
            <input id="p-name" v-model="form.name" type="text" class="field" maxlength="80" placeholder="e.g. Python" />
        </Field>
        <Field label="Color">
            <div class="flex flex-wrap gap-2 pt-1">
                <button
                    v-for="c in colors"
                    :key="c"
                    type="button"
                    :class="[classColors[c].solid, form.color === c ? 'ring-2 ring-ink ring-offset-2' : '']"
                    class="size-8 rounded-full transition"
                    :aria-label="c"
                    :aria-pressed="form.color === c"
                    @click="form.color = c"
                />
            </div>
        </Field>
        <Field id="p-desc" label="Description" :error="form.errors.description" class="sm:col-span-2">
            <input id="p-desc" v-model="form.description" type="text" class="field" maxlength="500" placeholder="e.g. From your first print() to real apps." />
        </Field>
        <div class="flex justify-end gap-2 sm:col-span-2">
            <button type="button" class="btn-ghost" @click="editing = null">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Save</button>
        </div>
    </form>

    <div v-if="programs.length" class="grid gap-4 lg:grid-cols-2">
        <section v-for="p in programs" :key="p.id" class="card overflow-hidden">
            <header class="flex items-start gap-3 border-b border-line p-5">
                <span :class="(classColors[p.color] ?? classColors.violet).solid" class="mt-1 size-3 shrink-0 rounded-full" />
                <div class="min-w-0 flex-1">
                    <h2 class="font-display text-lg font-extrabold text-ink">{{ p.name }}</h2>
                    <p v-if="p.description" class="text-sm text-muted">{{ p.description }}</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink" :aria-label="`Edit ${p.name}`" @click="startEdit(p)"><Icon name="pencil" :size="16" /></button>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-muted hover:bg-rose-50 hover:text-rose-600"
                    :aria-label="`Delete ${p.name}`"
                    @click="confirm.ask({ title: `Delete ${p.name}?`, message: 'Its courses are kept, they just no longer belong to a program.', url: route('learning.programs.destroy', p.id), confirmText: 'Delete' })"
                >
                    <Icon name="trash" :size="16" />
                </button>
            </header>
            <ol v-if="p.levels.length" class="divide-y divide-line">
                <li v-for="l in p.levels" :key="l.id" class="flex items-center gap-3 px-5 py-3">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-extrabold text-brand-700">{{ l.level_order ?? '–' }}</span>
                    <Link :href="route('learning.courses.show', l.id)" class="min-w-0 flex-1 truncate font-semibold text-ink hover:text-brand-600">{{ l.title }}</Link>
                    <span class="text-xs text-muted">{{ l.lessons_count }} {{ l.lessons_count === 1 ? 'item' : 'items' }}</span>
                    <Badge v-if="l.status !== 'published'" tone="gray">Draft</Badge>
                </li>
            </ol>
            <p v-else class="px-5 py-6 text-center text-sm text-muted">No levels yet. Open a course, click Edit, and choose this program.</p>
        </section>
    </div>
    <div v-else-if="!editing" class="card">
        <EmptyState icon="layers" title="No programs yet" description="Create a program such as Python, then link its courses as levels.">
            <button type="button" class="btn-primary" @click="startNew"><Icon name="plus" :size="18" /> New program</button>
        </EmptyState>
    </div>

    <section v-if="unassigned.length" class="mt-6">
        <h2 class="mb-2 font-display font-bold text-ink">Courses without a program</h2>
        <p class="mb-3 text-sm text-muted">They work on their own. Edit a course to put it in a program as a level.</p>
        <ul class="flex flex-wrap gap-2">
            <li v-for="c in unassigned" :key="c.id">
                <Link :href="route('learning.courses.edit', c.id)" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-ink ring-1 ring-line hover:bg-chip">
                    {{ c.title }} <Icon name="pencil" :size="13" class="text-muted" />
                </Link>
            </li>
        </ul>
    </section>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
