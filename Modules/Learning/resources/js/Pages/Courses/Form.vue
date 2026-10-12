<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ course: Object, levels: Object, colors: Array, statuses: Object, programs: { type: Array, default: () => [] } });
const c = props.course ?? {};

const form = useForm({
    title: c.title ?? '',
    subject: c.subject ?? '',
    level: c.level ?? 'beginner',
    description: c.description ?? '',
    color: c.color ?? 'violet',
    status: c.status ?? 'draft',
    program_id: c.program_id ?? '',
    level_order: c.level_order ?? '',
});

const submit = () => (props.course ? form.put(route('learning.courses.update', props.course.id)) : form.post(route('learning.courses.store')));
</script>

<template>
    <Head :title="course ? 'Edit course' : 'New course'" />
    <PageHeader eyebrow="Learning" :title="course ? 'Edit course' : 'New course'" />

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Course details" description="A course is a curriculum that several classes can use at once.">
            <Field id="title" label="Course title" :error="form.errors.title" required class="sm:col-span-2">
                <input id="title" v-model="form.title" type="text" class="field" placeholder="e.g. Python Start: Programming Basics" />
            </Field>
            <Field id="subject" label="Subject" :error="form.errors.subject" required>
                <input id="subject" v-model="form.subject" type="text" class="field" placeholder="e.g. Python" />
            </Field>
            <Field id="level" label="Level" :error="form.errors.level" required>
                <select id="level" v-model="form.level" class="field">
                    <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
                </select>
            </Field>
            <Field id="description" label="Description" :error="form.errors.description" class="sm:col-span-2">
                <textarea id="description" v-model="form.description" rows="3" class="field" placeholder="What students will learn and build." />
            </Field>
            <Field label="Cover color">
                <div class="flex gap-2 pt-1">
                    <button
                        v-for="color in colors"
                        :key="color"
                        type="button"
                        :class="[classColors[color].solid, form.color === color ? 'ring-2 ring-ink ring-offset-2' : '']"
                        class="size-8 rounded-full transition"
                        :aria-label="color"
                        @click="form.color = color"
                    />
                </div>
            </Field>
            <Field label="Status" :error="form.errors.status" hint="Draft courses are only visible to teachers and admins.">
                <div class="flex gap-2">
                    <button
                        v-for="(label, key) in statuses"
                        :key="key"
                        type="button"
                        :class="form.status === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                        @click="form.status = key"
                    >
                        {{ label }}
                    </button>
                </div>
            </Field>
        </FormSection>

        <FormSection title="Program and level" description="Put this course in a program (e.g. Python) as one of its levels. Students finish level 1 before level 2 opens for them.">
            <Field id="program_id" label="Program" :error="form.errors.program_id" hint="Leave empty for a course that stands on its own.">
                <select id="program_id" v-model="form.program_id" class="field">
                    <option value="">No program</option>
                    <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </Field>
            <Field id="level_order" label="Level number" :error="form.errors.level_order" :hint="form.program_id ? '1 for the first level, 2 for the next, and so on.' : 'Only used inside a program.'">
                <input id="level_order" v-model="form.level_order" type="number" min="1" max="99" class="field" :disabled="!form.program_id" placeholder="e.g. 1" />
            </Field>
            <p v-if="!programs.length" class="text-sm text-muted sm:col-span-2">
                No programs yet. <Link :href="route('learning.programs.index')" class="font-semibold text-brand-600 hover:underline">Create one on the Programs page</Link>.
            </p>
        </FormSection>

        <div class="flex justify-end gap-2 pt-6">
            <Link :href="course ? route('learning.courses.show', course.id) : route('learning.courses.index')" class="btn-ghost">Cancel</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Save</button>
        </div>
    </form>
</template>
