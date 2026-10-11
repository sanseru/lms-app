<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ mode: String, certificates: Array, eligible: Array, classes: Array });
const titles = { staff: 'Sertifikat', student: 'Sertifikatku', guardian: 'Sertifikat anak' };

const issuing = ref(null);
const issue = (row) => {
    issuing.value = `${row.student.id}-${row.course.id}`;
    useForm({ student_id: row.student.id, class_room_id: row.class.id, course_id: row.course.id })
        .post(route('certificates.store'), { preserveScroll: true, onFinish: () => (issuing.value = null) });
};

// Terbitkan manual (mis. lulus level atau juara lomba)
const showManual = ref(false);
const form = useForm({ class_room_id: '', student_id: '', course_id: null, title: '', achievement: '', issued_at: '' });
const klass = computed(() => props.classes?.find((c) => c.id === form.class_room_id));
const pickClass = () => { form.student_id = ''; form.title = klass.value?.course_title ?? ''; form.course_id = null; };
const submitManual = () => form
    .transform((d) => ({ ...d, achievement: d.achievement || null, issued_at: d.issued_at || null, course_id: d.course_id || null }))
    .post(route('certificates.store'), { preserveScroll: true, onSuccess: () => { form.reset(); showManual.value = false; } });
</script>

<template>
    <Head :title="titles[mode]" />

    <PageHeader eyebrow="Belajar" :title="titles[mode]" :description="mode === 'staff' ? 'Terbitkan sertifikat untuk siswa yang sudah menyelesaikan kursus. Setiap sertifikat punya QR untuk memeriksa keasliannya.' : 'Sertifikat dari kursus yang sudah diselesaikan. Bisa dicetak atau disimpan sebagai PDF.'">
        <template v-if="mode === 'staff'" #actions>
            <button class="btn-ghost" @click="showManual = !showManual"><Icon name="plus" :size="16" /> Terbitkan manual</button>
        </template>
    </PageHeader>

    <!-- Terbitkan manual -->
    <form v-if="mode === 'staff' && showManual" class="card mb-6 grid gap-4 p-5 sm:grid-cols-2 sm:p-6" @submit.prevent="submitManual">
        <Field label="Kelas" :error="form.errors.class_room_id" required>
            <select v-model="form.class_room_id" class="field" @change="pickClass"><option value="" disabled>Pilih kelas</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
        </Field>
        <Field label="Siswa" :error="form.errors.student_id" required>
            <select v-model="form.student_id" class="field" :disabled="!klass"><option value="" disabled>Pilih siswa</option><option v-for="s in klass?.students ?? []" :key="s.id" :value="s.id">{{ s.name }}</option></select>
        </Field>
        <Field label="Judul" :error="form.errors.title" required hint="mis. nama kursus, level, atau lomba"><input v-model="form.title" type="text" maxlength="150" class="field" /></Field>
        <Field label="Keterangan" :error="form.errors.achievement" hint="Kosongkan untuk: telah menyelesaikan seluruh materi dan proyek kursus"><input v-model="form.achievement" type="text" maxlength="255" class="field" placeholder="mis. sebagai Juara 1 Lomba Game Scratch" /></Field>
        <Field label="Tanggal terbit" :error="form.errors.issued_at" hint="Kosongkan untuk hari ini"><input v-model="form.issued_at" type="date" class="field" /></Field>
        <div class="flex items-end justify-end gap-2">
            <button type="button" class="btn-ghost" @click="showManual = false">Batal</button>
            <button class="btn-primary" :disabled="form.processing"><Icon name="award" :size="16" /> Terbitkan</button>
        </div>
    </form>

    <!-- Siap diterbitkan -->
    <div v-if="mode === 'staff' && eligible.length" class="mb-8 overflow-hidden rounded-3xl border border-amber-200 bg-amber-50">
        <div class="flex items-center gap-3 px-5 py-4">
            <span class="grid size-10 place-items-center rounded-xl bg-white text-amber-600"><Icon name="award" /></span>
            <div>
                <p class="font-display font-bold text-ink">{{ eligible.length }} siswa siap mendapat sertifikat</p>
                <p class="text-sm text-muted">Mereka sudah menyelesaikan semua pelajaran di kursus kelasnya.</p>
            </div>
        </div>
        <ul class="divide-y divide-amber-200/70 border-t border-amber-200 bg-white">
            <li v-for="row in eligible" :key="`${row.student.id}-${row.course.id}`" class="flex flex-wrap items-center gap-3 px-5 py-3">
                <Avatar :initials="row.student.initials" :seed="row.student.id" size="sm" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ row.student.name }}</p>
                    <p class="text-xs text-muted">{{ row.course.title }} · {{ row.class.name }} · selesai {{ row.finished_at }}</p>
                </div>
                <button class="btn-primary py-1.5 text-sm" :disabled="issuing === `${row.student.id}-${row.course.id}`" @click="issue(row)"><Icon name="award" :size="15" /> Terbitkan</button>
            </li>
        </ul>
    </div>

    <h2 v-if="mode === 'staff'" class="mb-3 font-display text-lg font-bold text-ink">Sudah terbit</h2>
    <div v-if="certificates.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <Link
            v-for="c in certificates"
            :key="c.id"
            :href="route('certificates.show', c.id)"
            class="group overflow-hidden rounded-3xl border border-line bg-white transition hover:-translate-y-1 hover:shadow-lg"
        >
            <div :class="(classColors[c.color] ?? classColors.violet).grad" class="relative grid aspect-[297/150] place-items-center p-5 text-center text-white">
                <span class="absolute top-3 right-4 text-2xl">🏅</span>
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] text-white uppercase">Sertifikat</p>
                    <p class="mt-1 font-display text-lg leading-tight font-extrabold">{{ c.title }}</p>
                    <p class="mt-1 text-sm text-white">{{ c.student.name }}</p>
                </div>
                <span v-if="c.revoked" class="absolute inset-0 grid place-items-center bg-white/70 font-display text-xl font-black text-rose-600">DICABUT</span>
            </div>
            <div class="flex items-center justify-between px-5 py-3 text-xs text-muted">
                <span>{{ c.issued_at }}</span>
                <span class="font-mono">{{ c.number }}</span>
            </div>
        </Link>
    </div>
    <div v-else class="card">
        <EmptyState
            icon="award"
            :title="mode === 'staff' ? 'Belum ada sertifikat terbit' : 'Belum ada sertifikat'"
            :description="mode === 'staff' ? null : 'Selesaikan semua pelajaran di kursusmu, lalu gurumu akan menerbitkan sertifikat.'"
        />
    </div>
</template>
