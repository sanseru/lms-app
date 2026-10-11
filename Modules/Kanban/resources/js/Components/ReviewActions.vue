<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({ card: { type: Object, required: true } });
const returning = ref(false);
const form = useForm({ decision: 'approve', feedback: '' });

const send = (decision) => {
    form.decision = decision;
    form.post(route('kanban.cards.review', props.card.id), { preserveScroll: true, onSuccess: () => { returning.value = false; form.reset(); } });
};
</script>

<template>
    <div>
        <div v-if="!returning" class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600" :disabled="form.processing" @click="send('approve')">
                <Icon name="check" :size="14" /> Setujui
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-amber-800 ring-1 ring-amber-300 hover:bg-amber-50" @click="returning = true">
                <Icon name="rotate-ccw" :size="14" /> Kembalikan
            </button>
        </div>
        <form v-else class="space-y-2" @submit.prevent="send('return')">
            <textarea v-model="form.feedback" rows="2" class="field text-sm" placeholder="Apa yang perlu diperbaiki?" />
            <p v-if="form.errors.feedback" class="text-xs text-rose-600">{{ form.errors.feedback }}</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-ghost py-1 text-xs" @click="returning = false">Batal</button>
                <button class="btn-primary py-1 text-xs" :disabled="form.processing">Kirim catatan</button>
            </div>
        </form>
    </div>
</template>
