<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useFullscreen } from '@/lib/fullscreen';
import CodeEditor from '../../../../../Coding/resources/js/Components/CodeEditor.vue';
import RunPanel from '../../Components/RunPanel.vue';
import ScratchFrame from '../../Components/ScratchFrame.vue';

const props = defineProps({ project: Object, runtime: Object });

const isScratch = props.project.language === 'scratch';
const isSlides = props.project.language === 'presentation';
const form = useForm({ title: props.project.title, description: props.project.description ?? '', code: props.project.code });
const runner = ref(null);
const confirming = ref(false);

// Scratch: the editor keeps its own state; the .sb3 it opened with stays the same after
// saving, so the iframe is not reloaded (and the student's undo history is kept).
const scratch = ref(null);
const scratchDirty = ref(false);
const startFile = props.project.file_url;
const dirty = computed(() => form.isDirty || scratchDirty.value);

// Fullscreen: code and the run panel (or the Scratch editor) over the whole screen.
const workspace = ref(null);
const { active: fullscreen, native: nativeFullscreen, toggle: toggleFullscreen } = useFullscreen(workspace);

async function save(then) {
    const done = { preserveScroll: true, onSuccess: () => ((scratchDirty.value = false), then?.()) };
    if (!isScratch) return form.transform((d) => d).put(route('lab.projects.update', props.project.id), done);

    let blob;
    try {
        blob = await scratch.value.save();
    } catch (e) {
        return form.setError('file', e.message);
    }
    // PHP only reads uploads from POST, so the PUT is sent as a POST with _method.
    form.transform((d) => ({ ...d, code: null, _method: 'put', file: new File([blob], 'project.sb3', { type: 'application/x.scratch.sb3' }) }))
        .post(route('lab.projects.update', props.project.id), { ...done, forceFormData: true });
}
const share = () => save(() => router.post(route('lab.projects.share', props.project.id), {}, { preserveScroll: true }));
const unshare = () => router.delete(route('lab.projects.unshare', props.project.id), { preserveScroll: true });
const destroy = () => {
    scratchDirty.value = false;
    form.defaults();
    router.delete(route('lab.projects.destroy', props.project.id));
};

const onKey = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        save();
    }
};

// Don't lose work: ask before leaving with unsaved changes.
const beforeUnload = (e) => dirty.value && e.preventDefault();
const stopGuard = router.on('before', (event) => {
    if (dirty.value && event.detail.visit.method === 'get') return window.confirm('You have unsaved changes. Leave without saving?');
});
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnload);
    stopGuard();
});
</script>

<template>
    <Head :title="`${project.title} · Laboratory`" />

    <div @keydown="onKey">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('lab.projects.index')" class="inline-flex items-center gap-1.5 text-sm font-bold text-brand-600 hover:text-brand-700"><Icon name="arrow-left" :size="16" /> All projects</Link>
            <div class="flex flex-wrap items-center gap-2">
                <span v-if="dirty" class="text-xs font-semibold text-muted">Unsaved changes</span>
                <Link v-if="project.shared" :href="route('community.show', project.id)" class="btn-ghost"><Icon name="eye" :size="16" /> View in Community</Link>
                <button type="button" class="btn-ghost" :disabled="form.processing" @click="save()"><Icon name="check" :size="16" /> Save</button>
                <button v-if="project.shared" type="button" class="btn-ghost" @click="unshare"><Icon name="eye-off" :size="16" /> Make private</button>
                <button v-else-if="!project.hidden" type="button" class="btn-accent" :disabled="form.processing" @click="share"><Icon name="send" :size="16" /> Save &amp; share</button>
                <button type="button" class="btn-ghost text-red-600" aria-label="Delete project" @click="confirming = true"><Icon name="trash" :size="16" /></button>
            </div>
        </div>

        <p v-if="project.hidden" class="mb-4 flex items-center gap-2 rounded-xl bg-accent-50 px-4 py-3 text-sm font-semibold text-accent-700">
            <Icon name="alert-triangle" :size="18" /> A teacher removed this project from the Community. You can still keep working on it here.
        </p>

        <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
            <div>
                <label class="label" for="p-title">Project name</label>
                <input id="p-title" v-model="form.title" class="field" maxlength="80" required />
                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
            </div>
            <div>
                <label class="label" for="p-desc">What does it do? <span class="font-normal text-muted">(optional)</span></label>
                <input id="p-desc" v-model="form.description" class="field" maxlength="500" placeholder="Tell others about your project" />
            </div>
        </div>

        <!-- Scratch: the whole editor; Save also sits in its bar for fullscreen -->
        <div v-if="isScratch" ref="workspace" :class="fullscreen && ['flex flex-col bg-paper p-2 sm:p-3', !nativeFullscreen && 'fixed inset-0 z-[55]']">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="eyebrow">Scratch</span>
                <span class="ml-auto hidden text-xs text-muted sm:inline">Press Save to keep your project<template v-if="fullscreen"> · Esc to exit</template></span>
                <button v-if="fullscreen" type="button" class="btn-ghost px-2.5 py-1.5 text-xs" :disabled="form.processing" @click="save()"><Icon name="check" :size="15" /> Save</button>
                <button type="button" class="btn-ghost px-2.5 py-1.5 text-xs" :aria-pressed="fullscreen" @click="toggleFullscreen">
                    <Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="15" /> {{ fullscreen ? 'Exit fullscreen' : 'Fullscreen' }}
                </button>
            </div>
            <p v-if="form.errors.file" class="mb-2 text-sm text-red-600" role="alert">{{ form.errors.file }}</p>
            <ScratchFrame ref="scratch" :src="startFile" :class="fullscreen ? 'min-h-0 flex-1' : 'h-[calc(100vh-15rem)] min-h-[560px]'" :title="`Scratch editor: ${project.title}`" @change="scratchDirty = true" />
        </div>

        <div
            v-else
            ref="workspace"
            :class="fullscreen && ['overflow-y-auto bg-paper p-4 sm:p-6 [&_.cm-editor]:min-h-[calc(100vh-8rem)]! [&_.cm-scroller]:min-h-[calc(100vh-8rem)]!', !nativeFullscreen && 'fixed inset-0 z-[55]']"
            class="grid gap-4 lg:grid-cols-2"
        >
            <div class="min-w-0">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="eyebrow">{{ isSlides ? 'Slides' : `${project.language_label} code` }}</span>
                    <span class="ml-auto hidden text-xs text-muted sm:inline">
                        <template v-if="isSlides">--- starts a new slide · # makes a title · Ctrl + S to save</template>
                        <template v-else>Ctrl + Enter to run · Ctrl + S to save</template>
                        <template v-if="fullscreen"> · Esc to exit</template>
                    </span>
                    <button type="button" class="btn-ghost px-2.5 py-1.5 text-xs" :aria-pressed="fullscreen" @click="toggleFullscreen">
                        <Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="15" /> {{ fullscreen ? 'Exit fullscreen' : 'Fullscreen' }}
                    </button>
                </div>
                <CodeEditor v-model="form.code" :language="project.language" min-height="28rem" :label="isSlides ? 'Slides' : `${project.language_label} code`" @run="runner?.run()" />
                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
            </div>
            <div class="min-w-0">
                <div class="mb-2"><span class="eyebrow">{{ isSlides ? 'Slideshow' : 'Try it' }}</span></div>
                <RunPanel ref="runner" :language="project.language" :code="form.code ?? ''" :runtime="runtime" tall />
            </div>
        </div>
    </div>

    <ConfirmDialog :show="confirming" title="Delete this project?" message="It will also disappear from the Community, with its likes and comments." confirm-text="Delete" @close="confirming = false" @confirm="destroy" />
</template>
