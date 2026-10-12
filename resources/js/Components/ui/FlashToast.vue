<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

// Toast from the server's flash message (success or error).
const page = usePage();
const toast = ref(null);
let timer;
watch(
    () => page.props.flash,
    (flash) => {
        const message = flash?.success ?? flash?.error;
        if (!message) return;
        toast.value = { type: flash.success ? 'success' : 'error', message };
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 4500);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <Transition
        enter-from-class="translate-y-4 opacity-0"
        leave-to-class="translate-y-4 opacity-0"
        enter-active-class="transition duration-200"
        leave-active-class="transition duration-200"
    >
        <div
            v-if="toast"
            role="status"
            class="fixed right-4 bottom-4 z-[60] flex max-w-sm items-start gap-3 rounded-2xl border bg-white p-4 shadow-xl shadow-ink/10"
            :class="toast.type === 'success' ? 'border-emerald-200' : 'border-rose-200'"
        >
            <span
                :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600'"
                class="grid size-8 shrink-0 place-items-center rounded-lg"
            >
                <Icon :name="toast.type === 'success' ? 'check' : 'alert-triangle'" :size="16" />
            </span>
            <p class="pt-1 text-sm font-medium text-ink">{{ toast.message }}</p>
            <button class="-m-1 ml-0 grid size-8 shrink-0 place-items-center rounded-lg text-muted hover:bg-chip hover:text-ink" aria-label="Dismiss notification" @click="toast = null"><Icon name="x" :size="16" /></button>
        </div>
    </Transition>
</template>
