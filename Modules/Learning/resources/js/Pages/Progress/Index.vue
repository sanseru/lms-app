<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

defineProps({ children: Array });
</script>

<template>
    <Head title="Progres Belajar" />
    <PageHeader eyebrow="Portal orang tua" title="Progres belajar" description="Pilih anak untuk melihat materi yang sudah diselesaikan, nilai kuis, dan nilai tugas." />

    <div v-if="children.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Link v-for="c in children" :key="c.id" :href="route('learning.progress.show', c.id)" class="card flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
            <Avatar :initials="c.initials" :seed="c.id" size="lg" />
            <span class="flex-1 font-display text-lg font-bold text-ink">{{ c.name }}</span>
            <Icon name="arrow-right" :size="18" class="text-brand-600" />
        </Link>
    </div>
    <div v-else class="card"><EmptyState icon="users" title="Belum ada anak terhubung" description="Hubungi admin untuk menghubungkan akun anak Anda." /></div>
</template>
