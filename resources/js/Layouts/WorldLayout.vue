<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useFullscreen } from '@/lib/fullscreen';

/**
 * Full-width layout for the student's course map (client prototype: the map is the
 * whole screen). No sidebar; a slim bar with the way home, notifications and fullscreen.
 */
const page = usePage();
const unread = computed(() => page.props.notifications?.unread ?? 0);

const root = ref(null);
const canFullscreen = ref(false);
const { active: fullscreen, toggle } = useFullscreen(root);
onMounted(() => {
    root.value = document.documentElement;
    canFullscreen.value = !!document.fullscreenEnabled;
});
</script>

<template>
    <div class="world-sky flex min-h-full flex-col">
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:font-bold focus:shadow-lg">Skip to content</a>

        <header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-3 sm:px-6">
                <Link :href="route('dashboard')" class="btn-ghost px-3" aria-label="Back to Home"><Icon name="arrow-left" :size="18" /> <span class="hidden sm:inline">Home</span></Link>
                <img src="/images/logo-header.png" alt="Alprogmatics" class="hidden h-8 w-auto sm:block" />
                <span class="flex-1" />
                <Link :href="route('learning.courses.explore')" class="btn-ghost px-3"><Icon name="compass" :size="18" /> <span class="hidden sm:inline">Explore courses</span></Link>
                <Link
                    :href="route('notifications.index')"
                    class="relative grid size-11 place-items-center rounded-xl text-muted ring-1 ring-line hover:bg-chip hover:text-ink"
                    :aria-label="unread ? `Notifications, ${unread} unread` : 'Notifications'"
                >
                    <Icon name="bell" :size="18" />
                    <span v-if="unread" class="absolute -top-1.5 -right-1.5 grid min-w-5 place-items-center rounded-full bg-accent-500 px-1 text-[11px] font-bold text-ink">{{ unread > 9 ? '9+' : unread }}</span>
                </Link>
                <button
                    v-if="canFullscreen"
                    type="button"
                    class="grid size-11 place-items-center rounded-xl text-muted ring-1 ring-line hover:bg-chip hover:text-ink"
                    :aria-label="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    :title="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    @click="toggle"
                >
                    <Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="18" />
                </button>
            </div>
        </header>

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
