<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import LastSessionCard from '@/Components/ui/LastSessionCard.vue';
import LearningCard from '@/Components/ui/LearningCard.vue';
import QuotaCard from '@/Components/ui/QuotaCard.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import { greeting, rupiah, greetName, gradeLabel } from '@/lib/format';

defineProps({ children: Array, invoices: Array });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Home" />

    <div class="mb-8">
        <p class="eyebrow">Parent portal</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink">{{ greeting() }}, {{ greetName(user.name) }}</h1>
        <p class="mt-1 text-muted">Follow your child's schedule, attendance, remaining sessions and invoices here.</p>
    </div>

    <!-- Invoices -->
    <div v-if="invoices.length" class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50">
        <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white text-amber-600"><Icon name="receipt" /></span>
                <div>
                    <p class="font-display font-bold text-ink">{{ invoices.length }} {{ invoices.length === 1 ? 'invoice' : 'invoices' }} waiting for payment</p>
                    <p class="text-sm text-muted">Make a transfer, then send the proof so we can verify it quickly.</p>
                </div>
            </div>
            <Link :href="route('finance.invoices.index')" class="hidden text-sm font-semibold text-amber-800 hover:underline sm:block">All invoices →</Link>
        </div>
        <ul class="divide-y divide-amber-200/70 border-t border-amber-200 bg-white">
            <li v-for="inv in invoices" :key="inv.id">
                <Link :href="route('finance.invoices.show', inv.id)" class="flex items-center gap-3 px-5 py-3 hover:bg-amber-50/50">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-ink">{{ inv.title }} · {{ inv.student }}</p>
                        <p :class="inv.is_overdue ? 'font-semibold text-rose-600' : 'text-muted'" class="text-xs">Due {{ inv.due_date }}<template v-if="inv.is_overdue"> (overdue)</template></p>
                    </div>
                    <p class="font-display font-bold text-ink">{{ rupiah(inv.remaining) }}</p>
                    <Icon name="chevron-right" class="text-muted" />
                </Link>
            </li>
        </ul>
    </div>

    <!-- Children -->
    <div v-if="children.length" class="space-y-6">
        <div v-for="child in children" :key="child.id" class="card p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-center gap-4">
                <Avatar :initials="child.initials" :seed="child.id" size="lg" />
                <div class="flex-1">
                    <p class="font-display text-lg font-bold text-ink">{{ child.name }}</p>
                    <p class="text-sm text-muted"><span class="font-mono">{{ child.code }}</span><template v-if="child.grade"> · {{ gradeLabel(child.grade) }}</template></p>
                </div>
                <Link v-if="child.achievements" :href="route('gamification.show', child.id)" class="flex items-center gap-2 rounded-2xl bg-brand-50 px-3 py-2 hover:bg-brand-100">
                    <span class="grid size-9 place-items-center rounded-xl bg-white text-brand-600"><Icon name="star" :size="18" /></span>
                    <span class="text-sm leading-tight"><b class="block text-ink">Level {{ child.achievements.level.level }}</b><span class="text-xs text-muted">{{ child.achievements.level.xp }} XP · {{ child.achievements.badges_earned }} {{ child.achievements.badges_earned === 1 ? 'badge' : 'badges' }}</span></span>
                </Link>
                <div v-if="child.stats.sessions" class="text-right">
                    <p class="font-display text-2xl font-extrabold text-ink">{{ child.stats.rate }}%</p>
                    <p class="text-xs text-muted">attendance over {{ child.stats.sessions }} {{ child.stats.sessions === 1 ? 'session' : 'sessions' }}</p>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div>
                    <p class="eyebrow mb-2">Package quota</p>
                    <div v-if="child.packages.length" class="space-y-3"><QuotaCard v-for="p in child.packages" :key="p.id" :pkg="p" /></div>
                    <p v-else class="text-sm text-muted">No active package yet.</p>
                </div>
                <div>
                    <p class="eyebrow mb-2">Next sessions</p>
                    <div v-if="child.next.length" class="space-y-2">
                        <SessionItem v-for="s in child.next" :key="s.id" :session="s" show-date />
                    </div>
                    <p v-else class="text-sm text-muted">No sessions scheduled yet.</p>
                </div>
                <div>
                    <p class="eyebrow mb-2">Last session notes</p>
                    <LastSessionCard v-if="child.lastSession" :session="child.lastSession" for-parent />
                    <p v-else class="text-sm text-muted">No finished sessions yet.</p>
                </div>
            </div>

            <div v-if="child.learning.length" class="mt-5 border-t border-line pt-5">
                <div class="mb-3 flex items-center justify-between">
                    <p class="eyebrow">Learning progress</p>
                    <Link :href="route('learning.progress.show', child.id)" class="text-sm font-semibold text-brand-600 hover:underline">View report →</Link>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <LearningCard v-for="c in child.learning" :key="c.id" :course="c" :link-next="false" />
                </div>
            </div>
        </div>
    </div>
    <div v-else class="card">
        <EmptyState icon="users" title="No children linked yet" description="Ask your admin to link your account to your child." />
    </div>
</template>
