<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import Icon from '@/Components/ui/Icon.vue';

// Bell with the latest notifications (shared by the sidebar and the student header layouts).
const page = usePage();
const notifications = computed(() => page.props.notifications ?? { unread: 0, latest: [] });
</script>

<template>
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
</template>
