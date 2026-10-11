<script setup>
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
</script>

<template>
    <Head title="Home" />
    <div class="card">
        <EmptyState icon="user" title="Your account has no role yet" description="Ask your admin to give your account a teacher, student or parent role." />
    </div>
</template>
