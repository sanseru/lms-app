<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import Icon from '@/Components/ui/Icon.vue';
import NotificationBell from '@/Components/ui/NotificationBell.vue';
import UserMenu from '@/Components/ui/UserMenu.vue';
import { enterPageFullscreen, useFullscreen } from '@/lib/fullscreen';

/**
 * Student header, as in the client prototype: big logo, the six main menus in the
 * client's order (Home, Course, Online Lesson, Community, Laboratory, Archive), and
 * everything else the app offers under "More". Phones get a drawer.
 */
defineProps({ fullscreenButton: Boolean });

const PRIMARY = ['dashboard', 'learning.courses.index', 'schedule.index', 'community.index', 'lab.projects.index', 'learning.archive'];

const page = usePage();
const items = computed(() => (page.props.navigation ?? []).flatMap((g) => g.items));
const primary = computed(() => PRIMARY.map((r) => items.value.find((i) => i.route === r)).filter(Boolean));
const more = computed(() => items.value.filter((i) => !PRIMARY.includes(i.route)));
const isActive = (item) => item.active.split('|').some((p) => route().current(p));
const moreActive = computed(() => more.value.some(isActive));
// Opening the course (client: the course is a full-screen world) goes fullscreen too.
const onItemClick = (item) => item.route === 'learning.courses.index' && enterPageFullscreen();

const drawer = ref(false);
router.on('navigate', () => {
    drawer.value = false;
    searching.value = false;
});

// Search (client prototype): courses and lessons, results on their own page.
const searching = ref(false);
const term = ref('');
const searchInput = ref(null);
const canSearch = route().has('learning.search');
const openSearch = async () => {
    searching.value = !searching.value;
    await nextTick();
    searchInput.value?.focus();
};
const search = () => term.value.trim().length >= 2 && router.get(route('learning.search'), { q: term.value.trim() });

const root = ref(null);
const canFullscreen = ref(false);
const { active: fullscreen, toggle } = useFullscreen(root);
onMounted(() => {
    root.value = document.documentElement;
    canFullscreen.value = !!document.fullscreenEnabled;
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-line bg-white">
        <div class="mx-auto flex h-[68px] max-w-[1400px] items-center gap-3 px-4 sm:px-6 md:h-[84px] lg:gap-6 lg:px-10">
            <Link :href="route('dashboard')" class="shrink-0" aria-label="Alprogmatics home">
                <img src="/images/logo-header.png" alt="Alprogmatics" class="h-12 w-auto md:h-[54px] xl:h-[62px]" />
            </Link>

            <nav class="hidden min-w-0 flex-1 items-center gap-1 lg:flex" aria-label="Main menu">
                <Link
                    v-for="item in primary"
                    :key="item.route"
                    :href="item.href"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    @click.capture="onItemClick(item)"
                    :class="isActive(item) ? 'font-extrabold text-brand-600' : 'font-medium text-ink hover:bg-brand-50 hover:text-brand-600'"
                    class="rounded-[10px] px-3.5 py-2 text-base whitespace-nowrap transition"
                >
                    {{ item.label }}
                </Link>
                <Dropdown v-if="more.length" align="left" width="48">
                    <template #trigger>
                        <button
                            type="button"
                            :class="moreActive ? 'font-extrabold text-brand-600' : 'font-medium text-ink hover:bg-brand-50 hover:text-brand-600'"
                            class="inline-flex items-center gap-1 rounded-[10px] px-3.5 py-2 text-base transition"
                        >
                            More <Icon name="chevron-down" :size="16" />
                        </button>
                    </template>
                    <template #content>
                        <Link
                            v-for="item in more"
                            :key="item.route + item.label"
                            :href="item.href"
                            :class="isActive(item) ? 'bg-brand-50 font-bold text-brand-700' : 'text-ink hover:bg-chip'"
                            class="flex items-center gap-2.5 px-4 py-2.5 text-sm"
                        >
                            <Icon :name="item.icon" :size="16" class="text-muted" /> {{ item.label }}
                        </Link>
                    </template>
                </Dropdown>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <button
                    v-if="canSearch"
                    type="button"
                    class="grid size-11 place-items-center rounded-xl bg-chip text-ink hover:bg-brand-50"
                    aria-label="Search"
                    title="Search"
                    :aria-expanded="searching"
                    aria-controls="header-search"
                    @click="openSearch"
                >
                    <Icon name="search" :size="20" />
                </button>
                <button
                    v-if="fullscreenButton && canFullscreen"
                    type="button"
                    class="hidden size-11 place-items-center rounded-xl bg-chip text-ink hover:bg-brand-50 sm:grid"
                    :aria-label="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    :title="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    @click="toggle"
                >
                    <Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="20" />
                </button>
                <NotificationBell />
                <UserMenu />
                <button type="button" class="grid size-11 place-items-center rounded-xl text-ink hover:bg-chip lg:hidden" aria-label="Open menu" :aria-expanded="drawer" @click="drawer = true">
                    <Icon name="menu" />
                </button>
            </div>
        </div>

        <!-- Search box under the header (client prototype) -->
        <form v-if="searching" id="header-search" role="search" class="absolute inset-x-0 top-full border-b border-line bg-white px-4 py-3 shadow-sm sm:px-6 lg:px-10" @submit.prevent="search" @keydown.esc="searching = false">
            <div class="mx-auto flex max-w-2xl items-center gap-2">
                <label for="header-search-input" class="sr-only">Search for courses or lessons</label>
                <input id="header-search-input" ref="searchInput" v-model="term" type="search" class="field" placeholder="Search for courses or lessons..." maxlength="80" />
                <button type="submit" class="btn-primary shrink-0" :disabled="term.trim().length < 2"><Icon name="search" :size="16" /> Search</button>
            </div>
        </form>

        <!-- Phone and tablet menu -->
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
            <div v-if="drawer" class="fixed inset-0 z-50 bg-ink/40 lg:hidden" @click="drawer = false" />
        </Transition>
        <Transition enter-from-class="translate-x-full" leave-to-class="translate-x-full" enter-active-class="transition-transform" leave-active-class="transition-transform">
            <nav v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-72 max-w-[85vw] flex-col overflow-y-auto bg-white p-4 shadow-xl lg:hidden" aria-label="Menu">
                <div class="mb-3 flex items-center justify-between">
                    <img src="/images/logo-header.png" alt="Alprogmatics" class="h-12 w-auto" />
                    <button type="button" class="grid size-11 place-items-center rounded-xl text-muted hover:bg-chip" aria-label="Close menu" @click="drawer = false"><Icon name="x" /></button>
                </div>
                <Link
                    v-for="item in [...primary, ...more]"
                    :key="item.route + item.label"
                    :href="item.href"
                    :class="isActive(item) ? 'bg-brand-600 text-white' : 'text-ink hover:bg-brand-50'"
                    @click.capture="onItemClick(item)"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 font-semibold"
                >
                    <Icon :name="item.icon" :size="18" /> {{ item.label }}
                </Link>
            </nav>
        </Transition>
    </header>
</template>
