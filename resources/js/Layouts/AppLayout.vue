<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Icon from '@/Components/ui/Icon.vue';
import NotificationBell from '@/Components/ui/NotificationBell.vue';
import UserMenu from '@/Components/ui/UserMenu.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const page = usePage();
const navigation = computed(() => page.props.navigation ?? []);
// Students get the client's top-menu layout; staff and parents keep the sidebar.
const isStudent = computed(() => page.props.auth?.user?.role === 'student');
const sidebarOpen = ref(false);

router.on('navigate', () => (sidebarOpen.value = false));

const isActive = (pattern) => pattern.split('|').some((p) => route().current(p));
</script>

<template>
    <StudentLayout v-if="isStudent"><slot /></StudentLayout>
    <div v-else class="min-h-full">
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:font-bold focus:shadow-lg">Skip to content</a>
        <!-- Mobile overlay -->
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-[2px] lg:hidden" @click="sidebarOpen = false" />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-line bg-white transition-transform duration-200"
        >
            <div class="flex h-20 items-center justify-between px-6">
                <Link :href="route('dashboard')" class="flex items-center">
                    <img src="/images/logo-header.png" alt="Alprogmatics" class="h-14 w-auto" />
                </Link>
                <button class="grid size-11 place-items-center rounded-lg text-muted hover:bg-chip lg:hidden" aria-label="Close menu" @click="sidebarOpen = false">
                    <Icon name="x" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-4">
                <div v-for="group in navigation" :key="group.label">
                    <p class="mb-2 px-3 text-xs font-bold text-muted">{{ group.label }}</p>
                    <ul class="space-y-1">
                        <li v-for="item in group.items" :key="item.href + item.label">
                            <Link
                                :href="item.href"
                                :aria-current="isActive(item.active) ? 'page' : undefined"
                                :class="
                                    isActive(item.active)
                                        ? 'bg-brand-600 text-white shadow-[0_3px_0_var(--color-brand-800)]'
                                        : 'text-ink/80 hover:bg-brand-50 hover:text-brand-700'
                                "
                                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            >
                                <Icon :name="item.icon" :size="18" />
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

        </aside>

        <!-- Content -->
        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button class="grid size-11 place-items-center rounded-lg text-ink hover:bg-chip lg:hidden" :aria-expanded="sidebarOpen" @click="sidebarOpen = true" aria-label="Open menu">
                    <Icon name="menu" />
                </button>
                <img src="/images/logo-header.png" alt="" class="h-11 w-auto lg:hidden" />

                <div class="ml-auto flex items-center gap-2">
                    <NotificationBell />
                    <UserMenu />
                </div>
            </header>

            <main id="content" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <slot />
            </main>
        </div>

        <FlashToast />
    </div>
</template>
