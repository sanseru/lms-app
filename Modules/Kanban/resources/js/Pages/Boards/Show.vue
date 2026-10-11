<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import ReviewActions from '../../Components/ReviewActions.vue';

const props = defineProps({ project: Object, board: Object, cards: Array, columns: Object, can: Object, nav: Object });

const style = {
    todo: { emoji: '📝', head: 'bg-slate-100 text-slate-700', body: 'bg-slate-50', ring: 'ring-slate-300' },
    doing: { emoji: '🔨', head: 'bg-sky-100 text-sky-800', body: 'bg-sky-50/60', ring: 'ring-sky-300' },
    review: { emoji: '👀', head: 'bg-amber-100 text-amber-800', body: 'bg-amber-50/60', ring: 'ring-amber-300' },
    done: { emoji: '✅', head: 'bg-emerald-100 text-emerald-800', body: 'bg-emerald-50/60', ring: 'ring-emerald-300' },
};
const keys = computed(() => Object.keys(props.columns).filter((k) => k !== 'review' || props.project.requires_review));

// Salinan lokal agar kartu langsung pindah saat diseret.
const cols = reactive({});
const sync = () => keys.value.forEach((k) => (cols[k] = props.cards.filter((c) => c.column === k)));
sync();
watch(() => props.cards, sync);

const canMove = (card, to) => {
    if (!props.can.work) return false;
    if (props.can.review || !props.project.requires_review) return true;
    return to !== 'done' && card.column !== 'done';
};

const move = (card, to, index) => {
    if (!canMove(card, to)) return;
    const from = card.column;
    cols[from] = cols[from].filter((c) => c.id !== card.id);
    const list = [...cols[to]];
    list.splice(Math.min(index, list.length), 0, { ...card, column: to });
    cols[to] = list;
    router.post(route('kanban.cards.move', card.id), { column: to, index }, { preserveScroll: true, preserveState: true, onError: sync });
};
const step = (card, dir) => {
    const i = keys.value.indexOf(card.column) + dir;
    if (i >= 0 && i < keys.value.length) move(card, keys.value[i], cols[keys.value[i]].length);
};

// Seret dan lepas (desktop)
const dragging = ref(null);
const over = ref(null);
const onDragStart = (e, card) => { dragging.value = card; e.dataTransfer.effectAllowed = 'move'; };
const onDragOver = (e, key) => {
    if (dragging.value && canMove(dragging.value, key)) { e.preventDefault(); over.value = key; }
};
const onDrop = (e, key) => {
    const card = dragging.value;
    over.value = null;
    dragging.value = null;
    if (!card) return;
    const items = [...e.currentTarget.querySelectorAll('[data-card]')].filter((el) => Number(el.dataset.card) !== card.id);
    const index = items.findIndex((el) => { const r = el.getBoundingClientRect(); return e.clientY < r.top + r.height / 2; });
    move(card, key, index === -1 ? items.length : index);
};

// Tambah kartu
const adding = ref(false);
const addForm = useForm({ title: '', description: '' });
const addCard = () => addForm.post(route('kanban.cards.store', props.board.id), { preserveScroll: true, onSuccess: () => { addForm.reset(); adding.value = false; } });

// Detail kartu
const openId = ref(null);
const open = computed(() => props.cards.find((c) => c.id === openId.value));
const editable = (c) => props.can.review || (props.can.work && !c.from_teacher && c.column !== 'done');
const editForm = useForm({ title: '', description: '' });
const show = (c) => { openId.value = c.id; editForm.title = c.title; editForm.description = c.description ?? ''; };
const save = () => editForm.put(route('kanban.cards.update', openId.value), { preserveScroll: true, onSuccess: () => (openId.value = null) });
const remove = () => router.delete(route('kanban.cards.destroy', openId.value), { preserveScroll: true, onSuccess: () => (openId.value = null) });

// Hasil proyek
const resultForm = useForm({ result_url: props.board.result_url ?? '', result_note: props.board.result_note ?? '' });
const saveResult = () => resultForm.put(route('kanban.boards.result', props.board.id), { preserveScroll: true });
</script>

<template>
    <Head :title="`${project.title} · ${board.student.name}`" />

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <Link :href="can.review ? route('kanban.projects.show', project.id) : route('kanban.projects.index')" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
            <Icon name="arrow-left" :size="15" /> {{ can.review ? 'Ringkasan proyek' : 'Semua proyek' }}
        </Link>
        <div v-if="nav" class="ml-auto flex gap-2">
            <Link v-if="nav.prev" :href="route('kanban.boards.show', nav.prev.id)" class="btn-ghost py-1.5 text-sm"><Icon name="chevron-left" :size="15" /> {{ nav.prev.name }}</Link>
            <Link v-if="nav.next" :href="route('kanban.boards.show', nav.next.id)" class="btn-ghost py-1.5 text-sm">{{ nav.next.name }} <Icon name="chevron-right" :size="15" /></Link>
        </div>
    </div>

    <!-- Kepala -->
    <div class="mb-6 flex flex-wrap items-center gap-5 rounded-3xl border border-line bg-white p-5 sm:p-6">
        <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-brand-50 text-4xl">{{ project.emoji }}</span>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-muted">{{ project.class.name }}<template v-if="project.due_date"> · <span :class="project.is_overdue && 'text-rose-600'">tenggat {{ project.due_date }}</span></template></p>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink">{{ project.title }}</h1>
            <p v-if="!can.mine" class="mt-1 flex items-center gap-2 text-sm text-muted"><Avatar :initials="board.student.initials" :seed="board.student.id" size="sm" /> Papan {{ board.student.name }}</p>
        </div>
        <div class="w-full sm:w-56">
            <div class="mb-1 flex justify-between text-sm"><span class="text-muted">{{ board.progress.done }}/{{ board.progress.total }} selesai</span><b class="text-ink">{{ board.progress.percent }}%</b></div>
            <ProgressBar :value="board.progress.percent" :tone="board.completed ? 'green' : 'brand'" />
        </div>
    </div>

    <div v-if="board.completed" role="status" class="mb-6 flex items-center gap-4 rounded-3xl bg-emerald-700 p-5 text-white">
        <span class="text-4xl" aria-hidden="true">🏆</span>
        <div>
            <p class="font-display text-xl font-extrabold">{{ can.mine ? 'Proyekmu selesai! Keren banget!' : 'Proyek selesai!' }}</p>
            <p class="text-sm text-emerald-50">Semua langkah sudah ada di kolom Selesai.</p>
        </div>
    </div>

    <details v-if="project.description" class="mb-6 rounded-2xl bg-brand-50 p-4 text-sm text-ink" :open="board.progress.done === 0">
        <summary class="cursor-pointer font-semibold text-brand-700">Penjelasan proyek dari guru</summary>
        <p class="mt-2 whitespace-pre-line">{{ project.description }}</p>
    </details>

    <!-- Papan -->
    <div class="-mx-4 snap-x snap-mandatory overflow-x-auto scroll-px-4 px-4 pb-2 sm:mx-0 sm:px-0">
        <div class="grid gap-4" :style="{ gridTemplateColumns: `repeat(${keys.length}, minmax(15rem, 1fr))` }">
            <section
                v-for="key in keys"
                :key="key"
                :class="[style[key].body, over === key && `ring-2 ${style[key].ring}`]"
                class="flex min-h-[18rem] snap-start flex-col rounded-2xl p-2 transition"
                @dragover="onDragOver($event, key)"
                @dragleave="over = over === key ? null : over"
                @drop="onDrop($event, key)"
            >
                <header :class="style[key].head" class="mb-2 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold">
                    <span>{{ style[key].emoji }}</span>
                    <span class="flex-1">{{ columns[key] }}</span>
                    <span class="rounded-full bg-white/70 px-2 text-xs">{{ cols[key].length }}</span>
                </header>

                <div class="flex flex-1 flex-col gap-2">
                    <article
                        v-for="c in cols[key]"
                        :key="c.id"
                        :data-card="c.id"
                        :draggable="can.work && (canMove(c, 'todo') || canMove(c, 'doing'))"
                        :class="[dragging?.id === c.id && 'opacity-40', c.feedback && key === 'doing' ? 'ring-2 ring-amber-300' : 'ring-1 ring-line']"
                        class="group cursor-pointer rounded-xl bg-white p-3 shadow-sm transition hover:shadow-md"
                        @dragstart="onDragStart($event, c)"
                        @dragend="dragging = null; over = null"
                        @click="show(c)"
                    >
                        <div class="flex items-start gap-2">
                            <p class="flex-1 text-sm font-semibold text-ink" :class="key === 'done' && 'text-ink/60 line-through decoration-emerald-400'">{{ c.title }}</p>
                            <Icon v-if="c.from_teacher" name="graduation-cap" :size="14" class="mt-0.5 shrink-0 text-brand-400" title="Langkah dari guru" />
                        </div>
                        <p v-if="c.description" class="mt-1 line-clamp-2 text-xs text-muted">{{ c.description }}</p>
                        <p v-if="c.feedback && key !== 'done'" class="mt-2 flex gap-1.5 rounded-lg bg-amber-50 p-2 text-xs text-amber-900"><Icon name="message-text" :size="13" class="mt-0.5 shrink-0" /> {{ c.feedback }}</p>
                        <p v-if="key === 'done' && c.reviewer" class="mt-1 text-xs font-semibold text-emerald-700">✓ dicek {{ c.reviewer }}</p>

                        <div v-if="can.review && key === 'review'" class="mt-3" @click.stop><ReviewActions :card="c" /></div>

                        <!-- Tombol pindah (layar sentuh) -->
                        <div v-if="can.work" class="mt-2 flex justify-between opacity-100 transition lg:opacity-0 lg:group-hover:opacity-100" @click.stop>
                            <button v-if="keys.indexOf(key) > 0 && canMove(c, keys[keys.indexOf(key) - 1])" class="rounded-md p-1 text-muted hover:bg-chip hover:text-ink" title="Geser ke kiri" @click="step(c, -1)"><Icon name="arrow-left" :size="14" /></button>
                            <span v-else />
                            <button v-if="keys.indexOf(key) < keys.length - 1 && canMove(c, keys[keys.indexOf(key) + 1])" class="rounded-md p-1 text-muted hover:bg-chip hover:text-ink" title="Geser ke kanan" @click="step(c, 1)"><Icon name="arrow-right" :size="14" /></button>
                        </div>
                    </article>

                    <p v-if="!cols[key].length" class="grid flex-1 place-items-center rounded-xl border-2 border-dashed border-black/5 p-4 text-center text-xs text-muted">
                        {{ key === 'review' ? 'Geser ke sini kalau langkahmu siap dicek guru' : key === 'done' ? 'Belum ada yang selesai' : 'Kosong' }}
                    </p>

                    <template v-if="key === 'todo' && can.work">
                        <form v-if="adding" class="space-y-2 rounded-xl bg-white p-2 ring-1 ring-brand-200" @submit.prevent="addCard">
                            <input v-model="addForm.title" type="text" maxlength="150" class="field py-1.5 text-sm" placeholder="Langkah baru…" autofocus />
                            <p v-if="addForm.errors.title" class="text-xs text-rose-600">{{ addForm.errors.title }}</p>
                            <div class="flex justify-end gap-1">
                                <button type="button" class="btn-ghost py-1 text-xs" @click="adding = false">Batal</button>
                                <button class="btn-primary py-1 text-xs" :disabled="addForm.processing || !addForm.title.trim()">Tambah</button>
                            </div>
                        </form>
                        <button v-else class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-muted hover:bg-white hover:text-ink" @click="adding = true"><Icon name="plus" :size="15" /> Tambah kartu</button>
                    </template>
                </div>
            </section>
        </div>
    </div>
    <p v-if="can.mine && project.requires_review" class="mt-3 text-xs text-muted">Seret kartu ke kanan saat kamu mengerjakannya. Kalau sudah, geser ke "Minta dicek guru" agar gurumu bisa menyetujuinya.</p>

    <!-- Hasil proyek -->
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <form v-if="can.work" class="card space-y-4 p-5 sm:p-6" @submit.prevent="saveResult">
            <div>
                <p class="font-display font-bold text-ink">Hasil proyek</p>
                <p class="text-sm text-muted">Tempel link karyamu, misalnya dari Scratch, Replit, atau GitHub Pages.</p>
            </div>
            <Field label="Link proyek" :error="resultForm.errors.result_url"><input v-model="resultForm.result_url" type="url" class="field" placeholder="https://scratch.mit.edu/projects/…" /></Field>
            <Field label="Cerita singkat" :error="resultForm.errors.result_note"><textarea v-model="resultForm.result_note" rows="3" class="field" placeholder="Apa yang paling seru atau paling sulit?" /></Field>
            <div class="flex justify-end"><button class="btn-primary" :disabled="resultForm.processing"><Icon name="check" :size="16" /> Simpan</button></div>
        </form>
        <div v-else-if="board.result_url || board.result_note" class="card space-y-2 p-5 sm:p-6">
            <p class="font-display font-bold text-ink">Hasil proyek</p>
            <a v-if="board.result_url" :href="board.result_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold break-all text-brand-600 hover:underline"><Icon name="external-link" :size="15" /> {{ board.result_url }}</a>
            <p v-if="board.result_note" class="text-sm whitespace-pre-line text-ink">{{ board.result_note }}</p>
        </div>
    </div>

    <!-- Detail kartu -->
    <Modal :show="!!open" max-width="lg" @close="openId = null">
        <div v-if="open" class="p-6">
            <div class="mb-4 flex items-center gap-2 text-xs font-semibold">
                <span :class="style[open.column].head" class="rounded-full px-2.5 py-1">{{ style[open.column].emoji }} {{ columns[open.column] }}</span>
                <span v-if="open.from_teacher" class="inline-flex items-center gap-1 text-brand-600"><Icon name="graduation-cap" :size="13" /> Langkah dari guru</span>
            </div>
            <form v-if="editable(open)" class="space-y-4" @submit.prevent="save">
                <Field label="Judul" :error="editForm.errors.title"><input v-model="editForm.title" type="text" maxlength="150" class="field" /></Field>
                <Field label="Catatan" :error="editForm.errors.description"><textarea v-model="editForm.description" rows="4" class="field" /></Field>
                <p v-if="open.feedback" class="flex gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"><Icon name="message-text" :size="16" class="mt-0.5 shrink-0" /> <span><b>Catatan guru:</b> {{ open.feedback }}</span></p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-ghost text-rose-600" @click="remove"><Icon name="trash" :size="15" /> Hapus</button>
                    <span class="flex-1" />
                    <button type="button" class="btn-ghost" @click="openId = null">Tutup</button>
                    <button class="btn-primary" :disabled="editForm.processing">Simpan</button>
                </div>
            </form>
            <div v-else class="space-y-3">
                <h3 class="font-display text-xl font-bold text-ink">{{ open.title }}</h3>
                <p v-if="open.description" class="text-sm whitespace-pre-line text-ink">{{ open.description }}</p>
                <p v-if="open.feedback" class="flex gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"><Icon name="message-text" :size="16" class="mt-0.5 shrink-0" /> <span><b>Catatan guru:</b> {{ open.feedback }}</span></p>
                <p v-if="open.done_at" class="text-sm text-emerald-700">Selesai {{ open.done_at }}<template v-if="open.reviewer"> · dicek {{ open.reviewer }}</template></p>
                <div class="flex justify-end"><button class="btn-ghost" @click="openId = null">Tutup</button></div>
            </div>
        </div>
    </Modal>
</template>
