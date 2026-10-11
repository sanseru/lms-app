<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({ guardians: Object, filters: Object });

const filters = useFilters({ search: props.filters.search ?? '' }, 'people.guardians.index');
const confirm = useConfirm();
const relationLabel = { ayah: 'Father', ibu: 'Mother', wali: 'Guardian' };
</script>

<template>
    <Head title="Parents" />

    <PageHeader eyebrow="People" title="Parents" description="Parent accounts for following their child's schedule and paying invoices.">
        <template #actions>
            <Link :href="route('people.guardians.create')" class="btn-accent"><Icon name="user-plus" :size="18" /> Add parent</Link>
        </template>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="border-b border-line p-4">
            <SearchInput v-model="filters.search" placeholder="Search by name, phone or child's name…" />
        </div>

        <ul v-if="guardians.data.length" class="divide-y divide-line">
            <li v-for="guardian in guardians.data" :key="guardian.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <Avatar :initials="guardian.initials" :seed="guardian.id" />
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">
                            {{ guardian.name }}
                            <span v-if="guardian.relationship" class="ml-1 rounded-full bg-chip px-2 py-0.5 text-xs font-semibold text-muted">{{ relationLabel[guardian.relationship] }}</span>
                        </p>
                        <p class="text-sm text-muted">{{ guardian.phone }}<template v-if="guardian.email"> · {{ guardian.email }}</template></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 sm:w-72">
                    <span v-for="child in guardian.children" :key="child.id" class="rounded-full bg-sky-soft px-2.5 py-1 text-xs font-semibold text-sky-ink">{{ child.name }}</span>
                    <span v-if="!guardian.children.length" class="text-xs font-semibold text-amber-700">No children linked yet</span>
                </div>
                <div class="flex gap-1">
                    <Link :href="route('people.guardians.edit', guardian.id)" class="rounded-lg p-2 text-muted hover:bg-brand-50 hover:text-brand-600" title="Edit"><Icon name="pencil" :size="16" /></Link>
                    <button
                        class="rounded-lg p-2 text-muted hover:bg-rose-50 hover:text-rose-600"
                        title="Delete"
                        @click="confirm.ask({ title: `Delete ${guardian.name}?`, message: 'Their links to children will also be removed.', url: route('people.guardians.destroy', guardian.id) })"
                    >
                        <Icon name="trash" :size="16" />
                    </button>
                </div>
            </li>
        </ul>

        <EmptyState v-else icon="users" title="No parents yet" description="Add a parent account, then link it to their children." />

        <Pagination :meta="guardians" />
    </div>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
