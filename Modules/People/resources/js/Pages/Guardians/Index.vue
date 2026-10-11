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
const relationLabel = { ayah: 'Ayah', ibu: 'Ibu', wali: 'Wali' };
</script>

<template>
    <Head title="Orang Tua" />

    <PageHeader eyebrow="Pengguna" title="Orang tua" description="Akun orang tua untuk memantau jadwal dan membayar tagihan anak.">
        <template #actions>
            <Link :href="route('people.guardians.create')" class="btn-accent"><Icon name="user-plus" :size="18" /> Tambah orang tua</Link>
        </template>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="border-b border-line p-4">
            <SearchInput v-model="filters.search" placeholder="Cari nama, HP, atau nama anak…" />
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
                    <span v-if="!guardian.children.length" class="text-xs font-semibold text-amber-700">Belum ada anak terhubung</span>
                </div>
                <div class="flex gap-1">
                    <Link :href="route('people.guardians.edit', guardian.id)" class="rounded-lg p-2 text-muted hover:bg-brand-50 hover:text-brand-600" title="Ubah"><Icon name="pencil" :size="16" /></Link>
                    <button
                        class="rounded-lg p-2 text-muted hover:bg-rose-50 hover:text-rose-600"
                        title="Hapus"
                        @click="confirm.ask({ title: `Hapus ${guardian.name}?`, message: 'Hubungan dengan data anak juga akan dilepas.', url: route('people.guardians.destroy', guardian.id) })"
                    >
                        <Icon name="trash" :size="16" />
                    </button>
                </div>
            </li>
        </ul>

        <EmptyState v-else icon="users" title="Belum ada orang tua" description="Tambahkan akun orang tua lalu hubungkan dengan anaknya." />

        <Pagination :meta="guardians" />
    </div>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
