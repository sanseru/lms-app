<script setup>
import { ref } from 'vue';
import { LoaderCircle, Play, Trash2 } from 'lucide-vue-next';
import { preparePython, runFree } from '../../../../Coding/resources/js/lib/runner';
import { buildSrcdoc } from '../../../../Coding/resources/js/lib/htmlCheck';
import SlideShow from './SlideShow.vue';

/**
 * Runs a Laboratory project in the browser with the Coding module's runners:
 * Python in Pyodide, JavaScript in a Web Worker, websites in a sandboxed iframe.
 * Presentations play as a slideshow that follows the text as it is typed.
 */
const props = defineProps({
    language: { type: String, required: true },
    code: { type: String, default: '' },
    runtime: { type: Object, required: true },
    tall: Boolean,
});

const busy = ref(false);
const output = ref(null);
const stdin = ref('');
const showInput = ref(false);
const preview = ref(props.language === 'html' ? buildSrcdoc(props.code) : '');
const opts = { timeLimitMs: 5000, indexURL: props.runtime.pyodide_url };

if (props.language === 'python') preparePython(opts.indexURL).catch(() => {});

const slideshow = ref(null);

async function run() {
    if (busy.value) return;
    if (props.language === 'presentation') return slideshow.value?.first();
    if (props.language === 'html') {
        preview.value = buildSrcdoc(props.code);
        return;
    }
    busy.value = true;
    output.value = null;
    try {
        output.value = await runFree(props.language, props.code, stdin.value, opts);
    } catch (e) {
        output.value = { output: '', error: 'Your code could not run: ' + e.message };
    } finally {
        busy.value = false;
    }
}

defineExpose({ run });
</script>

<template>
    <SlideShow v-if="language === 'presentation'" ref="slideshow" :source="code" :tall="tall" />
    <div v-else class="flex h-full flex-col overflow-hidden rounded-2xl bg-[#1a252d] text-[#d9e7e8]">
        <div class="flex items-center justify-between gap-2 border-b border-white/10 px-3 py-2">
            <span class="font-mono text-xs font-bold tracking-wider text-white/60 uppercase">{{ language === 'html' ? 'Preview' : 'Output' }}</span>
            <div class="flex items-center gap-1.5">
                <button v-if="language !== 'html'" type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-white/70 hover:bg-white/10" :aria-expanded="showInput" @click="showInput = !showInput">
                    Input
                </button>
                <button v-if="language !== 'html' && output" type="button" class="grid size-7 place-items-center rounded-lg text-white/70 hover:bg-white/10" aria-label="Clear output" @click="output = null">
                    <Trash2 :size="14" aria-hidden="true" />
                </button>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-success px-3 py-1.5 text-xs font-extrabold text-ink shadow-[0_3px_0_#2f7d52] active:translate-y-px active:shadow-none disabled:opacity-60" :disabled="busy" @click="run">
                    <LoaderCircle v-if="busy" :size="14" class="animate-spin" aria-hidden="true" />
                    <Play v-else :size="14" aria-hidden="true" />
                    Run
                </button>
            </div>
        </div>

        <div v-if="showInput && language !== 'html'" class="border-b border-white/10 p-3">
            <label class="mb-1 block text-xs font-semibold text-white/60" for="lab-stdin">Input for <code>input()</code> / <code>prompt()</code>, one value per line</label>
            <textarea id="lab-stdin" v-model="stdin" rows="2" class="w-full rounded-lg border-white/10 bg-black/30 font-mono text-sm text-white focus:border-brand-300 focus:ring-0" />
        </div>

        <iframe v-if="language === 'html'" :srcdoc="preview" sandbox="allow-scripts" title="Project preview" :class="tall ? 'min-h-[28rem]' : 'min-h-72'" class="w-full flex-1 bg-white" />
        <pre
            v-else
            aria-live="polite"
            :class="tall ? 'min-h-[28rem]' : 'min-h-56'"
            class="flex-1 overflow-auto p-4 font-mono text-sm leading-relaxed whitespace-pre-wrap"
        ><template v-if="busy"><span class="text-white/50">Running…</span></template><template v-else-if="output"><span class="text-[#9ad5ad]">{{ output.output }}</span><span v-if="output.error" class="text-[#ffb4a2]">{{ output.error }}</span><span v-if="!output.output && !output.error" class="text-white/50">(no output)</span></template><span v-else class="text-white/40">Press Run to see what your code does.</span></pre>
    </div>
</template>
