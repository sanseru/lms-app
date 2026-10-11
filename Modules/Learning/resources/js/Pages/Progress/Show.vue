<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { classColors } from '@/lib/format';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

defineProps({ student: Object, courses: Array, siblings: Array });

const gainLabel = (g) => (g === null ? null : g >= 0.7 ? 'Tinggi' : g >= 0.3 ? 'Sedang' : 'Rendah');
const scoreTone = (v) => (v === null || v === undefined ? 'text-muted' : v >= 80 ? 'text-emerald-600' : v >= 60 ? 'text-amber-600' : 'text-rose-600');
</script>

<template>
    <Head :title="`Progres ${student.name}`" />

    <div class="mb-6 flex flex-wrap items-center gap-4">
        <Avatar :initials="student.initials" :seed="student.id" size="lg" />
        <div class="flex-1">
            <p class="eyebrow">Rapor belajar</p>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ student.name }}</h1>
            <p v-if="student.code" class="font-mono text-sm text-muted">{{ student.code }}</p>
        </div>
        <div v-if="siblings.length > 1" class="flex flex-wrap gap-2">
            <Link
                v-for="s in siblings"
                :key="s.id"
                :href="route('learning.progress.show', s.id)"
                :class="s.id === student.id ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
                class="rounded-full px-4 py-2 text-sm font-semibold"
            >
                {{ s.name.split(' ')[0] }}
            </Link>
        </div>
    </div>

    <div v-if="courses.length" class="space-y-6">
        <section v-for="c in courses" :key="c.id" class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-5 border-b border-line p-5">
                <span :class="(classColors[c.color] ?? classColors.violet).grad" class="grid size-12 place-items-center rounded-2xl bg-gradient-to-br font-display font-black text-white">{{ c.subject.slice(0, 2) }}</span>
                <div class="min-w-48 flex-1">
                    <h2 class="font-display text-lg font-bold text-ink">{{ c.title }}</h2>
                    <div class="mt-2 flex items-center gap-3">
                        <ProgressBar :value="c.percent" class="max-w-xs" />
                        <span class="text-sm font-semibold text-ink">{{ c.completed }}/{{ c.total }}</span>
                    </div>
                </div>
                <dl class="grid grid-cols-3 gap-6 text-center">
                    <div><dt class="text-xs text-muted">Progres</dt><dd class="font-display text-2xl font-extrabold text-ink">{{ c.percent }}%</dd></div>
                    <div><dt class="text-xs text-muted">Rata-rata kuis</dt><dd :class="scoreTone(c.quiz_avg)" class="font-display text-2xl font-extrabold">{{ c.quiz_avg ?? '–' }}</dd></div>
                    <div><dt class="text-xs text-muted">Rata-rata tugas</dt><dd :class="scoreTone(c.assignment_avg)" class="font-display text-2xl font-extrabold">{{ c.assignment_avg ?? '–' }}</dd></div>
                </dl>
            </div>
            <div v-if="c.missions" class="border-b border-line bg-gradient-to-br from-brand-50 to-accent-50/60 p-5">
                <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                    <p class="flex items-center gap-2 font-display font-bold text-ink"><Icon name="rocket" :size="18" class="text-accent-600" /> Kenaikan kemampuan</p>
                    <div class="text-center"><p class="text-xs text-muted">Misi Pemanasan</p><p class="font-display text-xl font-extrabold text-ink">{{ c.missions.pre ?? '–' }}</p></div>
                    <Icon name="arrow-right" :size="18" class="text-muted" />
                    <div class="text-center"><p class="text-xs text-muted">Misi Akhir</p><p class="font-display text-xl font-extrabold text-ink">{{ c.missions.post ?? '–' }}</p></div>
                    <div v-if="c.missions.gain !== null" class="rounded-2xl bg-white px-4 py-2 text-center shadow-sm">
                        <p class="text-xs text-muted">Kenaikan (gain)</p>
                        <p class="font-display text-xl font-extrabold text-brand-600">{{ Math.round(c.missions.gain * 100) }}% <span class="text-xs font-semibold text-muted">{{ gainLabel(c.missions.gain) }}</span></p>
                    </div>
                    <p v-else class="text-sm text-muted">{{ c.missions.pre === null ? 'Misi Pemanasan belum dikerjakan.' : 'Misi Akhir belum dikerjakan.' }}</p>
                </div>
                <ul v-if="c.missions.concepts.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="k in c.missions.concepts" :key="k.concept" class="rounded-xl bg-white/80 p-3">
                        <p class="text-sm font-semibold text-ink capitalize">{{ k.concept }}</p>
                        <div class="mt-1.5 space-y-1 text-xs text-muted">
                            <div class="flex items-center gap-2"><span class="w-10">Awal</span><div class="h-1.5 flex-1 rounded-full bg-chip"><div class="h-full rounded-full bg-slate-400" :style="{ width: `${k.pre ?? 0}%` }" /></div><span class="w-9 text-right">{{ k.pre ?? '–' }}%</span></div>
                            <div class="flex items-center gap-2"><span class="w-10">Akhir</span><div class="h-1.5 flex-1 rounded-full bg-chip"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" :style="{ width: `${k.post ?? 0}%` }" /></div><span class="w-9 text-right">{{ k.post ?? '–' }}%</span></div>
                        </div>
                    </li>
                </ul>
                <p class="mt-3 text-xs text-muted">Kenaikan dihitung dari sisa ruang naik: (akhir − awal) ÷ (100 − awal). Misi Pemanasan tidak memengaruhi nilai.</p>
            </div>
            <ol class="divide-y divide-line">
                <li v-for="l in c.lessons" :key="l.id" class="flex items-center gap-3 px-5 py-3">
                    <LessonTypeIcon :type="l.type" :done="l.done" size="sm" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink">{{ l.title }}</span>
                        <span class="text-xs text-muted">{{ l.type_label }}</span>
                    </span>
                    <span v-if="l.type === 'quiz' || l.type === 'code'" class="text-right text-sm">
                        <span v-if="l.quiz_best !== null" :class="scoreTone(l.quiz_best)" class="font-bold">{{ l.quiz_best }}</span>
                        <span v-else class="text-muted">Belum dikerjakan</span>
                        <span v-if="l.quiz_tries" class="block text-xs text-muted">{{ l.quiz_tries }}× mencoba</span>
                    </span>
                    <span v-else-if="l.type === 'assignment'" class="text-right text-sm">
                        <span v-if="l.submission_score !== null" :class="scoreTone((l.submission_score / l.max_score) * 100)" class="font-bold">{{ l.submission_score }}/{{ l.max_score }}</span>
                        <span v-else :class="l.submission_status === 'revision' ? 'text-accent-600' : 'text-muted'">{{ l.submission_label ?? 'Belum dikumpulkan' }}</span>
                    </span>
                    <span v-else class="text-sm" :class="l.done ? 'font-semibold text-emerald-600' : 'text-muted'">{{ l.done ? 'Selesai' : 'Belum' }}</span>
                </li>
            </ol>
        </section>
    </div>
    <div v-else class="card"><EmptyState icon="book-open" title="Belum ada kursus" description="Kursus muncul setelah guru memasangnya di kelas yang diikuti." /></div>
</template>
