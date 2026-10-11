<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Icon from './Icon.vue';

/**
 * Kartu "Bingung? Tanya guru" di halaman pelajaran. Hanya tampil jika modul
 * Tanya Jawab aktif; datanya diambil terpisah agar modul Belajar tidak bergantung padanya.
 */
const props = defineProps({ lessonId: { type: Number, required: true } });
const enabled = route().has('forum.lessons.summary');
const data = ref(null);

onMounted(async () => {
    if (!enabled) return;
    try {
        const res = await fetch(route('forum.lessons.summary', props.lessonId), { headers: { Accept: 'application/json' } });
        if (res.ok) data.value = await res.json();
    } catch {
        /* kartu tetap disembunyikan */
    }
});
</script>

<template>
    <div v-if="data && (data.can_ask || data.count)" class="overflow-hidden rounded-2xl border border-brand-100 bg-brand-50">
        <div class="flex flex-wrap items-center gap-4 p-5">
            <span class="grid size-12 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm"><Icon name="message-question" :size="22" /></span>
            <div class="min-w-0 flex-1">
                <p class="font-display font-bold text-ink">{{ data.can_ask ? 'Bingung? Tanya guru' : 'Tanya jawab pelajaran ini' }}</p>
                <p class="text-sm text-muted">
                    <template v-if="data.count">{{ data.count }} pertanyaan tentang pelajaran ini.</template>
                    <template v-else>Belum ada pertanyaan. Jadilah yang pertama bertanya!</template>
                </p>
            </div>
            <Link v-if="data.count" :href="route('forum.questions.index', { lesson: lessonId })" class="btn-ghost bg-white">Lihat</Link>
            <Link v-if="data.can_ask" :href="route('forum.questions.create', { lesson: lessonId })" class="btn-primary"><Icon name="plus" :size="16" /> Tanya</Link>
        </div>
        <ul v-if="data.latest.length" class="divide-y divide-brand-100 border-t border-brand-100 bg-white/70">
            <li v-for="q in data.latest" :key="q.id">
                <Link :href="route('forum.questions.show', q.id)" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white">
                    <Icon :name="q.status === 'resolved' ? 'circle-check' : 'message-circle'" :size="16" :class="q.status === 'resolved' ? 'text-emerald-500' : 'text-muted'" />
                    <span class="min-w-0 flex-1 truncate font-semibold text-ink">{{ q.title }}</span>
                    <span class="shrink-0 text-xs text-muted">{{ q.answers_count }} jawaban</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
