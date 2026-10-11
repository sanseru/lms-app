<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { classColors } from '@/lib/format';
import ReviewActions from '../../Components/ReviewActions.vue';

const props = defineProps({ project: Object, boards: Array, reviews: Array, columns: Object });
const color = classColors[props.project.class.color] ?? classColors.violet;
const confirming = ref(false);
const destroy = () => router.delete(route('kanban.projects.destroy', props.project.id));
const columnDot = { todo: 'bg-slate-300', doing: 'bg-sky-400', review: 'bg-amber-400', done: 'bg-emerald-500' };
</script>

<template>
    <Head :title="project.title" />

    <Link :href="route('kanban.projects.index')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Semua proyek</Link>

    <div :class="color.grad" class="relative mb-6 overflow-hidden rounded-3xl p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -right-3 -bottom-8 text-[9rem] leading-none opacity-90 select-none">{{ project.emoji }}</span>
        <p class="text-sm font-semibold text-white">{{ project.class.name }}<template v-if="project.due_date"> · tenggat {{ project.due_date }}</template></p>
        <h1 class="mt-1 max-w-2xl pr-24 text-3xl font-extrabold tracking-tight">{{ project.title }}</h1>
        <p v-if="project.description" class="mt-2 max-w-2xl pr-24 text-white whitespace-pre-line">{{ project.description }}</p>
        <div class="relative mt-5 flex flex-wrap gap-2">
            <Link :href="route('kanban.projects.edit', project.id)" class="btn bg-white/90 text-ink hover:bg-white"><Icon name="pencil" :size="16" /> Ubah</Link>
            <button class="btn bg-white/20 text-white hover:bg-white/30" @click="confirming = true"><Icon name="trash" :size="16" /> Hapus</button>
            <span v-if="project.status === 'archived'" class="rounded-full bg-black/20 px-3 py-2 text-sm font-semibold">Diarsipkan</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <h2 class="font-display text-lg font-bold text-ink">Papan siswa</h2>
            <div v-if="boards.length" class="grid gap-3 sm:grid-cols-2">
                <Link
                    v-for="b in boards"
                    :key="b.id"
                    :href="route('kanban.boards.show', b.id)"
                    :class="b.completed ? 'border-emerald-200 bg-emerald-50/50' : 'border-line bg-white'"
                    class="group rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-center gap-3">
                        <Avatar :initials="b.student.initials" :seed="b.student.id" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink group-hover:text-brand-700">{{ b.student.name }}</p>
                            <p class="text-xs text-muted">diperbarui {{ b.updated_at }}</p>
                        </div>
                        <Icon v-if="b.completed" name="trophy" class="text-emerald-500" />
                        <span v-else class="font-display text-lg font-black text-ink">{{ b.progress.percent }}%</span>
                    </div>
                    <ProgressBar :value="b.progress.percent" size="sm" :tone="b.completed ? 'green' : 'brand'" class="mt-3" />
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted">
                        <span v-for="(label, key) in columns" :key="key" class="inline-flex items-center gap-1" :title="label">
                            <span :class="columnDot[key]" class="size-2 rounded-full" />{{ b.progress.counts[key] }}
                        </span>
                        <span v-if="b.result_url" class="ml-auto inline-flex items-center gap-1 font-semibold text-brand-600"><Icon name="link" :size="12" /> hasil</span>
                    </div>
                </Link>
            </div>
            <div v-else class="card"><EmptyState icon="users" title="Belum ada siswa di kelas ini" /></div>

            <div class="card p-5">
                <p class="mb-3 font-display font-bold text-ink">Langkah bawaan</p>
                <ol class="space-y-2 text-sm">
                    <li v-for="(t, i) in project.tasks" :key="t.id" class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-brand-50 text-xs font-bold text-brand-700">{{ i + 1 }}</span>
                        <span><span class="font-semibold text-ink">{{ t.title }}</span><span v-if="t.description" class="block text-xs text-muted">{{ t.description }}</span></span>
                    </li>
                    <li v-if="!project.tasks.length" class="text-muted">Belum ada langkah bawaan. Siswa menyusun kartunya sendiri.</li>
                </ol>
            </div>
        </div>

        <aside>
            <div class="card overflow-hidden lg:sticky lg:top-24">
                <div class="flex items-center gap-2 border-b border-line px-5 py-4">
                    <Icon name="eye" class="text-amber-500" />
                    <p class="flex-1 font-display font-bold text-ink">Perlu dicek</p>
                    <span class="rounded-full bg-amber-100 px-2 text-xs font-bold text-amber-800">{{ reviews.length }}</span>
                </div>
                <ul v-if="reviews.length" class="max-h-[70vh] divide-y divide-line overflow-y-auto">
                    <li v-for="c in reviews" :key="c.id" class="space-y-2 px-5 py-4">
                        <div>
                            <Link :href="route('kanban.boards.show', c.board_id)" class="text-xs font-semibold text-brand-600 hover:underline">{{ c.student }}</Link>
                            <p class="font-semibold text-ink">{{ c.title }}</p>
                            <p class="text-xs text-muted">dikirim {{ c.updated_at }}</p>
                        </div>
                        <ReviewActions :card="c" />
                    </li>
                </ul>
                <p v-else class="px-5 py-8 text-center text-sm text-muted">{{ project.requires_review ? 'Tidak ada kartu yang menunggu. 👍' : 'Proyek ini tidak memakai pengecekan guru.' }}</p>
            </div>
        </aside>
    </div>

    <ConfirmDialog :show="confirming" title="Hapus proyek?" message="Semua papan dan kartu siswa untuk proyek ini ikut terhapus." confirm-text="Hapus" @close="confirming = false" @confirm="destroy" />
</template>
