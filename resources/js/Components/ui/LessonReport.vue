<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Icon from './Icon.vue';

/**
 * Students: a "Report a problem" button for exercises/lessons that seem wrong.
 * Teachers: a reminder of how many reports are still open.
 */
const props = defineProps({ lessonId: { type: Number, required: true }, can: Object, reports: Object });

const reasons = {
    wrong_answer: 'The answer or a test seems wrong',
    unclear: 'The instructions are unclear',
    broken: 'Something is broken or has a typo',
    other: 'Something else',
};
const open = ref(false);
const form = useForm({ reason: 'wrong_answer', message: '' });
const send = () => form.post(route('learning.reports.store', props.lessonId), { preserveScroll: true, onSuccess: () => { open.value = false; form.reset(); } });
</script>

<template>
    <div v-if="can?.manage && reports?.open" class="flex flex-wrap items-center gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <Icon name="flag" :size="18" />
        <span><strong>{{ reports.open }}</strong> {{ reports.open === 1 ? 'student reported a problem' : 'students reported a problem' }} with this lesson.</span>
        <Link :href="route('learning.reports.index')" class="ml-auto font-semibold underline underline-offset-2">Review reports</Link>
    </div>

    <div v-else-if="can?.learn" class="flex items-center justify-end">
        <span v-if="reports?.mine" class="inline-flex items-center gap-1.5 text-sm text-muted"><Icon name="flag" :size="15" /> You reported a problem. Your teacher will check it.</span>
        <button v-else type="button" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink" @click="open = true">
            <Icon name="flag" :size="15" /> Report a problem
        </button>
    </div>

    <Modal :show="open" max-width="md" @close="open = false">
        <form class="space-y-4 p-6" @submit.prevent="send">
            <div>
                <h3 class="font-display text-lg font-bold text-ink">Report a problem</h3>
                <p class="text-sm text-muted">Your teacher gets a notification and will fix it or explain.</p>
            </div>
            <fieldset class="space-y-2">
                <legend class="label">What is wrong?</legend>
                <label v-for="(label, key) in reasons" :key="key" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm" :class="form.reason === key ? 'border-brand-500 bg-brand-50' : 'border-line'">
                    <input v-model="form.reason" type="radio" :value="key" class="text-brand-600" /> {{ label }}
                </label>
            </fieldset>
            <div>
                <label for="report_message" class="label">Tell us more (optional)</label>
                <textarea id="report_message" v-model="form.message" rows="3" class="field" placeholder="e.g. Test 2 expects 10 but 3 + 4 is 7" />
                <p v-if="form.errors.message" class="mt-1 text-sm text-rose-600">{{ form.errors.message }}</p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="open = false">Cancel</button>
                <button class="btn-primary" :disabled="form.processing">Send report</button>
            </div>
        </form>
    </Modal>
</template>
