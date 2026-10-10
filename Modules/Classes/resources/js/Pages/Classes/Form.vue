<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { classColors } from '@/lib/format';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

const props = defineProps({
    classRoom: Object,
    teachers: Array,
    options: Object,
});

const c = props.classRoom ?? {};
const form = useForm({
    name: c.name ?? '',
    subject: c.subject ?? '',
    level: c.level ?? 'beginner',
    description: c.description ?? '',
    teacher_id: c.teacher_id ?? '',
    zoom_url: c.zoom_url ?? '',
    zoom_meeting_id: c.zoom_meeting_id ?? '',
    zoom_passcode: c.zoom_passcode ?? '',
    schedule_days: c.schedule_days ?? [],
    start_time: c.start_time ?? '16:00',
    end_time: c.end_time ?? '17:30',
    start_date: c.start_date ?? '',
    end_date: c.end_date ?? '',
    capacity: c.capacity ?? '',
    status: c.status ?? 'active',
    color: c.color ?? 'violet',
});

const toggleDay = (day) => {
    const d = Number(day);
    form.schedule_days = form.schedule_days.includes(d) ? form.schedule_days.filter((x) => x !== d) : [...form.schedule_days, d].sort();
};

const submit = () => (props.classRoom ? form.put(route('classes.update', props.classRoom.id)) : form.post(route('classes.store')));
</script>

<template>
    <Head :title="classRoom ? 'Ubah kelas' : 'Buat kelas'" />

    <PageHeader :eyebrow="classRoom ? classRoom.code : 'Kelas baru'" :title="classRoom ? classRoom.name : 'Buat kelas'">
        <template #back>
            <Link :href="classRoom ? route('classes.show', classRoom.id) : route('classes.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Kembali
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Informasi kelas">
            <Field id="name" label="Nama kelas" :error="form.errors.name" required class="sm:col-span-2">
                <input id="name" v-model="form.name" type="text" class="field" placeholder="mis. Python Start 1 – Sabtu Pagi" required />
            </Field>
            <Field id="subject" label="Mata pelajaran" :error="form.errors.subject" required>
                <input id="subject" v-model="form.subject" list="subjects" type="text" class="field" required />
                <datalist id="subjects"><option v-for="s in options.subjects" :key="s" :value="s" /></datalist>
            </Field>
            <Field id="level" label="Tingkat" :error="form.errors.level">
                <select id="level" v-model="form.level" class="field">
                    <option v-for="(label, key) in options.levels" :key="key" :value="key">{{ label }}</option>
                </select>
            </Field>
            <Field id="teacher_id" label="Guru" :error="form.errors.teacher_id" class="sm:col-span-2" hint="Sistem akan menolak jika jadwal guru bentrok dengan kelas lain.">
                <select id="teacher_id" v-model="form.teacher_id" class="field">
                    <option value="">Belum ditentukan</option>
                    <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}<template v-if="t.expertise"> — {{ t.expertise }}</template></option>
                </select>
            </Field>
            <Field id="description" label="Deskripsi" :error="form.errors.description" class="sm:col-span-2">
                <textarea id="description" v-model="form.description" rows="3" class="field" />
            </Field>
            <Field label="Warna kartu" class="sm:col-span-2">
                <div class="flex gap-2">
                    <button
                        v-for="color in options.colors"
                        :key="color"
                        type="button"
                        :class="[classColors[color].solid, form.color === color ? 'ring-2 ring-offset-2 ring-ink' : '']"
                        class="size-8 rounded-full transition"
                        :aria-label="color"
                        @click="form.color = color"
                    />
                </div>
            </Field>
        </FormSection>

        <FormSection title="Jadwal mingguan" description="Kelas berlangsung rutin di hari dan jam yang sama setiap minggu.">
            <Field label="Hari" :error="form.errors.schedule_days" required class="sm:col-span-2">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(label, day) in options.days"
                        :key="day"
                        type="button"
                        :class="form.schedule_days.includes(Number(day)) ? 'bg-brand-600 text-white shadow-[0_3px_0_var(--color-brand-800)]' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                        @click="toggleDay(day)"
                    >
                        {{ label }}
                    </button>
                </div>
            </Field>
            <Field id="start_time" label="Jam mulai" :error="form.errors.start_time" required>
                <input id="start_time" v-model="form.start_time" type="time" class="field" required />
            </Field>
            <Field id="end_time" label="Jam selesai" :error="form.errors.end_time" required>
                <input id="end_time" v-model="form.end_time" type="time" class="field" required />
            </Field>
            <Field id="start_date" label="Tanggal mulai" :error="form.errors.start_date">
                <input id="start_date" v-model="form.start_date" type="date" class="field" />
            </Field>
            <Field id="end_date" label="Tanggal selesai" :error="form.errors.end_date">
                <input id="end_date" v-model="form.end_date" type="date" class="field" />
            </Field>
            <Field id="capacity" label="Kapasitas siswa" :error="form.errors.capacity" hint="Kosongkan jika tidak dibatasi.">
                <input id="capacity" v-model="form.capacity" type="number" min="1" class="field" />
            </Field>
            <Field id="status" label="Status" :error="form.errors.status">
                <select id="status" v-model="form.status" class="field">
                    <option v-for="(label, key) in options.statuses" :key="key" :value="key">{{ label }}</option>
                </select>
            </Field>
        </FormSection>

        <FormSection title="Zoom" description="Link yang dipakai siswa dan guru untuk masuk kelas.">
            <Field id="zoom_url" label="Link Zoom" :error="form.errors.zoom_url" class="sm:col-span-2">
                <input id="zoom_url" v-model="form.zoom_url" type="url" class="field" placeholder="https://us06web.zoom.us/j/…" />
            </Field>
            <Field id="zoom_meeting_id" label="Meeting ID" :error="form.errors.zoom_meeting_id">
                <input id="zoom_meeting_id" v-model="form.zoom_meeting_id" type="text" class="field font-mono" />
            </Field>
            <Field id="zoom_passcode" label="Passcode" :error="form.errors.zoom_passcode">
                <input id="zoom_passcode" v-model="form.zoom_passcode" type="text" class="field font-mono" />
            </Field>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="classRoom ? route('classes.show', classRoom.id) : route('classes.index')" class="btn-ghost">Batal</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan</button>
        </div>
    </form>
</template>
