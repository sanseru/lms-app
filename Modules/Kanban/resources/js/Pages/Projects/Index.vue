<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ mode: String, projects: Array });

const active = computed(() => props.projects.filter((p) => p.status === 'active'));
const archived = computed(() => props.projects.filter((p) => p.status !== 'active'));
const titles = { staff: 'Proyek kelas', student: 'Proyekku', guardian: 'Proyek anak' };
const descriptions = {
    staff: 'Buat proyek untuk kelas, lalu pantau papan kanban setiap siswa dan cek kartu yang mereka kirim.',
    student: 'Kerjakan proyekmu langkah demi langkah. Geser kartu dari kiri ke kanan sampai semuanya selesai!',
    guardian: 'Lihat sejauh mana anak Anda mengerjakan proyek dari guru.',
};
const color = (p) => classColors[p.class.color] ?? classColors.violet;
const href = (p) => (props.mode === 'staff' ? route('kanban.projects.show', p.id) : route('kanban.boards.show', p.board.id));
</script>

<template>
    <Head :title="titles[mode]" />

    <PageHeader eyebrow="Belajar" :title="titles[mode]" :description="descriptions[mode]">
        <template v-if="mode === 'staff'" #actions>
            <Link :href="route('kanban.projects.create')" class="btn-primary"><Icon name="plus" :size="18" /> Proyek baru</Link>
        </template>
    </PageHeader>

    <div v-if="projects.length" class="space-y-8">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="p in active"
                :key="`${p.id}-${p.board?.id ?? ''}`"
                :href="href(p)"
                class="group flex flex-col overflow-hidden rounded-3xl border border-line bg-white transition hover:-translate-y-1 hover:shadow-lg"
            >
                <div :class="color(p).grad" class="relative px-5 pt-5 pb-10 text-white">
                    <span class="absolute -right-2 -bottom-4 text-7xl opacity-90 drop-shadow-sm transition group-hover:scale-110 group-hover:-rotate-6">{{ p.emoji }}</span>
                    <p class="text-xs font-semibold text-white">{{ p.class.name }}</p>
                    <p class="mt-1 pr-16 font-display text-xl leading-tight font-extrabold">{{ p.title }}</p>
                </div>
                <div class="flex flex-1 flex-col gap-4 p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span v-if="p.due_date" :class="p.is_overdue ? 'bg-rose-50 text-rose-700' : 'bg-chip text-muted'" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-semibold">
                            <Icon name="calendar-days" :size="12" /> {{ p.is_overdue ? 'Lewat tenggat' : `Tenggat ${p.due_date}` }}
                        </span>
                        <span v-if="mode === 'staff' && p.to_review" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-800">
                            <Icon name="eye" :size="12" /> {{ p.to_review }} perlu dicek
                        </span>
                        <span v-if="p.board?.completed" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700"><Icon name="trophy" :size="12" /> Selesai</span>
                    </div>

                    <template v-if="mode === 'staff'">
                        <div class="mt-auto">
                            <div class="mb-1.5 flex justify-between text-sm">
                                <span class="text-muted">Rata-rata progres</span>
                                <span class="font-bold text-ink">{{ p.percent }}%</span>
                            </div>
                            <ProgressBar :value="p.percent" />
                            <p class="mt-2 text-xs text-muted">{{ p.completed }} dari {{ p.students }} siswa selesai</p>
                        </div>
                    </template>
                    <template v-else>
                        <div v-if="mode === 'guardian'" class="flex items-center gap-2 text-sm">
                            <Avatar :initials="p.board.student.initials" :seed="p.board.student.id" size="sm" />
                            <span class="font-semibold text-ink">{{ p.board.student.name }}</span>
                        </div>
                        <div class="mt-auto">
                            <div class="mb-1.5 flex justify-between text-sm">
                                <span class="text-muted">{{ p.board.progress.done }} dari {{ p.board.progress.total }} langkah</span>
                                <span class="font-bold text-ink">{{ p.board.progress.percent }}%</span>
                            </div>
                            <ProgressBar :value="p.board.progress.percent" :tone="p.board.completed ? 'green' : 'brand'" />
                            <div class="mt-3 grid grid-cols-4 gap-1 text-center text-xs text-muted">
                                <span v-for="(n, key) in p.board.progress.counts" :key="key" class="rounded-lg bg-chip/70 py-1">
                                    <b class="block text-sm text-ink">{{ n }}</b>{{ { todo: 'Mau', doing: 'Sedang', review: 'Dicek', done: 'Selesai' }[key] }}
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </Link>
        </div>

        <div v-if="archived.length">
            <h2 class="mb-3 font-display font-bold text-muted">Diarsipkan</h2>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Link v-for="p in archived" :key="`${p.id}-${p.board?.id ?? ''}`" :href="href(p)" class="flex items-center gap-3 rounded-2xl border border-line bg-white/60 p-4 hover:bg-white">
                    <span class="text-2xl grayscale">{{ p.emoji }}</span>
                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink">{{ p.title }}</span><span class="text-xs text-muted">{{ p.class.name }}</span></span>
                </Link>
            </div>
        </div>
    </div>

    <div v-else class="card">
        <EmptyState
            icon="kanban"
            :title="mode === 'staff' ? 'Belum ada proyek' : 'Belum ada proyek dari guru'"
            :description="mode === 'staff' ? 'Proyek membantu siswa membuat karya sendiri, misalnya game atau website, langkah demi langkah.' : 'Proyek dari guru akan muncul di sini.'"
        />
        <div v-if="mode === 'staff'" class="pb-8 text-center"><Link :href="route('kanban.projects.create')" class="btn-primary"><Icon name="plus" :size="18" /> Buat proyek pertama</Link></div>
    </div>
</template>
