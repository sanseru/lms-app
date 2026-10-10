<script setup>
import Modal from '@/Components/Modal.vue';
import Icon from './Icon.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Yakin?' },
    message: { type: String, default: null },
    confirmText: { type: String, default: 'Ya, lanjutkan' },
    danger: { type: Boolean, default: true },
    processing: { type: Boolean, default: false },
});
defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="$emit('close')">
        <div class="p-6">
            <div class="flex gap-4">
                <span :class="danger ? 'bg-rose-50 text-rose-600' : 'bg-brand-50 text-brand-600'" class="grid size-11 shrink-0 place-items-center rounded-xl">
                    <Icon :name="danger ? 'alert-triangle' : 'badge-check'" :size="20" />
                </span>
                <div>
                    <h3 class="font-display text-lg font-bold text-ink">{{ title }}</h3>
                    <p v-if="message" class="mt-1 text-sm text-muted">{{ message }}</p>
                    <slot />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="$emit('close')">Batal</button>
                <button type="button" :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="processing" @click="$emit('confirm')">
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
