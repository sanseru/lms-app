<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import { classColors } from '@/lib/format';
import CodeSnippet from '../../Components/CodeSnippet.vue';

const props = defineProps({ question: Object, answers: Array, can: Object, isStaff: Boolean });
const role = computed(() => usePage().props.auth.user.role);

const opts = { preserveScroll: true };
const voteQuestion = () => router.post(route('forum.questions.vote', props.question.id), {}, opts);
const togglePin = () => router.post(route('forum.questions.pin', props.question.id), {}, opts);
const toggleResolve = () => router.post(route('forum.questions.resolve', props.question.id), {}, opts);
const accept = (a) => router.post(route('forum.answers.accept', a.id), {}, opts);
const voteAnswer = (a) => router.post(route('forum.answers.vote', a.id), {}, opts);

// Jawaban baru
const showCode = ref(false);
const form = useForm({ body: '', code: '' });
const submit = () => form.post(route('forum.answers.store', props.question.id), {
    preserveScroll: true,
    onSuccess: () => { form.reset(); showCode.value = false; },
});

// Ubah jawaban
const editingId = ref(null);
const editForm = useForm({ body: '', code: '' });
const startEdit = (a) => { editingId.value = a.id; editForm.body = a.body; editForm.code = a.code ?? ''; };
const saveEdit = () => editForm.put(route('forum.answers.update', editingId.value), { preserveScroll: true, onSuccess: () => (editingId.value = null) });

// Hapus
const confirming = ref(null);
const destroy = () => {
    const url = confirming.value === 'question'
        ? route('forum.questions.destroy', props.question.id)
        : route('forum.answers.destroy', confirming.value);
    router.delete(url, { preserveScroll: true, onFinish: () => (confirming.value = null) });
};

const statusTone = { unanswered: 'amber', answered: 'blue', resolved: 'green' };
const color = computed(() => classColors[props.question.class.color] ?? classColors.violet);
</script>

<template>
    <Head :title="question.title" />

    <Link :href="route('forum.questions.index')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="arrow-left" :size="15" /> Semua pertanyaan
    </Link>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <!-- Pertanyaan -->
            <article class="card overflow-hidden">
                <div :class="color.grad" class="h-1.5 bg-gradient-to-r" />
                <div class="p-5 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted">
                        <span class="inline-flex items-center gap-1.5 font-semibold"><span :class="color.dot" class="size-2 rounded-full" />{{ question.class.name }}</span>
                        <Link v-if="question.lesson" :href="route('learning.lessons.show', question.lesson.id)" class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline">
                            <Icon name="book-open" :size="12" /> {{ question.lesson.title }}
                        </Link>
                        <Badge :tone="statusTone[question.status]">{{ question.status_label }}</Badge>
                        <span v-if="question.visibility === 'private'" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 font-semibold text-brand-700"><Icon name="lock" :size="11" /> Hanya guru</span>
                        <span v-if="question.pinned" class="inline-flex items-center gap-1 font-bold text-amber-700"><Icon name="pin" :size="12" /> Disematkan</span>
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ question.title }}</h1>

                    <div class="mt-3 flex items-center gap-3 text-sm">
                        <Avatar :initials="question.author.initials" :seed="question.author.id" size="sm" />
                        <span class="font-semibold text-ink">{{ question.author.name }}</span>
                        <span v-if="question.author.anonymous && question.author.id" class="inline-flex items-center gap-1 text-xs text-muted"><Icon name="eye-off" :size="12" /> anonim untuk teman</span>
                        <span class="text-muted">· {{ question.created_at }}</span>
                    </div>

                    <div class="lesson-prose mt-5" v-html="question.body_html" />
                    <CodeSnippet v-if="question.code" :code="question.code" class="mt-5" />
                    <a v-if="question.image_url" :href="question.image_url" target="_blank" class="mt-5 block">
                        <img :src="question.image_url" alt="Gambar pertanyaan" class="max-h-[28rem] rounded-2xl border border-line" />
                    </a>

                    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                        <button
                            v-if="can.vote"
                            :class="question.voted ? 'bg-accent-500 text-white hover:bg-accent-600' : 'bg-accent-50 text-accent-700 hover:bg-accent-100'"
                            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
                            @click="voteQuestion"
                        >
                            <Icon name="hand" :size="16" /> Aku juga bingung
                            <span v-if="question.votes_count" class="rounded-full bg-white/30 px-1.5 text-xs">{{ question.votes_count }}</span>
                        </button>
                        <span v-else-if="question.votes_count" class="inline-flex items-center gap-1.5 text-sm text-muted"><Icon name="hand" :size="15" /> {{ question.votes_count }} teman juga bingung</span>

                        <div class="ml-auto flex flex-wrap gap-1">
                            <button v-if="can.resolve" class="btn-ghost py-1.5 text-sm" @click="toggleResolve">
                                <Icon :name="question.status === 'resolved' ? 'rotate-ccw' : 'circle-check'" :size="15" /> {{ question.status === 'resolved' ? 'Buka lagi' : 'Tandai terjawab' }}
                            </button>
                            <button v-if="can.pin" class="btn-ghost py-1.5 text-sm" @click="togglePin"><Icon name="pin" :size="15" /> {{ question.pinned ? 'Lepas sematan' : 'Sematkan' }}</button>
                            <Link v-if="can.update" :href="route('forum.questions.edit', question.id)" class="btn-ghost py-1.5 text-sm"><Icon name="pencil" :size="15" /> Ubah</Link>
                            <button v-if="can.delete" class="btn-ghost py-1.5 text-sm text-rose-600" @click="confirming = 'question'"><Icon name="trash" :size="15" /> Hapus</button>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Jawaban -->
            <div>
                <h2 class="mb-3 font-display text-lg font-bold text-ink">{{ answers.length ? `${answers.length} jawaban` : 'Belum ada jawaban' }}</h2>

                <div v-if="answers.length" class="space-y-4">
                    <div
                        v-for="a in answers"
                        :key="a.id"
                        :class="a.accepted ? 'border-emerald-300 ring-2 ring-emerald-100' : a.by_staff ? 'border-brand-200' : 'border-line'"
                        class="rounded-2xl border bg-white p-5"
                    >
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <Avatar :initials="a.author.initials" :seed="a.author.id" size="sm" />
                            <span class="font-semibold text-ink">{{ a.author.name }}</span>
                            <span v-if="a.by_staff" class="inline-flex items-center gap-1 rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white"><Icon name="badge-check" :size="12" /> Guru</span>
                            <span v-if="a.accepted" class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-bold text-white"><Icon name="trophy" :size="12" /> Jawaban terbaik</span>
                            <span class="text-xs text-muted">· {{ a.created_at }}<template v-if="a.edited"> · diubah</template></span>
                        </div>

                        <form v-if="editingId === a.id" class="mt-4 space-y-3" @submit.prevent="saveEdit">
                            <Field :error="editForm.errors.body"><textarea v-model="editForm.body" rows="4" class="field" /></Field>
                            <Field label="Kode" :error="editForm.errors.code"><textarea v-model="editForm.code" rows="5" spellcheck="false" class="field bg-[#241a35] font-mono text-sm text-[#f3eefb]" /></Field>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="btn-ghost" @click="editingId = null">Batal</button>
                                <button class="btn-primary" :disabled="editForm.processing">Simpan</button>
                            </div>
                        </form>
                        <template v-else>
                            <div class="lesson-prose mt-3 text-[15px]" v-html="a.body_html" />
                            <CodeSnippet v-if="a.code" :code="a.code" class="mt-4" />
                        </template>

                        <div class="mt-4 flex flex-wrap items-center gap-1 border-t border-line pt-3 text-sm">
                            <button
                                v-if="a.can.vote"
                                :class="a.voted ? 'bg-brand-600 text-white' : 'text-muted hover:bg-chip hover:text-ink'"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-semibold transition"
                                @click="voteAnswer(a)"
                            >
                                <Icon name="thumbs-up" :size="14" /> Membantu<span v-if="a.votes_count"> · {{ a.votes_count }}</span>
                            </button>
                            <span v-else-if="a.votes_count" class="inline-flex items-center gap-1.5 px-2 text-muted"><Icon name="thumbs-up" :size="14" /> {{ a.votes_count }} merasa terbantu</span>
                            <div class="ml-auto flex flex-wrap gap-1">
                                <button
                                    v-if="can.resolve"
                                    :class="a.accepted ? 'text-muted hover:text-ink' : 'text-emerald-700 hover:bg-emerald-50'"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-semibold"
                                    @click="accept(a)"
                                >
                                    <Icon :name="a.accepted ? 'x' : 'trophy'" :size="14" /> {{ a.accepted ? 'Batalkan terbaik' : 'Pilih jawaban terbaik' }}
                                </button>
                                <button v-if="a.can.update && editingId !== a.id" class="rounded-full px-3 py-1.5 text-muted hover:bg-chip hover:text-ink" @click="startEdit(a)"><Icon name="pencil" :size="14" /></button>
                                <button v-if="a.can.delete" class="rounded-full px-3 py-1.5 text-muted hover:bg-rose-50 hover:text-rose-600" @click="confirming = a.id"><Icon name="trash" :size="14" /></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center gap-4 rounded-2xl border border-dashed border-line bg-white p-5 text-sm text-muted">
                    <span class="text-3xl">🤔</span>
                    <p v-if="isStaff">Siswa menunggu jawabanmu. Jawaban guru ditandai khusus dan tampil paling atas.</p>
                    <p v-else>Guru sudah diberi tahu lewat daftar "Belum dijawab". Teman sekelas juga boleh membantu.</p>
                </div>
            </div>

            <!-- Form jawaban -->
            <form v-if="can.answer" class="card space-y-4 p-5 sm:p-6" @submit.prevent="submit">
                <p class="font-display font-bold text-ink">{{ isStaff ? 'Jawab sebagai guru' : 'Bantu jawab' }}</p>
                <Field :error="form.errors.body">
                    <textarea v-model="form.body" rows="4" maxlength="5000" class="field" :placeholder="isStaff ? 'Jelaskan langkah demi langkah…' : 'Menurutku…'" />
                </Field>
                <button v-if="!showCode" type="button" class="btn-ghost text-sm" @click="showCode = true"><Icon name="code" :size="16" /> Tambahkan kode</button>
                <Field v-else label="Kode" :error="form.errors.code">
                    <textarea v-model="form.code" rows="6" spellcheck="false" class="field bg-[#241a35] font-mono text-sm text-[#f3eefb]" />
                </Field>
                <div class="flex justify-end">
                    <button class="btn-primary" :disabled="form.processing || !form.body.trim()"><Icon name="send" :size="16" /> Kirim jawaban</button>
                </div>
            </form>
            <p v-else-if="role === 'guardian'" class="rounded-2xl bg-sky-soft p-4 text-sm text-ink">Orang tua dapat membaca pertanyaan dan jawaban anak. Untuk menjawab, hubungi guru kelas secara langsung.</p>
        </div>

        <!-- Samping -->
        <aside class="space-y-4">
            <div v-if="question.status === 'resolved'" class="rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-400 p-5 text-white">
                <p class="text-3xl">🎉</p>
                <p class="mt-2 font-display text-lg font-bold">Pertanyaan terjawab!</p>
                <p class="text-sm text-white/90">Jawaban terbaik ditandai hijau. Masih bingung? Penanya bisa membukanya lagi.</p>
            </div>
            <div class="card p-5">
                <p class="eyebrow">Kelas</p>
                <p class="mt-1 font-display font-bold text-ink">{{ question.class.name }}</p>
                <p v-if="question.teacher" class="mt-1 text-sm text-muted">Guru: {{ question.teacher }}</p>
                <Link :href="route('forum.questions.index', { class: question.class.id })" class="mt-3 inline-flex text-sm font-semibold text-brand-600 hover:underline">Pertanyaan lain di kelas ini →</Link>
            </div>
            <div v-if="question.mine && question.status !== 'resolved' && answers.length" class="card p-5 text-sm text-muted">
                <p class="font-semibold text-ink">Sudah paham?</p>
                <p class="mt-1">Pilih jawaban yang paling membantu. Teman yang punya pertanyaan sama jadi tahu jawabannya.</p>
            </div>
        </aside>
    </div>

    <ConfirmDialog
        :show="confirming !== null"
        :title="confirming === 'question' ? 'Hapus pertanyaan?' : 'Hapus jawaban?'"
        :message="confirming === 'question' ? 'Pertanyaan beserta semua jawabannya akan dihapus.' : 'Jawaban ini akan dihapus.'"
        confirm-text="Hapus"
        @close="confirming = null"
        @confirm="destroy"
    />
</template>
