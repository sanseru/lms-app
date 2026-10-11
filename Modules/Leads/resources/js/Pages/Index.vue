<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({ columns: Array, closed: Array, stats: Object, search: String, interests: Array, sources: Object });

const filters = useFilters({ search: props.search ?? '' }, 'leads.index');
const adding = ref(false);
const form = useForm({ parent_name: '', phone: '', email: '', child_name: '', child_age: '', child_grade: '', interest: '', preferred_time: '', source: 'whatsapp', message: '' });
const save = () => form.post(route('leads.store'));

const tone = { new: 'bg-sky-500', contacted: 'bg-brand-500', trial_scheduled: 'bg-amber-500', trial_done: 'bg-emerald-500' };
const empty = { new: 'Belum ada pendaftar baru.', contacted: 'Kosong.', trial_scheduled: 'Belum ada trial terjadwal.', trial_done: 'Belum ada hasil trial.' };
</script>

<template>
    <Head title="Calon Siswa" />

    <PageHeader eyebrow="Pengguna" title="Calon siswa" description="Dari pendaftar coba gratis sampai jadi siswa. Hubungi yang baru masuk secepatnya, jadwalkan trial, lalu daftarkan ke kelas.">
        <template #actions>
            <Link :href="route('leads.trials')" class="btn-ghost"><Icon name="calendar-days" :size="18" /> Jadwal trial</Link>
            <button class="btn-accent" @click="adding = true"><Icon name="plus" :size="18" /> Catat manual</button>
        </template>
    </PageHeader>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-semibold text-muted">Pendaftar bulan ini</p>
            <p class="font-display text-2xl font-extrabold text-ink">{{ stats.new_month }}</p>
        </div>
        <div class="card p-4" :class="stats.waiting && 'border-amber-300 bg-amber-50'">
            <p class="text-xs font-semibold text-muted">Belum dihubungi &gt; 1 hari</p>
            <p class="font-display text-2xl font-extrabold" :class="stats.waiting ? 'text-amber-800' : 'text-ink'">{{ stats.waiting }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-muted">Trial 7 hari ke depan</p>
            <p class="font-display text-2xl font-extrabold text-ink">{{ stats.trials_week }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-muted">Jadi siswa (90 hari)</p>
            <p class="font-display text-2xl font-extrabold text-ink">{{ stats.won_90 }}<span v-if="stats.conversion !== null" class="ml-1 text-sm font-semibold text-muted">· {{ stats.conversion }}%</span></p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchInput v-model="filters.search" placeholder="Cari nama orang tua, anak, atau nomor…" />
        <p v-if="stats.sources.length" class="text-sm text-muted sm:ml-auto">
            Sumber teratas: <span v-for="(s, i) in stats.sources.slice(0, 3)" :key="s.label"><span class="font-semibold text-ink">{{ s.label }}</span> {{ s.total }}<span v-if="i < Math.min(stats.sources.length, 3) - 1">, </span></span>
        </p>
    </div>

    <!-- Papan pipeline -->
    <div class="-mx-4 overflow-x-auto px-4 pb-2">
        <div class="grid min-w-[60rem] grid-cols-4 gap-4">
            <section v-for="col in columns" :key="col.status" class="rounded-2xl bg-chip/70 p-3">
                <h2 class="mb-3 flex items-center gap-2 px-1 text-sm font-bold text-ink">
                    <span class="size-2.5 rounded-full" :class="tone[col.status]" />{{ col.label }}
                    <span class="ml-auto rounded-full bg-white px-2 text-xs text-muted">{{ col.leads.length }}</span>
                </h2>
                <div class="space-y-2">
                    <Link v-for="l in col.leads" :key="l.id" :href="route('leads.show', l.id)" class="block rounded-xl border border-line bg-white p-3 transition hover:border-brand-300">
                        <p class="font-semibold text-ink">{{ l.child_name }}<span v-if="l.child_age" class="font-normal text-muted"> · {{ l.child_age }} th</span></p>
                        <p class="text-sm text-muted">{{ l.parent_name }}</p>
                        <p v-if="l.interest" class="mt-1 text-xs font-semibold text-brand-700">{{ l.interest }}</p>
                        <p v-if="l.trial_at" class="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900"><Icon name="calendar-days" :size="13" /> {{ l.trial_at }} · {{ l.trial_teacher }}</p>
                        <p class="mt-2 text-xs" :class="col.status === 'new' && l.age_days >= 1 ? 'font-semibold text-amber-800' : 'text-muted'">{{ l.created }}<span v-if="l.source"> · {{ l.source }}</span></p>
                    </Link>
                    <p v-if="!col.leads.length" class="px-1 py-4 text-center text-sm text-muted">{{ empty[col.status] }}</p>
                </div>
            </section>
        </div>
    </div>

    <!-- Selesai -->
    <div v-if="closed.length" class="card mt-8 overflow-hidden">
        <h2 class="border-b border-line px-5 py-3 font-display font-bold text-ink">Selesai terakhir</h2>
        <ul class="divide-y divide-line">
            <li v-for="l in closed" :key="l.id">
                <Link :href="route('leads.show', l.id)" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 hover:bg-chip/40">
                    <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1" :class="l.status === 'won' ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-chip text-muted ring-line'">{{ l.status === 'won' ? 'Jadi siswa' : 'Tidak lanjut' }}</span>
                    <span class="font-semibold text-ink">{{ l.child_name }}</span>
                    <span class="text-sm text-muted">{{ l.parent_name }}<span v-if="l.lost_reason"> · {{ l.lost_reason }}</span></span>
                    <span class="ml-auto text-xs text-muted">{{ l.closed }}</span>
                </Link>
            </li>
        </ul>
    </div>

    <Modal :show="adding" max-width="2xl" @close="adding = false">
        <form class="grid gap-4 p-6 sm:grid-cols-2" @submit.prevent="save">
            <h3 class="font-display text-lg font-bold text-ink sm:col-span-2">Catat calon siswa</h3>
            <p class="-mt-2 text-sm text-muted sm:col-span-2">Untuk orang tua yang menghubungi lewat WhatsApp, telepon, atau datang langsung.</p>
            <Field id="m_parent" label="Nama orang tua" required :error="form.errors.parent_name"><input id="m_parent" v-model="form.parent_name" class="field" /></Field>
            <Field id="m_phone" label="Nomor WhatsApp" required :error="form.errors.phone"><input id="m_phone" v-model="form.phone" type="tel" class="field" /></Field>
            <Field id="m_child" label="Nama anak" required :error="form.errors.child_name"><input id="m_child" v-model="form.child_name" class="field" /></Field>
            <div class="grid grid-cols-2 gap-3">
                <Field id="m_age" label="Usia" :error="form.errors.child_age"><input id="m_age" v-model="form.child_age" type="number" class="field" /></Field>
                <Field id="m_grade" label="Kelas" :error="form.errors.child_grade"><input id="m_grade" v-model="form.child_grade" class="field" /></Field>
            </div>
            <Field id="m_interest" label="Minat" :error="form.errors.interest">
                <select id="m_interest" v-model="form.interest" class="field"><option value="">Belum tahu</option><option v-for="i in interests" :key="i" :value="i">{{ i }}</option></select>
            </Field>
            <Field id="m_source" label="Sumber" :error="form.errors.source">
                <select id="m_source" v-model="form.source" class="field"><option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option></select>
            </Field>
            <Field id="m_time" label="Waktu yang cocok" class="sm:col-span-2"><input id="m_time" v-model="form.preferred_time" class="field" /></Field>
            <Field id="m_msg" label="Catatan" class="sm:col-span-2"><textarea id="m_msg" v-model="form.message" rows="2" class="field" /></Field>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn-ghost" @click="adding = false">Batal</button>
                <button class="btn-primary" :disabled="form.processing">Simpan</button>
            </div>
        </form>
    </Modal>
</template>
