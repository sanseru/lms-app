<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

const props = defineProps({
    lead: Object, statuses: Object, interests: Array, sources: Object, teachers: Array, classes: Array, existingGuardian: Object, suggested: Object,
});

const open = computed(() => ['new', 'contacted', 'trial_scheduled', 'trial_done'].includes(props.lead.status));
const steps = ['new', 'contacted', 'trial_scheduled', 'trial_done', 'won'];
const stepIndex = computed(() => steps.indexOf(props.lead.status));

const waText = computed(() => {
    const l = props.lead;
    if (l.status === 'trial_scheduled' && l.trial_at_label) {
        return `Halo ${l.parent_name}, mengingatkan kelas coba gratis ${l.child_name} ${l.trial_at_label} WIB bersama ${l.trial_teacher}.${l.trial_zoom_url ? ` Tautan Zoom: ${l.trial_zoom_url}` : ''} Sampai jumpa!`;
    }
    return `Halo ${l.parent_name}, terima kasih sudah mendaftarkan ${l.child_name} untuk kelas coba gratis Alprogmatics. Kapan waktu yang paling cocok untuk trial 45 menit via Zoom?`;
});
const waUrl = computed(() => `https://wa.me/${props.lead.wa}?text=${encodeURIComponent(waText.value)}`);

const post = (name, data = {}) => router.post(route(name, props.lead.id), data, { preserveScroll: true });

const note = useForm({ body: '' });
const addNote = () => note.post(route('leads.note', props.lead.id), { preserveScroll: true, onSuccess: () => note.reset() });

const scheduling = ref(false);
const trial = useForm({ trial_at: props.lead.trial_at ?? '', trial_teacher_id: props.lead.trial_teacher_id ?? '', trial_zoom_url: props.lead.trial_zoom_url ?? '' });
const schedule = () => trial.post(route('leads.schedule', props.lead.id), { preserveScroll: true, onSuccess: () => (scheduling.value = false) });

const recording = ref(false);
const result = useForm({ trial_result: props.lead.trial_result ?? '', trial_interest: props.lead.trial_interest ?? 4, recommended_class_id: props.lead.recommended_class_id ?? '' });
const saveResult = () => result.post(route('leads.result', props.lead.id), { preserveScroll: true, onSuccess: () => (recording.value = false) });

const losing = ref(false);
const lost = useForm({ lost_reason: '' });
const lose = () => lost.post(route('leads.lose', props.lead.id), { preserveScroll: true, onSuccess: () => (losing.value = false) });

const converting = ref(false);
const convert = useForm({
    student_username: props.suggested.student, guardian_username: props.existingGuardian ? '' : props.suggested.guardian,
    password: '', relationship: 'ibu', class_room_id: props.lead.recommended_class_id ?? '',
});
const doConvert = () => convert.post(route('leads.convert', props.lead.id), { preserveScroll: true, onSuccess: () => (converting.value = false) });

const editing = ref(false);
const edit = useForm({ ...Object.fromEntries(['parent_name', 'phone', 'email', 'child_name', 'child_age', 'child_grade', 'interest', 'preferred_time', 'source', 'message'].map((k) => [k, props.lead[k] ?? ''])) });
const saveEdit = () => edit.put(route('leads.update', props.lead.id), { preserveScroll: true, onSuccess: () => (editing.value = false) });
</script>

<template>
    <Head :title="`${lead.child_name} · Calon Siswa`" />

    <PageHeader eyebrow="Calon siswa" :title="lead.child_name" :description="`${lead.parent_name} · masuk ${lead.created}${lead.source_label ? ` · dari ${lead.source_label}` : ''}`">
        <template #back>
            <Link :href="route('leads.index')" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Semua calon siswa</Link>
        </template>
        <template #actions>
            <a :href="waUrl" target="_blank" rel="noopener" class="btn-accent"><Icon name="message-circle" :size="18" /> Chat WhatsApp</a>
        </template>
    </PageHeader>

    <!-- Tahapan -->
    <ol v-if="lead.status !== 'lost'" class="mb-6 grid grid-cols-5 gap-1 text-center text-xs font-semibold" aria-label="Tahapan">
        <li v-for="(s, i) in steps" :key="s" class="rounded-lg px-1 py-2" :class="i < stepIndex ? 'bg-brand-100 text-brand-800' : i === stepIndex ? 'bg-brand-600 text-white' : 'bg-chip text-muted'" :aria-current="i === stepIndex ? 'step' : undefined">
            {{ statuses[s] }}
        </li>
    </ol>
    <div v-else class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl bg-chip p-4 text-sm">
        <span class="font-bold text-ink">Tidak lanjut:</span> <span class="text-ink">{{ lead.lost_reason }}</span>
        <button class="btn-ghost ml-auto py-2" @click="post('leads.reopen')"><Icon name="rotate-ccw" :size="16" /> Buka kembali</button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <!-- Langkah berikutnya -->
            <div v-if="open" class="card p-5">
                <h2 class="font-display font-bold text-ink">Langkah berikutnya</h2>
                <template v-if="lead.status === 'new'">
                    <p class="mt-1 text-sm text-muted">Sapa orang tua lewat WhatsApp, tanyakan jadwal yang cocok. Pesan pembuka sudah disiapkan di tombol Chat WhatsApp.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="post('leads.contacted')"><Icon name="check" :size="16" /> Sudah saya hubungi</button>
                        <button class="btn-ghost" @click="scheduling = true"><Icon name="calendar-days" :size="16" /> Langsung jadwalkan trial</button>
                    </div>
                </template>
                <template v-else-if="lead.status === 'contacted'">
                    <p class="mt-1 text-sm text-muted">Setelah jadwal disepakati, catat di sini. Guru akan mendapat notifikasi.</p>
                    <button class="btn-primary mt-4" @click="scheduling = true"><Icon name="calendar-days" :size="16" /> Jadwalkan trial</button>
                </template>
                <template v-else-if="lead.status === 'trial_scheduled'">
                    <p class="mt-1 text-sm text-ink">Trial <strong>{{ lead.trial_at_label }}</strong> bersama <strong>{{ lead.trial_teacher }}</strong>.</p>
                    <p v-if="lead.trial_zoom_url" class="text-sm"><a :href="lead.trial_zoom_url" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Tautan Zoom</a></p>
                    <p class="mt-1 text-sm text-muted">Kirim pengingat lewat tombol Chat WhatsApp sehari sebelumnya. Hasil trial diisi guru setelah sesi.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="recording = true"><Icon name="clipboard-check" :size="16" /> Isi hasil trial</button>
                        <button class="btn-ghost" @click="scheduling = true">Pindah jadwal</button>
                    </div>
                </template>
                <template v-else-if="lead.status === 'trial_done'">
                    <div class="mt-2 rounded-xl bg-emerald-50 p-4 text-sm ring-1 ring-emerald-200">
                        <p class="font-semibold text-emerald-900">Hasil trial · antusiasme {{ lead.trial_interest }}/5<span v-if="lead.recommended_class"> · disarankan {{ lead.recommended_class }}</span></p>
                        <p class="mt-1 whitespace-pre-line text-ink">{{ lead.trial_result }}</p>
                    </div>
                    <p class="mt-3 text-sm text-muted">Tawarkan kelas ke orang tua. Jika setuju, buat akunnya di sini lalu jual paket kuota dari menu Keuangan.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="converting = true"><Icon name="user-plus" :size="16" /> Jadikan siswa</button>
                        <button class="btn-ghost" @click="recording = true">Ubah hasil trial</button>
                    </div>
                </template>
                <div class="mt-4 flex flex-wrap gap-x-4 border-t border-line pt-3 text-sm">
                    <button v-if="lead.status !== 'trial_done'" class="font-semibold text-brand-600 hover:underline" @click="converting = true">Langsung jadikan siswa</button>
                    <button class="font-semibold text-muted hover:text-rose-700" @click="losing = true">Tandai tidak lanjut</button>
                </div>
            </div>

            <div v-if="lead.status === 'won' && lead.student" class="card flex flex-wrap items-center gap-3 border-emerald-200 bg-emerald-50 p-5">
                <Icon name="circle-check" :size="24" class="text-emerald-700" />
                <p class="text-sm text-ink"><strong>{{ lead.student.name }}</strong> sudah jadi siswa (username <span class="font-mono">{{ lead.student.username }}</span>).</p>
                <Link :href="route('finance.packages.index')" class="btn-ghost ml-auto py-2">Jual paket kuota</Link>
            </div>

            <!-- Riwayat -->
            <div class="card p-5">
                <h2 class="font-display font-bold text-ink">Riwayat dan catatan</h2>
                <form class="mt-3 flex gap-2" @submit.prevent="addNote">
                    <label for="note" class="sr-only">Catatan baru</label>
                    <input id="note" v-model="note.body" class="field" placeholder="mis. Ibu minta dihubungi lagi Senin sore" />
                    <button class="btn-primary shrink-0" :disabled="note.processing || !note.body">Simpan</button>
                </form>
                <p v-if="note.errors.body" class="mt-1 text-sm text-rose-600">{{ note.errors.body }}</p>
                <ol class="mt-4 space-y-3 border-l-2 border-line pl-4">
                    <li v-for="n in lead.notes" :key="n.id" class="relative">
                        <span class="absolute top-1.5 -left-[1.3rem] size-2.5 rounded-full" :class="n.type === 'status' ? 'bg-brand-500' : 'bg-accent-500'" />
                        <p class="text-sm whitespace-pre-line text-ink">{{ n.body }}</p>
                        <p class="text-xs text-muted">{{ n.at }}<span v-if="n.user"> · {{ n.user }}</span></p>
                    </li>
                </ol>
            </div>
        </div>

        <!-- Data -->
        <aside class="card h-fit p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display font-bold text-ink">Data pendaftar</h2>
                <button class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink" title="Ubah data" @click="editing = true"><Icon name="pencil" :size="15" /></button>
            </div>
            <dl class="mt-3 space-y-3 text-sm">
                <div><dt class="text-muted">Orang tua</dt><dd class="font-semibold text-ink">{{ lead.parent_name }}</dd></div>
                <div><dt class="text-muted">WhatsApp</dt><dd><a :href="`https://wa.me/${lead.wa}`" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">{{ lead.phone }}</a></dd></div>
                <div v-if="lead.email"><dt class="text-muted">Email</dt><dd class="text-ink">{{ lead.email }}</dd></div>
                <div><dt class="text-muted">Anak</dt><dd class="text-ink">{{ lead.child_name }}<span v-if="lead.child_age">, {{ lead.child_age }} tahun</span><span v-if="lead.child_grade"> · kelas {{ lead.child_grade }}</span></dd></div>
                <div><dt class="text-muted">Minat</dt><dd class="text-ink">{{ lead.interest ?? 'Belum tahu' }}</dd></div>
                <div v-if="lead.preferred_time"><dt class="text-muted">Waktu yang cocok</dt><dd class="text-ink">{{ lead.preferred_time }}</dd></div>
                <div v-if="lead.message"><dt class="text-muted">Pesan</dt><dd class="whitespace-pre-line text-ink">{{ lead.message }}</dd></div>
            </dl>
        </aside>
    </div>

    <!-- Jadwalkan trial -->
    <Modal :show="scheduling" max-width="lg" @close="scheduling = false">
        <form class="space-y-4 p-6" @submit.prevent="schedule">
            <h3 class="font-display text-lg font-bold text-ink">Jadwalkan kelas trial</h3>
            <Field id="t_at" label="Waktu" required :error="trial.errors.trial_at"><input id="t_at" v-model="trial.trial_at" type="datetime-local" class="field" /></Field>
            <Field id="t_teacher" label="Guru" required :error="trial.errors.trial_teacher_id">
                <select id="t_teacher" v-model="trial.trial_teacher_id" class="field"><option value="">Pilih guru</option><option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option></select>
            </Field>
            <Field id="t_zoom" label="Tautan Zoom" :error="trial.errors.trial_zoom_url" hint="Boleh dikosongkan bila guru memakai tautan pribadinya."><input id="t_zoom" v-model="trial.trial_zoom_url" type="url" class="field" placeholder="https://zoom.us/j/…" /></Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="scheduling = false">Batal</button><button class="btn-primary" :disabled="trial.processing">Simpan jadwal</button></div>
        </form>
    </Modal>

    <!-- Hasil trial -->
    <Modal :show="recording" max-width="lg" @close="recording = false">
        <form class="space-y-4 p-6" @submit.prevent="saveResult">
            <h3 class="font-display text-lg font-bold text-ink">Hasil kelas trial</h3>
            <Field id="r_result" label="Bagaimana trialnya?" required :error="result.errors.trial_result"><textarea id="r_result" v-model="result.trial_result" rows="4" class="field" placeholder="Apa yang dibuat anak, seberapa cepat menangkap, apa yang disukai." /></Field>
            <Field id="r_interest" label="Antusiasme anak" required :error="result.errors.trial_interest">
                <div class="flex gap-2">
                    <button v-for="n in 5" :key="n" type="button" class="grid size-11 place-items-center rounded-xl border font-bold" :class="result.trial_interest === n ? 'border-brand-600 bg-brand-600 text-white' : 'border-line text-ink hover:border-brand-300'" :aria-pressed="result.trial_interest === n" @click="result.trial_interest = n">{{ n }}</button>
                </div>
            </Field>
            <Field id="r_class" label="Kelas yang disarankan" :error="result.errors.recommended_class_id">
                <select id="r_class" v-model="result.recommended_class_id" class="field"><option value="">Belum ada</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="recording = false">Batal</button><button class="btn-primary" :disabled="result.processing">Simpan hasil</button></div>
        </form>
    </Modal>

    <!-- Tidak lanjut -->
    <Modal :show="losing" max-width="md" @close="losing = false">
        <form class="space-y-4 p-6" @submit.prevent="lose">
            <h3 class="font-display text-lg font-bold text-ink">Tandai tidak lanjut</h3>
            <Field id="l_reason" label="Alasan" required :error="lost.errors.lost_reason">
                <input id="l_reason" v-model="lost.lost_reason" list="reasons" class="field" />
                <datalist id="reasons"><option>Biaya belum cocok</option><option>Jadwal tidak cocok</option><option>Tidak membalas</option><option>Anak belum tertarik</option><option>Memilih tempat lain</option></datalist>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="losing = false">Batal</button><button class="btn-primary" :disabled="lost.processing">Simpan</button></div>
        </form>
    </Modal>

    <!-- Jadikan siswa -->
    <Modal :show="converting" max-width="lg" @close="converting = false">
        <form class="space-y-4 p-6" @submit.prevent="doConvert">
            <h3 class="font-display text-lg font-bold text-ink">Jadikan {{ lead.child_name }} siswa</h3>
            <p class="text-sm text-muted">Akun anak dan orang tua dibuat dengan kata sandi yang sama. Kirim ke orang tua lewat WhatsApp dan minta diganti setelah masuk.</p>
            <Field id="c_student" label="Username anak" required :error="convert.errors.student_username"><input id="c_student" v-model="convert.student_username" class="field font-mono" /></Field>
            <div v-if="existingGuardian" class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900 ring-1 ring-sky-200">
                Nomor ini sudah terdaftar sebagai orang tua <strong>{{ existingGuardian.name }}</strong> ({{ existingGuardian.username }}). Anak akan ditambahkan ke akun tersebut.
            </div>
            <Field v-else id="c_guardian" label="Username orang tua" required :error="convert.errors.guardian_username"><input id="c_guardian" v-model="convert.guardian_username" class="field font-mono" /></Field>
            <div class="grid grid-cols-2 gap-3">
                <Field id="c_password" label="Kata sandi awal" required :error="convert.errors.password"><input id="c_password" v-model="convert.password" type="text" class="field font-mono" autocomplete="off" /></Field>
                <Field id="c_rel" label="Hubungan" :error="convert.errors.relationship">
                    <select id="c_rel" v-model="convert.relationship" class="field"><option value="ibu">Ibu</option><option value="ayah">Ayah</option><option value="wali">Wali</option></select>
                </Field>
            </div>
            <Field id="c_class" label="Daftarkan ke kelas" :error="convert.errors.class_room_id">
                <select id="c_class" v-model="convert.class_room_id" class="field"><option value="">Nanti saja</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="converting = false">Batal</button><button class="btn-primary" :disabled="convert.processing">Buat akun</button></div>
        </form>
    </Modal>

    <!-- Ubah data -->
    <Modal :show="editing" max-width="2xl" @close="editing = false">
        <form class="grid gap-4 p-6 sm:grid-cols-2" @submit.prevent="saveEdit">
            <h3 class="font-display text-lg font-bold text-ink sm:col-span-2">Ubah data pendaftar</h3>
            <Field id="e_parent" label="Nama orang tua" :error="edit.errors.parent_name"><input id="e_parent" v-model="edit.parent_name" class="field" /></Field>
            <Field id="e_phone" label="Nomor WhatsApp" :error="edit.errors.phone"><input id="e_phone" v-model="edit.phone" class="field" /></Field>
            <Field id="e_child" label="Nama anak" :error="edit.errors.child_name"><input id="e_child" v-model="edit.child_name" class="field" /></Field>
            <div class="grid grid-cols-2 gap-3">
                <Field id="e_age" label="Usia" :error="edit.errors.child_age"><input id="e_age" v-model="edit.child_age" type="number" class="field" /></Field>
                <Field id="e_grade" label="Kelas" :error="edit.errors.child_grade"><input id="e_grade" v-model="edit.child_grade" class="field" /></Field>
            </div>
            <Field id="e_email" label="Email" :error="edit.errors.email"><input id="e_email" v-model="edit.email" class="field" /></Field>
            <Field id="e_interest" label="Minat" :error="edit.errors.interest">
                <select id="e_interest" v-model="edit.interest" class="field"><option value="">Belum tahu</option><option v-for="i in interests" :key="i" :value="i">{{ i }}</option></select>
            </Field>
            <Field id="e_source" label="Sumber" :error="edit.errors.source">
                <select id="e_source" v-model="edit.source" class="field"><option value="">–</option><option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option></select>
            </Field>
            <Field id="e_time" label="Waktu yang cocok"><input id="e_time" v-model="edit.preferred_time" class="field" /></Field>
            <Field id="e_msg" label="Pesan" class="sm:col-span-2"><textarea id="e_msg" v-model="edit.message" rows="2" class="field" /></Field>
            <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn-ghost" @click="editing = false">Batal</button><button class="btn-primary" :disabled="edit.processing">Simpan</button></div>
        </form>
    </Modal>
</template>
