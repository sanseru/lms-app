<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ session: Object, students: Array, statuses: Object, can: Object });

const color = computed(() => classColors[props.session.class?.color] ?? classColors.violet);
const completed = computed(() => props.session.status === 'completed');

const form = useForm({
    topic: props.session.topic ?? '',
    homework: props.session.homework ?? '',
    parent_note: props.session.parent_note ?? '',
    internal_note: props.session.internal_note ?? '',
    flagged: props.session.flagged ?? false,
    recording_url: props.session.recording_url ?? '',
    attendances: props.students.map((s) => ({ student_id: s.id, status: s.status ?? 'present', note: s.note ?? '' })),
});

const studentById = Object.fromEntries(props.students.map((s) => [s.id, s]));
const setAll = (status) => form.attendances.forEach((a) => (a.status = status));
const summary = computed(() =>
    Object.keys(props.statuses).map((k) => [props.statuses[k], form.attendances.filter((a) => a.status === k).length]).filter(([, n]) => n),
);

const pill = {
    present: 'bg-emerald-500 text-white ring-emerald-500',
    excused: 'bg-sky-500 text-white ring-sky-500',
    sick: 'bg-amber-500 text-white ring-amber-500',
    absent: 'bg-rose-500 text-white ring-rose-500',
};
const tone = { present: 'active', excused: 'upcoming', sick: 'unpaid', absent: 'overdue' };

const submit = () => form.put(route('sessions.update', props.session.id), { preserveScroll: true });

const cancelling = ref(false);
const cancelForm = useForm({ reason: '' });
const cancel = () =>
    cancelForm.post(route('sessions.cancel', props.session.id), { preserveScroll: true, onSuccess: () => (cancelling.value = false) });

const rescheduling = ref(false);
const rescheduleForm = useForm({ date: '', start_time: props.session.start_time, end_time: props.session.end_time, reason: '' });
const reschedule = () => rescheduleForm.post(route('sessions.reschedule', props.session.id), { onSuccess: () => (rescheduling.value = false) });
const today = new Date().toLocaleDateString('sv-SE');
</script>

<template>
    <Head :title="`Sesi ${session.class?.name}`" />

    <Link :href="can.staff ? route('sessions.index') : route('dashboard')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> {{ can.staff ? 'Semua sesi' : 'Beranda' }}
    </Link>

    <!-- Kepala sesi -->
    <div :class="color.grad" class="relative overflow-hidden rounded-3xl p-6 text-white sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-white">{{ session.date_label }} · {{ session.start_time }}–{{ session.end_time }} WIB</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ session.class?.name }}</h1>
                <p class="mt-1 text-white">
                    <template v-if="session.teacher">Guru: {{ session.teacher.name }}</template>
                    <template v-if="session.topic"> · {{ session.topic }}</template>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-white px-1 py-0.5"><StatusBadge :status="completed ? 'paid' : ['cancelled', 'rescheduled'].includes(session.status) ? 'cancelled' : 'upcoming'" :label="session.status_label" /></span>
                <a v-if="session.zoom_url && session.status === 'scheduled'" :href="session.zoom_url" target="_blank" rel="noopener" class="btn bg-white text-ink hover:bg-white/90">
                    <Icon name="video" :size="18" /> Masuk Zoom
                </a>
                <a v-if="session.recording_url" :href="session.recording_url" target="_blank" rel="noopener" class="btn bg-white/20 text-white hover:bg-white/30">
                    <Icon name="video" :size="16" /> Rekaman
                </a>
                <button v-if="can.reschedule" class="btn bg-white/20 text-white hover:bg-white/30" @click="rescheduling = true">
                    <Icon name="calendar-days" :size="16" /> {{ session.status === 'cancelled' ? 'Buat sesi pengganti' : 'Pindah jadwal' }}
                </button>
                <button v-if="can.cancel" class="btn bg-white/20 text-white hover:bg-white/30" @click="cancelling = true">Batalkan</button>
            </div>
        </div>
    </div>

    <p v-if="session.status === 'cancelled'" class="mt-4 rounded-xl bg-chip px-4 py-3 text-sm text-ink">Sesi dibatalkan: {{ session.cancel_reason }}. Kuota siswa tidak terpotong.</p>
    <p v-if="session.status === 'rescheduled'" class="mt-4 rounded-xl bg-chip px-4 py-3 text-sm text-ink">Sesi dipindah: {{ session.cancel_reason }}. Kuota siswa tidak terpotong.</p>
    <Link
        v-if="session.replacement"
        :href="route('sessions.show', session.replacement.id)"
        class="mt-3 flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700 hover:bg-brand-100"
    >
        <Icon name="calendar-days" :size="18" />
        <span class="flex-1"><b>Sesi pengganti:</b> {{ session.replacement.label }} WIB ({{ session.replacement.status_label }})</span>
        <Icon name="arrow-right" :size="16" />
    </Link>
    <Link
        v-if="session.replaces"
        :href="route('sessions.show', session.replaces.id)"
        class="mt-4 flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 text-sm text-ink hover:bg-chip/60"
    >
        <Icon name="calendar-days" :size="18" class="text-muted" />
        <span class="flex-1"><b>Pengganti untuk sesi</b> {{ session.replaces.label }} WIB ({{ session.replaces.status_label }})</span>
        <Icon name="arrow-right" :size="16" class="text-muted" />
    </Link>

    <!-- Form satu layar: absensi + catatan -->
    <form v-if="can.report" class="mt-6 grid gap-6 lg:grid-cols-5" @submit.prevent="submit">
        <div class="card overflow-hidden lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 class="font-display font-bold text-ink">Absensi</h2>
                    <p class="text-sm text-muted">{{ summary.map(([l, n]) => `${n} ${l.toLowerCase()}`).join(' · ') || 'Belum ada siswa' }}</p>
                </div>
                <button v-if="students.length" type="button" class="text-sm font-semibold text-brand-600 hover:underline" @click="setAll('present')">Tandai semua hadir</button>
            </div>
            <ul class="divide-y divide-line">
                <li v-for="(row, i) in form.attendances" :key="row.student_id" class="px-5 py-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <Avatar :initials="studentById[row.student_id].initials" :seed="row.student_id" size="sm" />
                        <p class="min-w-32 flex-1 font-semibold text-ink">{{ studentById[row.student_id].name }}</p>
                        <div class="flex gap-1" role="radiogroup" :aria-label="`Kehadiran ${studentById[row.student_id].name}`">
                            <button
                                v-for="(label, key) in statuses"
                                :key="key"
                                type="button"
                                role="radio"
                                :aria-checked="row.status === key"
                                :class="row.status === key ? pill[key] : 'bg-white text-muted ring-line hover:text-ink'"
                                class="rounded-lg px-3 py-1.5 text-xs font-bold ring-1 transition ring-inset"
                                @click="row.status = key"
                            >
                                {{ label }}
                            </button>
                        </div>
                    </div>
                    <input
                        v-if="row.status !== 'present' || row.note"
                        v-model="row.note"
                        type="text"
                        class="field mt-2 py-1.5 text-xs"
                        placeholder="Keterangan (opsional)"
                    />
                    <p v-if="form.errors[`attendances.${i}.status`]" class="mt-1 text-xs text-rose-600">{{ form.errors[`attendances.${i}.status`] }}</p>
                </li>
                <li v-if="!students.length" class="px-5 py-8 text-center text-sm text-muted">Kelas ini belum memiliki siswa.</li>
            </ul>
            <p class="border-t border-line bg-chip/50 px-5 py-3 text-xs text-muted">Hadir dan alpa memotong kuota paket. Izin dan sakit tidak memotong kuota.</p>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-4 p-5">
                <h2 class="font-display font-bold text-ink">Catatan sesi</h2>
                <Field id="topic" label="Materi yang dibahas" :error="form.errors.topic" required>
                    <input id="topic" v-model="form.topic" type="text" class="field" placeholder="mis. Perulangan for dan while" />
                </Field>
                <Field id="homework" label="PR / tugas" :error="form.errors.homework">
                    <textarea id="homework" v-model="form.homework" rows="2" class="field" />
                </Field>
                <Field id="parent_note" label="Catatan untuk orang tua" :error="form.errors.parent_note" hint="Terlihat oleh orang tua di portal.">
                    <textarea id="parent_note" v-model="form.parent_note" rows="3" class="field" />
                </Field>
                <Field id="internal_note" label="Catatan internal" :error="form.errors.internal_note" hint="Hanya admin dan guru.">
                    <textarea id="internal_note" v-model="form.internal_note" rows="2" class="field" />
                </Field>
                <Field id="recording_url" label="Link rekaman Zoom" :error="form.errors.recording_url">
                    <input id="recording_url" v-model="form.recording_url" type="url" class="field" />
                </Field>
                <label class="flex items-start gap-3 rounded-xl border border-line p-3" :class="form.flagged && 'border-rose-200 bg-rose-50'">
                    <input v-model="form.flagged" type="checkbox" class="mt-0.5 rounded border-line text-rose-600 focus:ring-rose-300" />
                    <span>
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-ink"><Icon name="flag" :size="14" /> Tandai untuk admin</span>
                        <span class="text-xs text-muted">Ada kendala (siswa kesulitan, masalah teknis, dll.) yang perlu ditindaklanjuti.</span>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn-primary w-full py-3" :disabled="form.processing">
                <Icon name="check" :size="18" /> {{ completed ? 'Simpan perubahan' : 'Simpan & selesaikan sesi' }}
            </button>
            <p v-if="completed" class="text-center text-xs text-muted">Diselesaikan {{ session.completed_at }}</p>
        </div>
    </form>

    <!-- Tampilan baca (siswa / orang tua / admin non-pengajar pada sesi batal) -->
    <div v-else class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card space-y-4 p-5 lg:col-span-2">
            <h2 class="font-display font-bold text-ink">Catatan sesi</h2>
            <p v-if="!completed" class="text-sm text-muted">Catatan akan muncul setelah guru menyelesaikan sesi.</p>
            <template v-else>
                <div><p class="text-sm text-muted">Materi</p><p class="font-semibold text-ink">{{ session.topic }}</p></div>
                <div v-if="session.homework" class="rounded-xl bg-amber-50 p-4"><p class="text-sm font-semibold text-amber-800">PR</p><p class="text-ink">{{ session.homework }}</p></div>
                <div v-if="session.parent_note" class="rounded-xl bg-sky-soft p-4"><p class="text-sm font-semibold text-sky-ink">Catatan guru</p><p class="text-ink">{{ session.parent_note }}</p></div>
            </template>
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-display font-bold text-ink">Kehadiran</h2>
            <ul class="space-y-2">
                <li v-for="s in students" :key="s.id" class="flex items-center justify-between gap-2">
                    <span class="text-sm text-ink">{{ s.name }}</span>
                    <StatusBadge v-if="s.status" :status="tone[s.status]" :label="statuses[s.status]" />
                    <span v-else class="text-xs text-muted">–</span>
                </li>
            </ul>
        </div>
    </div>

    <Modal :show="rescheduling" max-width="md" @close="rescheduling = false">
        <form class="p-6" @submit.prevent="reschedule">
            <h3 class="font-display text-lg font-bold text-ink">{{ session.status === 'cancelled' ? 'Buat sesi pengganti' : 'Pindah jadwal sesi' }}</h3>
            <p class="mb-4 text-sm text-muted">
                Sesi baru tertaut ke sesi ini, memakai guru dan link Zoom yang sama. Jadwal guru dicek agar tidak bentrok.
            </p>
            <div class="grid gap-4 sm:grid-cols-3">
                <Field id="rs_date" label="Tanggal" :error="rescheduleForm.errors.date" required class="sm:col-span-3">
                    <input id="rs_date" v-model="rescheduleForm.date" type="date" :min="today" class="field" />
                </Field>
                <Field id="rs_start" label="Mulai" :error="rescheduleForm.errors.start_time" required class="sm:col-span-1">
                    <input id="rs_start" v-model="rescheduleForm.start_time" type="time" class="field" />
                </Field>
                <Field id="rs_end" label="Selesai" :error="rescheduleForm.errors.end_time" required class="sm:col-span-2">
                    <input id="rs_end" v-model="rescheduleForm.end_time" type="time" class="field" />
                </Field>
                <Field id="rs_reason" label="Alasan" :error="rescheduleForm.errors.reason" required class="sm:col-span-3">
                    <input id="rs_reason" v-model="rescheduleForm.reason" type="text" class="field" placeholder="mis. Siswa ujian sekolah" />
                </Field>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="rescheduling = false">Kembali</button>
                <button type="submit" class="btn-primary" :disabled="rescheduleForm.processing">Simpan jadwal baru</button>
            </div>
        </form>
    </Modal>

    <Modal :show="cancelling" max-width="md" @close="cancelling = false">
        <form class="p-6" @submit.prevent="cancel">
            <h3 class="font-display text-lg font-bold text-ink">Batalkan sesi?</h3>
            <p class="mb-4 text-sm text-muted">Kuota siswa tidak terpotong dan honor tidak dihitung.</p>
            <Field id="reason" label="Alasan" :error="cancelForm.errors.reason" required>
                <input id="reason" v-model="cancelForm.reason" type="text" class="field" placeholder="mis. Libur nasional" />
            </Field>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="cancelling = false">Kembali</button>
                <button type="submit" class="btn-danger" :disabled="cancelForm.processing">Batalkan sesi</button>
            </div>
        </form>
    </Modal>
</template>
