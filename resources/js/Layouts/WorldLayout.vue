<script setup>
import FlashToast from '@/Components/ui/FlashToast.vue';
import StudentHeader from '@/Components/StudentHeader.vue';

/**
 * Full-width layout for the student's course map (client prototype: the map is the
 * whole screen). Same header as the other student pages, plus a fullscreen button.
 * The browser only goes fullscreen when the student presses that button (client: the
 * browser tabs and the taskbar must stay visible when opening Course).
 */
</script>

<template>
    <div class="world-sky flex min-h-full flex-col">
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:font-bold focus:shadow-lg">Skip to content</a>

        <StudentHeader fullscreen-button />

        <main id="content" class="world-clouds w-full flex-1">
            <slot />
        </main>

        <FlashToast />
    </div>
</template>

<style scoped>
/* The sky of the learning world fills the whole screen (client prototype: course/python/map/python.css). */
.world-sky {
    background: linear-gradient(180deg, #8fdcf7 0, #c4f0fd 900px, #e9fbff 100%);
}
.world-clouds {
    background:
        radial-gradient(ellipse 120px 44px at 10% 9%, rgb(255 255 255 / 0.9) 60%, transparent 62%),
        radial-gradient(ellipse 160px 52px at 88% 21%, rgb(255 255 255 / 0.85) 60%, transparent 62%),
        radial-gradient(ellipse 130px 46px at 7% 47%, rgb(255 255 255 / 0.85) 60%, transparent 62%),
        radial-gradient(ellipse 150px 50px at 91% 71%, rgb(255 255 255 / 0.85) 60%, transparent 62%),
        radial-gradient(ellipse 120px 44px at 13% 90%, rgb(255 255 255 / 0.9) 60%, transparent 62%);
    background-size: 100% 900px;
    background-repeat: repeat-y;
}
</style>
