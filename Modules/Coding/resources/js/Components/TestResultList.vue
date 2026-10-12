<script setup>
import CodeLabel from './CodeLabel.vue';
import { Lightbulb } from 'lucide-vue-next';
import Icon from '@/Components/ui/Icon.vue';

/** Test result list. Same format for every mode (local results and server results). */
defineProps({ results: Array, mode: String });

const short = (v) => (v == null || v === '' ? '(empty)' : String(v).length > 300 ? String(v).slice(0, 300) + '…' : String(v));
</script>

<template>
    <ul class="space-y-2">
        <li
            v-for="(r, i) in results"
            :key="r.id"
            :class="r.passed === true ? 'border-emerald-200 bg-emerald-50/60' : r.passed === false ? 'border-rose-200 bg-rose-50/60' : 'border-line bg-white'"
            class="rounded-xl border p-3 text-sm"
        >
            <div class="flex items-center gap-2">
                <span
                    :class="r.passed === true ? 'bg-emerald-500 text-white' : r.passed === false ? 'bg-rose-500 text-white' : 'bg-chip text-muted'"
                    class="grid size-6 shrink-0 place-items-center rounded-lg"
                >
                    <Icon :name="r.passed === true ? 'check' : r.passed === false ? 'x' : r.hidden ? 'lock' : 'clock'" :size="13" />
                </span>
                <span class="font-semibold text-ink"><CodeLabel :text="r.label ?? (r.hidden ? `Hidden test ${i + 1}` : `Test ${i + 1}`)" /></span>
                <span v-if="r.passed === null && r.hidden" class="text-xs text-muted">graded when you submit</span>
                <span v-if="r.weight > 1" class="ml-auto text-xs text-muted">weight {{ r.weight }}</span>
            </div>
            <p v-if="r.error" class="mt-2 rounded-lg bg-rose-100/70 px-3 py-2 font-mono text-xs whitespace-pre-wrap text-rose-800">{{ r.error }}</p>
            <p v-if="r.message && r.passed === false" class="mt-2 flex gap-1.5 text-amber-800"><Lightbulb :size="15" class="mt-0.5 shrink-0" aria-hidden="true" /><span>{{ r.message }}</span></p>
            <dl v-if="!r.hidden && (r.input != null || r.expected != null) && r.passed !== true && mode !== 'html'" class="mt-2 grid gap-2 sm:grid-cols-3">
                <div v-if="r.input != null && r.input !== ''"><dt class="text-xs text-muted">{{ mode === 'function' ? 'Arguments' : 'Input' }}</dt><dd class="font-mono text-xs whitespace-pre-wrap text-ink">{{ short(r.input) }}</dd></div>
                <div><dt class="text-xs text-muted">Expected</dt><dd class="font-mono text-xs whitespace-pre-wrap text-ink">{{ short(r.expected) }}</dd></div>
                <div><dt class="text-xs text-muted">Your result</dt><dd class="font-mono text-xs whitespace-pre-wrap text-ink">{{ short(r.actual) }}</dd></div>
            </dl>
        </li>
    </ul>
</template>
