<script setup>
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
</script>

<template>
    <Head title="Beranda" />
    <div class="card">
        <EmptyState icon="user" title="Akun belum memiliki peran" description="Hubungi admin agar akun Anda diberi peran guru, siswa, atau orang tua." />
    </div>
</template>
