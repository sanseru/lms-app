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
const sidebarOpen = ref(false);

router.on('navigate', () => (sidebarOpen.value = false));

const isActive = (pattern) => pattern.split('|').some((p) => route().current(p));

// Toast dari flash message server.
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
    student: 'bg-sky-soft text-sky',
    guardian: 'bg-success-soft text-emerald-700',
};
</script>

<template>
    <div class="min-h-full">
        <!-- Overlay mobile -->
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
                <button class="rounded-lg p-2 text-muted hover:bg-chip lg:hidden" @click="sidebarOpen = false">
                    <Icon name="x" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-4">
                <div v-for="group in navigation" :key="group.label">
                    <p class="mb-2 px-3 text-[11px] font-extrabold uppercase tracking-[0.12em] text-muted/80">{{ group.label }}</p>
                    <ul class="space-y-1">
                        <li v-for="item in group.items" :key="item.href + item.label">
                            <Link
                                :href="item.href"
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

            <div class="m-4 rounded-2xl bg-gradient-to-br from-brand-600 via-violet-500 to-accent-500 p-4 text-white">
                <p class="font-display text-sm font-bold">Learn. Create. Grow.</p>
                <p class="mt-1 text-xs text-white/80">Modul tanya jawab, absensi, dan peta belajar akan hadir di sini.</p>
            </div>
        </aside>

        <!-- Konten -->
        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button class="rounded-lg p-2 text-ink hover:bg-chip lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">
                    <Icon name="menu" />
                </button>
                <img src="/images/logo-header.png" alt="" class="h-8 w-auto lg:hidden" />

                <div class="ml-auto flex items-center gap-2">
                    <button class="relative grid size-10 place-items-center rounded-xl border border-line text-muted hover:bg-chip hover:text-ink" aria-label="Notifikasi">
                        <Icon name="bell" />
                        <span class="absolute top-2 right-2 size-2 rounded-full bg-accent-500 ring-2 ring-white" />
                    </button>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-3 rounded-xl py-1 pr-2 pl-1 hover:bg-chip">
                                <Avatar :initials="user.initials" :seed="user.id" size="sm" />
                                <span class="hidden text-left sm:block">
                                    <span class="block text-sm font-bold leading-tight text-ink">{{ user.name }}</span>
                                    <span :class="roleTone[user.role]" class="mt-0.5 inline-block rounded-full px-2 text-[11px] font-bold">{{ user.role_label }}</span>
                                </span>
                                <Icon name="chevron-down" :size="16" class="text-muted" />
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profil saya</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Keluar</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
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
                    :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                    class="grid size-8 shrink-0 place-items-center rounded-lg"
                >
                    <Icon :name="toast.type === 'success' ? 'check' : 'alert-triangle'" :size="16" />
                </span>
                <p class="pt-1 text-sm font-medium text-ink">{{ toast.message }}</p>
                <button class="ml-1 text-muted hover:text-ink" @click="toast = null"><Icon name="x" :size="16" /></button>
            </div>
        </Transition>
    </div>
</template>
