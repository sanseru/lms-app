<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';

defineProps({ reports: Object, status: String, openCount: Number });

const closing = ref(null);
const form = useForm({ status: 'resolved', response: '' });
const start = (r) => {
    closing.value = r.id;
    form.reset();
};
const close = (r) => form.post(route('learning.reports.resolve', r.id), { preserveScroll: true, onSuccess: () => (closing.value = null) });
</script>

<template>
    <Head title="Reported problems" />

    <PageHeader eyebrow="Learning" title="Reported problems" description="Students flag lessons and exercises that look wrong. Fix the lesson, then close the report so the student knows.">
        <template #back>
            <Link :href="route('learning.grading.index')" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Grading</Link>
        </template>
    </PageHeader>

    <div class="mb-5 flex gap-2">
        <Link
            v-for="(label, key) in { open: `Open (${openCount})`, closed: 'Closed' }"
            :key="key"
            :href="route('learning.reports.index', { status: key })"
            :class="status === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="rounded-full px-4 py-2 text-sm font-semibold transition"
        >
            {{ label }}
        </Link>
    </div>

    <div v-if="reports.data.length" class="space-y-3">
        <div v-for="r in reports.data" :key="r.id" class="card p-5">
            <div class="flex flex-wrap items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-800"><Icon name="flag" :size="18" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ r.reason }}</p>
                    <p class="text-sm text-muted">
                        <Link :href="route('learning.lessons.show', r.lesson.id)" class="font-semibold text-brand-600 hover:underline">{{ r.lesson.title }}</Link>
                        <span v-if="r.lesson.course"> · {{ r.lesson.course }}</span> · {{ r.student }} · {{ r.at }}
                    </p>
                    <p v-if="r.message" class="mt-2 rounded-xl bg-chip/60 p-3 text-sm whitespace-pre-line text-ink">{{ r.message }}</p>
                    <p v-if="r.status !== 'open'" class="mt-2 text-sm text-muted">
                        <Badge :tone="r.status === 'resolved' ? 'green' : 'gray'">{{ r.status_label }}</Badge>
                        <span v-if="r.resolver"> by {{ r.resolver }}</span><span v-if="r.response">: {{ r.response }}</span>
                    </p>
                </div>
                <div v-if="r.status === 'open' && closing !== r.id" class="flex gap-2">
                    <Link :href="route('learning.lessons.edit', r.lesson.id)" class="btn-ghost py-2">Edit lesson</Link>
                    <button class="btn-primary py-2" @click="start(r)">Close report</button>
                </div>
            </div>

            <form v-if="closing === r.id" class="mt-4 space-y-3 border-t border-line pt-4" @submit.prevent="close(r)">
                <div class="flex flex-wrap gap-2">
                    <label v-for="(label, key) in { resolved: 'Fixed it', dismissed: 'No change needed' }" :key="key" class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm" :class="form.status === key ? 'border-brand-500 bg-brand-50' : 'border-line'">
                        <input v-model="form.status" type="radio" :value="key" class="text-brand-600" /> {{ label }}
                    </label>
                </div>
                <label class="block">
                    <span class="label">Message to the student (optional)</span>
                    <textarea v-model="form.response" rows="2" class="field" placeholder="e.g. Good catch! Test 2 is fixed, try submitting again." />
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-ghost" @click="closing = null">Cancel</button>
                    <button class="btn-primary" :disabled="form.processing">Close and notify</button>
                </div>
            </form>
        </div>
        <Pagination :meta="reports" />
    </div>
    <EmptyState v-else icon="flag" :title="status === 'open' ? 'No open reports' : 'No closed reports yet'" description="When a student reports a problem with a lesson, it shows up here." class="card" />
</template>
