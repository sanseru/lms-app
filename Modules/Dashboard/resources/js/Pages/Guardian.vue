<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import { greeting, rupiah } from '@/lib/format';

defineProps({ children: Array, invoices: Array });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Beranda" />

    <div class="mb-8">
        <p class="eyebrow">Portal orang tua</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink">{{ greeting() }}, {{ user.name.split(' ')[0] }}</h1>
        <p class="mt-1 text-muted">Pantau jadwal kelas dan tagihan anak Anda di sini.</p>
    </div>

    <!-- Tagihan -->
    <div v-if="invoices.length" class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50">
        <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white text-amber-600"><Icon name="receipt" /></span>
                <div>
                    <p class="font-display font-bold text-ink">{{ invoices.length }} tagihan menunggu pembayaran</p>
                    <p class="text-sm text-muted">Transfer lalu kirim bukti agar segera diverifikasi.</p>
                </div>
            </div>
            <Link :href="route('finance.invoices.index')" class="hidden text-sm font-semibold text-amber-800 hover:underline sm:block">Semua tagihan →</Link>
        </div>
        <ul class="divide-y divide-amber-200/70 border-t border-amber-200 bg-white">
            <li v-for="inv in invoices" :key="inv.id">
                <Link :href="route('finance.invoices.show', inv.id)" class="flex items-center gap-3 px-5 py-3 hover:bg-amber-50/50">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-ink">{{ inv.title }} · {{ inv.student }}</p>
                        <p :class="inv.is_overdue ? 'font-semibold text-rose-600' : 'text-muted'" class="text-xs">Jatuh tempo {{ inv.due_date }}<template v-if="inv.is_overdue"> (terlambat)</template></p>
                    </div>
                    <p class="font-display font-bold text-ink">{{ rupiah(inv.remaining) }}</p>
                    <Icon name="chevron-right" class="text-muted" />
                </Link>
            </li>
        </ul>
    </div>

    <!-- Anak -->
    <div v-if="children.length" class="grid gap-6 lg:grid-cols-2">
        <div v-for="child in children" :key="child.id" class="card p-5">
            <div class="mb-4 flex items-center gap-3">
                <Avatar :initials="child.initials" :seed="child.id" size="lg" />
                <div>
                    <p class="font-display text-lg font-bold text-ink">{{ child.name }}</p>
                    <p class="text-sm text-muted"><span class="font-mono">{{ child.code }}</span><template v-if="child.grade"> · Kelas {{ child.grade }}</template></p>
                </div>
            </div>
            <p class="eyebrow mb-2">Kelas yang diikuti</p>
            <div v-if="child.classes.length" class="space-y-2">
                <SessionItem v-for="c in child.classes" :key="c.id" :session="{ ...c, subject: c.schedule }" :show-teacher="false" />
            </div>
            <p v-else class="text-sm text-muted">Belum terdaftar di kelas.</p>
        </div>
    </div>
    <div v-else class="card">
        <EmptyState icon="users" title="Belum ada anak terhubung" description="Hubungi admin untuk menghubungkan akun Anda dengan data anak." />
    </div>
</template>
