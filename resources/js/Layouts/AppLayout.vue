<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const navigation = computed(() => page.props.navigation ?? []);
const notifications = computed(() => page.props.notifications ?? { unread: 0, latest: [] });
const sidebarOpen = ref(false);

router.on('navigate', () => (sidebarOpen.value = false));

const isActive = (pattern) => pattern.split('|').some((p) => route().current(p));

// Toast from the server's flash message.
const toast = ref(null);
let timer;
watch(
    () => page.props.flash,
    (flash) => {
        const message = flash?.success ?? flash?.error;
        if (!message) return;
        toast.value = { type: flash.success ? 'success' : 'error', message };
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 4500);
    },
    { immediate: true, deep: true },
);

const roleTone = {
    admin: 'bg-brand-50 text-brand-700',
    teacher: 'bg-accent-50 text-accent-700',
    student: 'bg-sky-soft text-sky-ink',
    guardian: 'bg-success-soft text-emerald-700',
};
</script>

<template>
    <div class="min-h-full">
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
                    <img src="/images/logo-header.png" alt="Alprogmatics" class="h-11 w-auto" />
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
                <img src="/images/logo-header.png" alt="" class="h-8 w-auto lg:hidden" />

                <div class="ml-auto flex items-center gap-2">
                    <Dropdown align="right" width="80" content-classes="bg-white">
                        <template #trigger>
                            <button
                                class="relative grid size-11 place-items-center rounded-xl border border-line text-muted hover:bg-chip hover:text-ink"
                                :aria-label="notifications.unread ? `Notifications, ${notifications.unread} unread` : 'Notifications'"
                            >
                                <Icon name="bell" />
                                <span
                                    v-if="notifications.unread"
                                    class="absolute -top-1.5 -right-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-accent-500 px-1 text-xs font-extrabold text-ink ring-2 ring-white"
                                >
                                    {{ notifications.unread > 9 ? '9+' : notifications.unread }}
                                </span>
                            </button>
                        </template>
                        <template #content>
                            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                                <p class="font-display text-sm font-bold text-ink">Notifications</p>
                                <button
                                    v-if="notifications.unread"
                                    class="text-xs font-semibold text-brand-600 hover:underline"
                                    @click="router.post(route('notifications.read-all'), {}, { preserveScroll: true })"
                                >
                                    Mark all as read
                                </button>
                            </div>
                            <ul v-if="notifications.latest.length" class="max-h-96 divide-y divide-line overflow-y-auto">
                                <li v-for="n in notifications.latest" :key="n.id">
                                    <Link :href="route('notifications.open', n.id)" class="flex gap-3 px-4 py-3 hover:bg-chip/60" :class="!n.read && 'bg-brand-50/60'">
                                        <span :class="n.read ? 'bg-chip text-muted' : 'bg-brand-600 text-white'" class="grid size-8 shrink-0 place-items-center rounded-lg">
                                            <Icon :name="n.icon" :size="15" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-ink">{{ n.title }}</span>
                                            <span v-if="n.body" class="line-clamp-2 block text-xs text-muted">{{ n.body }}</span>
                                            <span class="mt-0.5 block text-xs text-muted">{{ n.time }}</span>
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                            <p v-else class="px-4 py-8 text-center text-sm text-muted">No notifications yet.</p>
                            <Link :href="route('notifications.index')" class="block border-t border-line px-4 py-2.5 text-center text-sm font-semibold text-brand-600 hover:bg-chip/60">
                                View all
                            </Link>
                        </template>
                    </Dropdown>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex min-h-11 items-center gap-3 rounded-xl py-1 pr-2 pl-1 hover:bg-chip">
                                <span class="sr-only sm:hidden">Account menu</span>
                                <Avatar :initials="user.initials" :seed="user.id" size="sm" />
                                <span class="hidden text-left sm:block">
                                    <span class="block text-sm font-bold leading-tight text-ink">{{ user.name }}</span>
                                    <span :class="roleTone[user.role]" class="mt-0.5 inline-block rounded-full px-2 text-xs font-bold">{{ user.role_label }}</span>
                                </span>
                                <Icon name="chevron-down" :size="16" class="text-muted" />
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">My profile</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Sign out</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <main id="content" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <slot />
            </main>
        </div>

        <!-- Toast -->
        <Transition
            enter-from-class="translate-y-4 opacity-0"
            leave-to-class="translate-y-4 opacity-0"
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
        >
            <div
                v-if="toast"
                role="status"
                class="fixed right-4 bottom-4 z-[60] flex max-w-sm items-start gap-3 rounded-2xl border bg-white p-4 shadow-xl shadow-ink/10"
                :class="toast.type === 'success' ? 'border-emerald-200' : 'border-rose-200'"
            >
                <span
                    :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600'"
                    class="grid size-8 shrink-0 place-items-center rounded-lg"
                >
                    <Icon :name="toast.type === 'success' ? 'check' : 'alert-triangle'" :size="16" />
                </span>
                <p class="pt-1 text-sm font-medium text-ink">{{ toast.message }}</p>
                <button class="-m-1 ml-0 grid size-8 shrink-0 place-items-center rounded-lg text-muted hover:bg-chip hover:text-ink" aria-label="Dismiss notification" @click="toast = null"><Icon name="x" :size="16" /></button>
            </div>
        </Transition>
    </div>
</template>
