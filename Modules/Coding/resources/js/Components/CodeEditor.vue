<script setup>
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { python } from '@codemirror/lang-python';
import { oneDark } from '@codemirror/theme-one-dark';
import { Compartment, EditorState } from '@codemirror/state';
import { basicSetup, EditorView } from 'codemirror';
import { keymap } from '@codemirror/view';
import { indentWithTab } from '@codemirror/commands';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

/** Code editor (CodeMirror 6) with large text so it is comfortable for kids and tablets. */
const props = defineProps({
    language: { type: String, default: 'python' },
    readonly: { type: Boolean, default: false },
    minHeight: { type: String, default: '280px' },
    label: { type: String, default: 'Code editor' },
});
const model = defineModel({ type: String, default: '' });
const emit = defineEmits(['run']);

const host = ref(null);
let view;
const lang = new Compartment();
// Slides (markdown) and other plain text get no code highlighting.
const langFor = (l) => (l === 'html' ? html() : l === 'javascript' ? javascript() : l === 'presentation' ? [] : python());

onMounted(() => {
    view = new EditorView({
        parent: host.value,
        state: EditorState.create({
            doc: model.value,
            extensions: [
                basicSetup,
                EditorView.lineWrapping,
                keymap.of([indentWithTab, { key: 'Mod-Enter', run: () => (emit('run'), true) }]),
                lang.of(langFor(props.language)),
                oneDark,
                EditorState.readOnly.of(props.readonly),
                EditorView.contentAttributes.of({ 'aria-label': props.label }),
                EditorView.theme({
                    '&': { fontSize: '15px', minHeight: props.minHeight, borderRadius: '1rem' },
                    '.cm-scroller': { fontFamily: 'var(--font-mono)', lineHeight: '1.65', minHeight: props.minHeight },
                    '.cm-gutters': { borderTopLeftRadius: '1rem', borderBottomLeftRadius: '1rem' },
                }),
                EditorView.updateListener.of((u) => {
                    if (u.docChanged) model.value = u.state.doc.toString();
                }),
            ],
        }),
    });
    // Re-measure after fonts load so line numbers line up with the code.
    document.fonts?.ready.then(() => view?.requestMeasure());
});

watch(model, (v) => {
    if (view && v !== view.state.doc.toString()) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: v } });
});
watch(() => props.language, (l) => view?.dispatch({ effects: lang.reconfigure(langFor(l)) }));
onBeforeUnmount(() => view?.destroy());
</script>

<template>
    <div ref="host" class="overflow-hidden rounded-2xl bg-[#282c34] shadow-inner ring-1 ring-black/10" />
</template>
