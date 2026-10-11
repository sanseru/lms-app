<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ question: Object, classes: Array, defaults: Object, visibilities: Object });
const isStudent = computed(() => usePage().props.auth.user.role === 'student');
const editing = computed(() => !!props.question);

const form = useForm({
    class_room_id: props.question?.class_room_id ?? props.defaults?.class_room_id ?? null,
    lesson_id: props.question?.lesson_id ?? props.defaults?.lesson_id ?? '',
    title: props.question?.title ?? '',
    body: props.question?.body ?? '',
    code: props.question?.code ?? '',
    visibility: props.question?.visibility ?? 'class',
    anonymous: props.question?.anonymous ?? false,
    image: null,
    remove_image: false,
});

const showCode = ref(!!form.code);
const selectedClass = computed(() => props.classes.find((c) => c.id === form.class_room_id));
watch(() => form.class_room_id, (id, old) => { if (old !== undefined && old !== null && id !== old) form.lesson_id = ''; });

const indent = (e) => {
    const el = e.target;
    const { selectionStart: start, selectionEnd: end } = el;
    form.code = form.code.slice(0, start) + '    ' + form.code.slice(end);
    requestAnimationFrame(() => el.setSelectionRange(start + 4, start + 4));
};

const preview = ref(props.question?.image_url ?? null);
const pickImage = (e) => {
    const file = e.target.files[0];
    form.image = file ?? null;
    form.remove_image = false;
    preview.value = file ? URL.createObjectURL(file) : null;
};
const removeImage = () => { form.image = null; form.remove_image = true; preview.value = null; };

const submit = () => {
    const opts = { forceFormData: true, preserveScroll: true };
    if (editing.value) {
        form.transform(({ class_room_id, ...rest }) => ({ ...rest, lesson_id: rest.lesson_id || null }))
            .post(route('forum.questions.update', props.question.id), opts);
    } else {
        form.transform((d) => ({ ...d, lesson_id: d.lesson_id || null })).post(route('forum.questions.store'), opts);
    }
};

const visibilityHelp = {
    class: 'Teman sekelas bisa ikut membaca dan menjawab.',
    private: 'Hanya kamu dan guru yang bisa melihat.',
};
</script>

<template>
    <Head :title="editing ? 'Ubah pertanyaan' : 'Tanya sesuatu'" />

    <PageHeader eyebrow="Tanya jawab" :title="editing ? 'Ubah pertanyaan' : 'Tanya sesuatu'">
        <template #back>
            <Link :href="editing ? route('forum.questions.show', question.id) : route('forum.questions.index')" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
                <Icon name="arrow-left" :size="15" /> Kembali
            </Link>
        </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <form class="space-y-5 lg:col-span-2" @submit.prevent="submit">
            <div class="card space-y-5 p-5 sm:p-6">
                <Field v-if="!editing" label="Kelas" :error="form.errors.class_room_id" required>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="c in classes"
                            :key="c.id"
                            type="button"
                            :class="form.class_room_id === c.id ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line hover:border-brand-200'"
                            class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-sm font-semibold text-ink transition"
                            @click="form.class_room_id = c.id"
                        >
                            <span :class="(classColors[c.color] ?? classColors.violet).dot" class="size-2.5 rounded-full" />
                            <span class="truncate">{{ c.name }}</span>
                        </button>
                    </div>
                </Field>

                <Field v-if="selectedClass?.lessons.length" label="Tentang pelajaran" hint="Opsional. Membantu guru tahu bagian mana yang membingungkan." :error="form.errors.lesson_id">
                    <select v-model="form.lesson_id" class="field">
                        <option value="">Pertanyaan umum</option>
                        <option v-for="l in selectedClass.lessons" :key="l.id" :value="l.id">{{ l.title }}</option>
                    </select>
                </Field>

                <Field label="Judul pertanyaan" :error="form.errors.title" required hint="Singkat dan jelas, mis. &quot;Kenapa perulangan for-ku tidak berhenti?&quot;">
                    <input v-model="form.title" type="text" maxlength="150" class="field" placeholder="Apa yang membuatmu bingung?" />
                </Field>

                <Field label="Ceritakan lebih lengkap" :error="form.errors.body" required hint="Apa yang sudah kamu coba? Apa yang kamu harapkan terjadi? Tulis `kode` di antara tanda petik terbalik.">
                    <textarea v-model="form.body" rows="6" maxlength="5000" class="field" placeholder="Aku sudah mencoba… tapi hasilnya…" />
                </Field>

                <div>
                    <button v-if="!showCode" type="button" class="btn-ghost text-sm" @click="showCode = true"><Icon name="code" :size="16" /> Tambahkan kode</button>
                    <Field v-else label="Kode kamu" :error="form.errors.code" hint="Tempel kode yang bermasalah. Spasi dan indentasi tetap terjaga.">
                        <textarea v-model="form.code" rows="8" spellcheck="false" class="field bg-[#241a35] font-mono text-sm text-[#f3eefb] placeholder:text-white/40" placeholder="for i in range(5):&#10;    print(i)" @keydown.tab.prevent="indent" />
                    </Field>
                </div>

                <Field label="Gambar (opsional)" :error="form.errors.image" hint="Tangkapan layar error atau hasil program. JPG/PNG, maks 4 MB.">
                    <div v-if="preview" class="relative inline-block">
                        <img :src="preview" alt="Pratinjau gambar" class="max-h-56 rounded-xl border border-line" />
                        <button type="button" class="absolute -top-2 -right-2 grid size-7 place-items-center rounded-full bg-ink text-white shadow" title="Hapus gambar" @click="removeImage"><Icon name="x" :size="14" /></button>
                    </div>
                    <label v-else class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-line px-4 py-4 text-sm text-muted hover:border-brand-300 hover:text-ink">
                        <Icon name="image" :size="20" /> Pilih gambar
                        <input type="file" accept="image/*" class="hidden" @change="pickImage" />
                    </label>
                </Field>
            </div>

            <div class="card space-y-4 p-5 sm:p-6">
                <p class="label !mb-0">Siapa yang bisa melihat?</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="(label, key) in visibilities"
                        :key="key"
                        :class="form.visibility === key ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line'"
                        class="flex cursor-pointer gap-3 rounded-xl border p-3"
                    >
                        <input v-model="form.visibility" type="radio" :value="key" class="mt-1 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm">
                            <span class="flex items-center gap-1.5 font-semibold text-ink"><Icon :name="key === 'private' ? 'lock' : 'users'" :size="15" /> {{ label }}</span>
                            <span class="block text-xs leading-snug text-muted">{{ visibilityHelp[key] }}</span>
                        </span>
                    </label>
                </div>
                <label v-if="isStudent && form.visibility === 'class'" class="flex items-start gap-3 rounded-xl bg-chip/60 p-3 text-sm">
                    <input v-model="form.anonymous" type="checkbox" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" />
                    <span>
                        <span class="flex items-center gap-1.5 font-semibold text-ink"><Icon name="eye-off" :size="15" /> Sembunyikan namaku dari teman</span>
                        <span class="text-xs text-muted">Teman melihat "Teman sekelas". Guru dan orang tuamu tetap tahu siapa yang bertanya.</span>
                    </span>
                </label>
                <p v-if="form.errors.visibility" class="text-sm font-medium text-rose-600">{{ form.errors.visibility }}</p>
            </div>

            <div class="flex justify-end gap-2">
                <Link :href="editing ? route('forum.questions.show', question.id) : route('forum.questions.index')" class="btn-ghost">Batal</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="send" :size="16" /> {{ editing ? 'Simpan' : 'Kirim pertanyaan' }}</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="rounded-3xl bg-gradient-to-br from-brand-600 to-accent-500 p-5 text-white">
                <p class="text-3xl">💡</p>
                <p class="mt-2 font-display text-lg font-bold">Tips bertanya</p>
                <ul class="mt-2 space-y-2 text-sm text-white/90">
                    <li>1. Tulis apa yang ingin kamu buat.</li>
                    <li>2. Ceritakan apa yang sudah kamu coba.</li>
                    <li>3. Tempel kode dan pesan error-nya.</li>
                    <li>4. Kalau sudah terjawab, pilih jawaban terbaik ya!</li>
                </ul>
            </div>
            <div class="card p-5 text-sm text-muted">
                <p class="font-semibold text-ink">Tidak ada pertanyaan yang bodoh 🙌</p>
                <p class="mt-1">Teman sekelasmu mungkin juga bingung hal yang sama. Dengan bertanya, kamu membantu mereka juga.</p>
            </div>
        </aside>
    </div>
</template>
