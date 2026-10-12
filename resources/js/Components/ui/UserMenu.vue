<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';

// Avatar, name and role with My profile / Sign out (shared by both layouts).
const user = computed(() => usePage().props.auth.user);
const roleTone = {
    admin: 'bg-brand-50 text-brand-700',
    teacher: 'bg-accent-50 text-accent-700',
    student: 'bg-sky-soft text-sky-ink',
    guardian: 'bg-success-soft text-emerald-700',
};
</script>

<template>
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
</template>
