<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';

defineProps({ items: Object });

const readAll = () => router.post(route('notifications.read-all'), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Notifikasi" />

    <PageHeader eyebrow="Kabar terbaru" title="Notifikasi" description="Tagihan, pembayaran, nilai tugas, jawaban pertanyaan, dan ringkasan sesi.">
        <template #actions>
            <button v-if="items.data.some((n) => !n.read)" class="btn-ghost" @click="readAll"><Icon name="check" :size="16" /> Tandai semua dibaca</button>
        </template>
    </PageHeader>

    <div class="card overflow-hidden">
        <ul v-if="items.data.length" class="divide-y divide-line">
            <li v-for="n in items.data" :key="n.id">
                <Link
                    :href="route('notifications.open', n.id)"
                    :class="n.read ? 'bg-white' : 'bg-brand-50/60'"
                    class="flex items-start gap-4 px-5 py-4 transition hover:bg-chip/60"
                >
                    <span :class="n.read ? 'bg-chip text-muted' : 'bg-brand-600 text-white'" class="grid size-10 shrink-0 place-items-center rounded-xl">
                        <Icon :name="n.icon" :size="18" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-3">
                            <span :class="n.read ? 'font-semibold' : 'font-bold'" class="text-ink">{{ n.title }}</span>
                            <span class="shrink-0 text-xs text-muted">{{ n.time }}</span>
                        </span>
                        <span v-if="n.body" class="mt-0.5 block text-sm text-muted">{{ n.body }}</span>
                    </span>
                    <span v-if="!n.read" class="mt-2 size-2 shrink-0 rounded-full bg-accent-500" aria-label="Belum dibaca" />
                </Link>
            </li>
        </ul>
        <EmptyState v-else icon="bell" title="Belum ada notifikasi" description="Kabar soal tagihan, nilai, dan jawaban pertanyaan akan muncul di sini." />
        <Pagination :meta="items" />
    </div>
</template>
