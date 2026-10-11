<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ project: Object, classes: Array, defaultClass: Number });
const editing = computed(() => !!props.project);

const form = useForm({
    class_room_id: props.project?.class_room_id ?? props.defaultClass ?? (props.classes.length === 1 ? props.classes[0].id : null),
    title: props.project?.title ?? '',
    description: props.project?.description ?? '',
    emoji: props.project?.emoji ?? '🚀',
    due_date: props.project?.due_date ?? '',
    requires_review: props.project?.requires_review ?? true,
    status: props.project?.status ?? 'active',
    tasks: props.project?.tasks?.map((t) => ({ ...t })) ?? [{ id: null, title: '', description: '' }],
});

const emojis = ['🚀', '🎮', '🌐', '🤖', '🐱', '🎨', '🧩', '🏆', '📱', '🌋', '🐍', '⭐'];

const presets = [
    { label: 'Scratch game', emoji: '🐱', tasks: ['Write down your game idea and rules', 'Pick sprites and a backdrop', 'Make the character move', 'Add a score and lives', 'Test it with a friend', 'Share your project link'] },
    { label: 'Python program', emoji: '🐍', tasks: ['Write down what your program does', 'Add input and output', 'Add an if statement', 'Add a loop', 'Test it with 3 example inputs', 'Tidy up the code and add comments'] },
    { label: 'Personal website', emoji: '🌐', tasks: ['Sketch the page on paper', 'Build the HTML structure', 'Add pictures with alt text', 'Style it with CSS', 'Make it look good on a phone', 'Publish it and share the link'] },
];
const applyPreset = (p) => {
    form.emoji = p.emoji;
    form.tasks = p.tasks.map((title) => ({ id: null, title, description: '' }));
    if (!form.title) form.title = p.label;
};

const addTask = () => form.tasks.push({ id: null, title: '', description: '' });
const removeTask = (i) => form.tasks.splice(i, 1);
const moveTask = (i, d) => {
    const j = i + d;
    if (j < 0 || j >= form.tasks.length) return;
    [form.tasks[i], form.tasks[j]] = [form.tasks[j], form.tasks[i]];
};

const clean = (d) => ({ ...d, tasks: d.tasks.filter((t) => t.title.trim()), due_date: d.due_date || null });
const submit = () => {
    if (editing.value) {
        // eslint-disable-next-line no-unused-vars
        form.transform(({ class_room_id, ...rest }) => clean(rest)).put(route('kanban.projects.update', props.project.id), { preserveScroll: true });
    } else {
        form.transform(clean).post(route('kanban.projects.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="editing ? 'Edit project' : 'New project'" />

    <PageHeader eyebrow="Projects" :title="editing ? 'Edit project' : 'New project'" description="Every student in the class gets their own kanban board with the steps below.">
        <template #back>
            <Link :href="editing ? route('kanban.projects.show', project.id) : route('kanban.projects.index')" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Back</Link>
        </template>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-5" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-3">
            <div class="card space-y-5 p-5 sm:p-6">
                <Field v-if="!editing" label="Class" :error="form.errors.class_room_id" required>
                    <div v-if="classes.length" class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="c in classes"
                            :key="c.id"
                            type="button"
                            :class="form.class_room_id === c.id ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line hover:border-brand-200'"
                            class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-sm font-semibold text-ink"
                            @click="form.class_room_id = c.id"
                        >
                            <span :class="(classColors[c.color] ?? classColors.violet).dot" class="size-2.5 rounded-full" /> <span class="truncate">{{ c.name }}</span>
                        </button>
                    </div>
                    <p v-else class="text-sm text-muted">You are not teaching any active classes yet.</p>
                </Field>

                <div class="flex gap-4">
                    <Field label="Icon" class="shrink-0">
                        <div class="grid grid-cols-4 gap-1 rounded-xl border border-line p-1.5">
                            <button
                                v-for="e in emojis"
                                :key="e"
                                type="button"
                                :class="form.emoji === e ? 'bg-brand-100 ring-2 ring-brand-400' : 'hover:bg-chip'"
                                class="grid size-9 place-items-center rounded-lg text-xl"
                                @click="form.emoji = e"
                            >{{ e }}</button>
                        </div>
                    </Field>
                    <div class="min-w-0 flex-1 space-y-5">
                        <Field label="Project title" :error="form.errors.title" required>
                            <input v-model="form.title" type="text" maxlength="150" class="field" placeholder="e.g. Catch the Ball Game" />
                        </Field>
                        <Field label="Due date" :error="form.errors.due_date" hint="Optional">
                            <input v-model="form.due_date" type="date" class="field" />
                        </Field>
                    </div>
                </div>

                <Field label="Instructions for students" :error="form.errors.description" hint="What to build and how to hand in the result.">
                    <textarea v-model="form.description" rows="4" class="field" placeholder="Make a simple game with at least one character you can move…" />
                </Field>

                <label class="flex items-start gap-3 rounded-xl bg-chip/60 p-4 text-sm">
                    <input v-model="form.requires_review" type="checkbox" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" />
                    <span>
                        <span class="font-semibold text-ink">Teacher reviews every step</span>
                        <span class="block text-xs text-muted">Students move cards to "Ready for review", then the teacher approves them or sends them back with a note. Turn this off to let students mark steps done themselves.</span>
                    </span>
                </label>

                <Field v-if="editing" label="Status">
                    <select v-model="form.status" class="field">
                        <option value="active">Active</option>
                        <option value="archived">Archived (students can't change their boards)</option>
                    </select>
                </Field>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="card p-5 sm:p-6">
                <div class="mb-3 flex items-center justify-between">
                    <p class="font-display font-bold text-ink">Default steps</p>
                    <span class="text-xs text-muted">{{ form.tasks.length }} {{ form.tasks.length === 1 ? 'card' : 'cards' }}</span>
                </div>
                <div v-if="!editing" class="mb-4 flex flex-wrap gap-2">
                    <span class="text-xs text-muted">Use a template:</span>
                    <button v-for="p in presets" :key="p.label" type="button" class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100" @click="applyPreset(p)">{{ p.emoji }} {{ p.label }}</button>
                </div>
                <ol class="space-y-2">
                    <li v-for="(t, i) in form.tasks" :key="i" class="rounded-xl border border-line bg-white p-2.5">
                        <div class="flex items-center gap-2">
                            <span class="grid size-6 shrink-0 place-items-center rounded-md bg-brand-50 text-xs font-bold text-brand-700">{{ i + 1 }}</span>
                            <input v-model="t.title" type="text" maxlength="150" class="field py-1.5 text-sm" placeholder="Step title" />
                            <div class="flex shrink-0">
                                <button type="button" class="rounded-md p-1 text-muted hover:bg-chip hover:text-ink disabled:opacity-30" :disabled="i === 0" title="Move up" @click="moveTask(i, -1)"><Icon name="arrow-up" :size="14" /></button>
                                <button type="button" class="rounded-md p-1 text-muted hover:bg-chip hover:text-ink disabled:opacity-30" :disabled="i === form.tasks.length - 1" title="Move down" @click="moveTask(i, 1)"><Icon name="arrow-down" :size="14" /></button>
                                <button type="button" class="rounded-md p-1 text-muted hover:bg-rose-50 hover:text-rose-600" title="Remove" @click="removeTask(i)"><Icon name="x" :size="14" /></button>
                            </div>
                        </div>
                        <textarea v-model="t.description" rows="1" class="field mt-2 py-1.5 text-xs" placeholder="Hint (optional)" />
                        <p v-if="form.errors[`tasks.${i}.title`]" class="mt-1 text-xs text-rose-600">{{ form.errors[`tasks.${i}.title`] }}</p>
                    </li>
                </ol>
                <button type="button" class="btn-ghost mt-3 w-full justify-center text-sm" @click="addTask"><Icon name="plus" :size="16" /> Add step</button>
                <p class="mt-3 text-xs text-muted">Students can add their own cards too. Edited steps also change on the boards of students who haven't finished them yet.</p>
            </div>

            <div class="flex justify-end gap-2">
                <Link :href="editing ? route('kanban.projects.show', project.id) : route('kanban.projects.index')" class="btn-ghost">Cancel</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="16" /> {{ editing ? 'Save' : 'Create project' }}</button>
            </div>
        </div>
    </form>
</template>
