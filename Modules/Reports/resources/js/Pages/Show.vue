<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';
import { rupiah } from '@/lib/format';

defineOptions({ layout: null });
const props = defineProps({ report: Object, editable: Array, prev: String, next: String });

const r = computed(() => props.report);
const page = usePage();
const today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

const attendanceTone = {
    present: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    excused: 'bg-sky-50 text-sky-800 ring-sky-200',
    sick: 'bg-amber-50 text-amber-900 ring-amber-200',
    absent: 'bg-rose-50 text-rose-800 ring-rose-200',
};

const isEmpty = computed(() => !r.value.sessions.length && !r.value.lessons.length && !r.value.quizzes.length && !r.value.assignments.length);

// Catatan guru per kelas (hanya untuk guru kelas dan admin).
const forms = ref(Object.fromEntries(props.editable.map((c) => [c.id, useForm({
    class_room_id: c.id,
    highlights: r.value.notes[c.id]?.highlights ?? '',
    focus: r.value.notes[c.id]?.focus ?? '',
})])));
const save = (id) => forms.value[id].put(route('reports.note', [r.value.student.id, r.value.month.key]), { preserveScroll: true });

const print = () => window.print();
const backUrl = computed(() => (page.props.auth?.user ? route('reports.index', { bulan: r.value.month.key }) : '/'));
</script>

<template>
    <Head :title="`Laporan ${report.student.name} · ${report.month.label}`" />

    <div class="min-h-screen bg-paper pb-16">
        <!-- Toolbar -->
        <div class="no-print sticky top-0 z-10 border-b border-line bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-[860px] flex-wrap items-center gap-2 px-4 py-3">
                <Link :href="backUrl" class="mr-auto inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Laporan bulanan</Link>
                <div class="flex items-center rounded-xl border border-line">
                    <Link :href="route('reports.show', [report.student.id, prev])" class="grid size-11 place-items-center text-muted hover:text-ink" aria-label="Bulan sebelumnya"><Icon name="chevron-left" :size="18" /></Link>
                    <span class="min-w-32 text-center text-sm font-bold text-ink">{{ report.month.label }}</span>
                    <Link v-if="next" :href="route('reports.show', [report.student.id, next])" class="grid size-11 place-items-center text-muted hover:text-ink" aria-label="Bulan berikutnya"><Icon name="chevron-right" :size="18" /></Link>
                    <span v-else class="grid size-11 place-items-center text-line"><Icon name="chevron-right" :size="18" /></span>
                </div>
                <button class="btn-primary" @click="print"><Icon name="printer" :size="16" /> Cetak / simpan PDF</button>
            </div>
        </div>

        <div class="mx-auto max-w-[860px] px-4 pt-6">
            <!-- Catatan guru (tidak ikut dicetak) -->
            <div v-for="c in editable" :key="c.id" class="no-print card mb-5 p-5">
                <div class="mb-3 flex flex-wrap items-baseline gap-x-2">
                    <h2 class="font-display font-bold text-ink">Catatan untuk orang tua · {{ c.name }}</h2>
                    <p class="text-sm text-muted">Muncul di laporan. Tulis singkat dan konkret.</p>
                </div>
                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="save(c.id)">
                    <label class="block">
                        <span class="label">Yang sudah bagus</span>
                        <textarea v-model="forms[c.id].highlights" rows="3" class="field" placeholder="Mis. sudah lancar memakai perulangan dan berani bertanya." />
                        <span v-if="forms[c.id].errors.highlights" class="text-xs text-rose-600">{{ forms[c.id].errors.highlights }}</span>
                    </label>
                    <label class="block">
                        <span class="label">Fokus bulan depan</span>
                        <textarea v-model="forms[c.id].focus" rows="3" class="field" placeholder="Mis. latihan membaca pesan error sebelum bertanya." />
                        <span v-if="forms[c.id].errors.focus" class="text-xs text-rose-600">{{ forms[c.id].errors.focus }}</span>
                    </label>
                    <div class="flex items-center gap-3 sm:col-span-2">
                        <button class="btn-primary" :disabled="forms[c.id].processing">Simpan catatan</button>
                        <span v-if="forms[c.id].recentlySuccessful" class="text-sm font-semibold text-emerald-700">Tersimpan</span>
                    </div>
                </form>
            </div>

            <!-- Dokumen laporan -->
            <article class="report-doc card px-6 py-8 sm:px-10">
                <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-brand-600 pb-5">
                    <img src="/images/logo-header.png" alt="Alprogmatics" class="h-10 w-auto" />
                    <div class="text-right">
                        <p class="font-display text-xl font-extrabold text-ink">Laporan Belajar Bulanan</p>
                        <p class="font-semibold text-brand-600">{{ report.month.label }}<span v-if="report.month.is_current" class="font-normal text-muted"> (bulan berjalan)</span></p>
                    </div>
                </header>

                <section class="mt-5 flex flex-wrap items-center gap-4">
                    <Avatar :initials="report.student.initials" :seed="report.student.id" />
                    <div class="mr-auto">
                        <p class="font-display text-lg font-bold text-ink">{{ report.student.name }}</p>
                        <p class="text-sm text-muted">
                            <span v-if="report.student.code">{{ report.student.code }}</span>
                            <span v-if="report.student.grade"> · Kelas {{ report.student.grade }}</span>
                            <span v-if="report.student.school"> · {{ report.student.school }}</span>
                        </p>
                    </div>
                    <ul class="text-sm text-muted sm:text-right">
                        <li v-for="c in report.classes" :key="c.id"><span class="font-semibold text-ink">{{ c.name }}</span><span v-if="c.teacher"> · {{ c.teacher }}</span></li>
                    </ul>
                </section>

                <!-- Ringkasan -->
                <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-brand-50 p-4">
                        <p class="text-xs font-semibold text-muted">Kehadiran</p>
                        <p class="font-display text-2xl font-extrabold text-brand-700">{{ report.attendance.rate ?? '–' }}<span v-if="report.attendance.rate !== null" class="text-base">%</span></p>
                        <p class="text-xs text-muted">{{ report.attendance.present }} dari {{ report.attendance.total }} sesi</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 p-4">
                        <p class="text-xs font-semibold text-muted">Materi selesai</p>
                        <p class="font-display text-2xl font-extrabold text-brand-700">{{ report.lessons.length }}</p>
                        <p class="text-xs text-muted">pelajaran bulan ini</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 p-4">
                        <p class="text-xs font-semibold text-muted">Rata-rata kuis</p>
                        <p class="font-display text-2xl font-extrabold text-brand-700">{{ report.quiz_avg ?? '–' }}</p>
                        <p class="text-xs text-muted">{{ report.quizzes.length }} kuis dikerjakan</p>
                    </div>
                    <div v-if="report.achievements" class="rounded-2xl bg-accent-50 p-4">
                        <p class="text-xs font-semibold text-muted">XP bulan ini</p>
                        <p class="font-display text-2xl font-extrabold text-accent-700">+{{ report.achievements.xp }}</p>
                        <p class="text-xs text-muted">Level {{ report.achievements.level.level }} · {{ report.achievements.level.name }}</p>
                    </div>
                    <div v-else class="rounded-2xl bg-accent-50 p-4">
                        <p class="text-xs font-semibold text-muted">Tugas dinilai</p>
                        <p class="font-display text-2xl font-extrabold text-accent-700">{{ report.assignments.length }}</p>
                    </div>
                </section>

                <p v-if="isEmpty" class="mt-6 rounded-2xl bg-chip p-5 text-center text-sm text-muted">Belum ada sesi selesai atau materi yang dikerjakan pada {{ report.month.label }}.</p>

                <!-- Catatan guru -->
                <section class="mt-8">
                    <h2 class="report-h">Catatan guru</h2>
                    <div v-if="Object.keys(report.notes).length" class="grid gap-3">
                        <template v-for="c in report.classes" :key="c.id">
                            <div v-if="report.notes[c.id]" class="rounded-2xl border border-line p-4">
                                <p class="text-sm font-bold text-ink">{{ c.name }} <span class="font-normal text-muted">· {{ report.notes[c.id].author }}</span></p>
                                <div class="mt-2 grid gap-3 text-sm sm:grid-cols-2">
                                    <div v-if="report.notes[c.id].highlights">
                                        <p class="font-semibold text-emerald-800">Yang sudah bagus</p>
                                        <p class="whitespace-pre-line text-ink">{{ report.notes[c.id].highlights }}</p>
                                    </div>
                                    <div v-if="report.notes[c.id].focus">
                                        <p class="font-semibold text-accent-700">Fokus bulan depan</p>
                                        <p class="whitespace-pre-line text-ink">{{ report.notes[c.id].focus }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p v-else class="text-sm text-muted">Catatan guru untuk bulan ini belum ditulis.</p>
                </section>

                <!-- Sesi -->
                <section v-if="report.sessions.length" class="mt-8">
                    <h2 class="report-h">Sesi bulan ini</h2>
                    <div class="mb-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full px-2.5 py-1 font-semibold ring-1" :class="attendanceTone.present">Hadir {{ report.attendance.present }}</span>
                        <span v-if="report.attendance.excused" class="rounded-full px-2.5 py-1 font-semibold ring-1" :class="attendanceTone.excused">Izin {{ report.attendance.excused }}</span>
                        <span v-if="report.attendance.sick" class="rounded-full px-2.5 py-1 font-semibold ring-1" :class="attendanceTone.sick">Sakit {{ report.attendance.sick }}</span>
                        <span v-if="report.attendance.absent" class="rounded-full px-2.5 py-1 font-semibold ring-1" :class="attendanceTone.absent">Alpa {{ report.attendance.absent }}</span>
                    </div>
                    <ol class="divide-y divide-line rounded-2xl border border-line">
                        <li v-for="(s, i) in report.sessions" :key="i" class="report-row grid gap-1 p-4 sm:grid-cols-[7rem_1fr_auto] sm:gap-4">
                            <div class="text-sm">
                                <p class="font-semibold text-ink">{{ s.date }}</p>
                                <p class="text-xs text-muted">{{ s.time }} · {{ s.class }}</p>
                            </div>
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold text-ink">{{ s.topic || 'Topik belum diisi' }}</p>
                                <p v-if="s.parent_note" class="mt-1 text-ink">{{ s.parent_note }}</p>
                                <p v-if="s.note" class="mt-1 text-muted">Catatan: {{ s.note }}</p>
                                <p v-if="s.homework" class="mt-1 text-muted"><span class="font-semibold">PR:</span> {{ s.homework }}</p>
                            </div>
                            <div><span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-bold ring-1" :class="attendanceTone[s.status]">{{ s.status_label }}</span></div>
                        </li>
                    </ol>
                </section>

                <!-- Materi & nilai -->
                <section v-if="report.lessons.length || report.quizzes.length || report.assignments.length" class="mt-8">
                    <h2 class="report-h">Materi dan nilai</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div v-if="report.lessons.length">
                            <p class="mb-2 text-sm font-bold text-ink">Pelajaran yang diselesaikan</p>
                            <ul class="space-y-1.5 text-sm">
                                <li v-for="(l, i) in report.lessons" :key="i" class="flex gap-2">
                                    <Icon name="circle-check" :size="16" class="mt-0.5 shrink-0 text-emerald-600" />
                                    <span><span class="text-ink">{{ l.title }}</span> <span class="text-xs text-muted">· {{ l.course }}, {{ l.date }}</span></span>
                                </li>
                            </ul>
                        </div>
                        <div class="space-y-5">
                            <div v-if="report.quizzes.length">
                                <p class="mb-2 text-sm font-bold text-ink">Kuis (nilai terbaik)</p>
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-line">
                                        <tr v-for="(q, i) in report.quizzes" :key="i">
                                            <td class="py-1.5 pr-2 text-ink">{{ q.title }}</td>
                                            <td class="py-1.5 text-right font-bold" :class="q.passed ? 'text-emerald-700' : 'text-amber-700'">{{ q.score }}</td>
                                            <td class="w-20 py-1.5 text-right text-xs text-muted">{{ q.tries }}× coba</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="report.assignments.length">
                                <p class="mb-2 text-sm font-bold text-ink">Tugas yang dinilai</p>
                                <ul class="space-y-2 text-sm">
                                    <li v-for="(a, i) in report.assignments" :key="i">
                                        <p class="flex justify-between gap-2"><span class="text-ink">{{ a.title }}</span><span class="font-bold text-ink">{{ a.score }}/{{ a.max }}</span></p>
                                        <p v-if="a.feedback" class="text-xs text-muted">"{{ a.feedback }}"</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Coding & proyek -->
                <section v-if="report.code?.length || report.projects?.length" class="mt-8">
                    <h2 class="report-h">Latihan coding dan proyek</h2>
                    <div class="grid gap-5 text-sm sm:grid-cols-2">
                        <div v-if="report.code?.length">
                            <p class="mb-2 font-bold text-ink">{{ report.code.length }} soal coding berhasil dipecahkan</p>
                            <ul class="list-inside list-disc space-y-1 text-ink marker:text-brand-400">
                                <li v-for="t in report.code" :key="t">{{ t }}</li>
                            </ul>
                        </div>
                        <div v-if="report.projects?.length">
                            <p class="mb-2 font-bold text-ink">{{ report.projects.length }} tugas proyek selesai</p>
                            <ul class="space-y-1 text-ink">
                                <li v-for="(p, i) in report.projects" :key="i">{{ p.title }} <span class="text-xs text-muted">· {{ p.project }}</span></li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Prestasi -->
                <section v-if="report.achievements?.badges.length || report.certificates.length" class="mt-8">
                    <h2 class="report-h">Prestasi bulan ini</h2>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="b in report.achievements?.badges ?? []" :key="b.name" class="flex items-center gap-3 rounded-2xl border border-line px-4 py-3">
                            <span class="text-2xl" aria-hidden="true">{{ b.emoji }}</span>
                            <div><p class="text-sm font-bold text-ink">{{ b.name }}</p><p class="text-xs text-muted">{{ b.how }}</p></div>
                        </div>
                        <div v-for="c in report.certificates" :key="c.number" class="flex items-center gap-3 rounded-2xl border border-accent-200 bg-accent-50 px-4 py-3">
                            <Icon name="award" :size="22" class="text-accent-700" />
                            <div><p class="text-sm font-bold text-ink">Sertifikat {{ c.title }}</p><p class="font-mono text-xs text-muted">{{ c.number }}</p></div>
                        </div>
                    </div>
                </section>

                <!-- Posisi saat ini -->
                <section class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div v-if="report.courses.length">
                        <h2 class="report-h">Progres kursus</h2>
                        <ul class="space-y-3 text-sm">
                            <li v-for="c in report.courses" :key="c.title">
                                <p class="flex justify-between"><span class="text-ink">{{ c.title }}</span><span class="font-semibold text-ink">{{ c.percent }}%</span></p>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full bg-brand-500" :style="{ width: `${c.percent}%` }" /></div>
                                <p class="text-xs text-muted">{{ c.completed }} dari {{ c.total }} pelajaran</p>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h2 class="report-h">Kuota dan tagihan</h2>
                        <ul class="space-y-2 text-sm">
                            <li v-for="p in report.account.packages" :key="p.name" class="flex justify-between gap-2">
                                <span class="text-ink">{{ p.name }}<span v-if="p.expires_at" class="text-xs text-muted"> · s/d {{ p.expires_at }}</span></span>
                                <span class="font-semibold" :class="p.remaining <= 2 ? 'text-amber-700' : 'text-ink'">sisa {{ p.remaining }} sesi</span>
                            </li>
                            <li v-if="!report.account.packages.length" class="text-muted">Tidak ada paket kuota aktif.</li>
                            <li class="flex justify-between gap-2 border-t border-line pt-2">
                                <span class="text-ink">Tagihan belum dibayar</span>
                                <span class="font-semibold" :class="report.account.outstanding ? 'text-accent-700' : 'text-emerald-700'">{{ report.account.outstanding ? rupiah(report.account.outstanding) : 'Lunas' }}</span>
                            </li>
                        </ul>
                        <p class="mt-2 text-xs text-muted">Posisi per {{ today }}.</p>
                    </div>
                </section>

                <footer class="mt-10 border-t border-line pt-4 text-xs text-muted">
                    Dibuat dari catatan kelas di LMS Alprogmatics pada {{ today }}. Ada pertanyaan tentang laporan ini? Hubungi guru kelas atau admin.
                </footer>
            </article>
        </div>
    </div>
</template>
