<script setup>
import FlashToast from '@/Components/ui/FlashToast.vue';
import StudentHeader from '@/Components/StudentHeader.vue';

// Students: the client's top-menu layout instead of the staff sidebar.
</script>

<template>
    <div class="min-h-full bg-paper">
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:font-bold focus:shadow-lg">Skip to content</a>
        <StudentHeader />
        <main id="content" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <slot />
        </main>
        <FlashToast />
    </div>
</template>
