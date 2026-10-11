<script setup>
import { useForm } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({ submission: Object, maxScore: Number });
const emit = defineEmits(['done']);

const form = useForm({
    status: 'graded',
    score: props.submission.score ?? '',
    feedback: props.submission.feedback ?? '',
});

const submit = (status) => {
    form.status = status;
    form.post(route('learning.submissions.grade', props.submission.id), { preserveScroll: true, onSuccess: () => emit('done') });
};
</script>

<template>
    <form class="space-y-3 rounded-2xl bg-chip/60 p-4" @submit.prevent="submit('graded')">
        <div class="flex items-center gap-3">
            <label :for="`score-${submission.id}`" class="text-sm font-semibold text-ink">Score</label>
            <input :id="`score-${submission.id}`" v-model="form.score" type="number" min="0" :max="maxScore" class="field w-24 py-2" />
            <span class="text-sm text-muted">/ {{ maxScore }}</span>
        </div>
        <p v-if="form.errors.score" class="text-sm text-rose-600">{{ form.errors.score }}</p>
        <textarea v-model="form.feedback" rows="2" class="field text-sm" placeholder="Feedback for the student (required when asking for a revision)" :aria-label="`Feedback for ${submission.student?.name ?? 'the student'}`" />
        <p v-if="form.errors.feedback" class="text-sm text-rose-600">{{ form.errors.feedback }}</p>
        <div class="flex flex-wrap justify-end gap-2">
            <button type="button" class="btn-ghost py-2 text-sm" :disabled="form.processing" @click="submit('revision')"><Icon name="rotate-ccw" :size="15" /> Ask for revision</button>
            <button type="submit" class="btn-primary py-2 text-sm" :disabled="form.processing"><Icon name="check" :size="15" /> Save grade</button>
        </div>
    </form>
</template>
