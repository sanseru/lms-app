<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';
import LevelCard from '@/Components/ui/LevelCard.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import BadgeTile from '../Components/BadgeTile.vue';

const props = defineProps({ student: Object, self: Boolean, summary: Object, leaderboards: Array, children: Array });
const earned = computed(() => props.summary.badges.filter((b) => b.earned_at).length);
const medal = ['🥇', '🥈', '🥉'];
</script>

<template>
    <Head :title="self ? 'My achievements' : `${student.name}'s achievements`" />

    <PageHeader eyebrow="Learning" :title="self ? 'My achievements' : `${student.name}'s achievements`" :description="self ? 'Collect XP by learning, coming to class, working on projects and helping friends.' : 'XP and badges are worked out automatically from learning activity.'">
        <template v-if="children.length > 1" #actions>
            <Link v-for="c in children" :key="c.id" :href="route('gamification.show', c.id)" :class="c.id === student.id ? 'btn-primary' : 'btn-ghost'">{{ c.name.split(' ')[0] }}</Link>
        </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <LevelCard :level="summary.level" />

            <div class="grid grid-cols-3 gap-3">
                <div class="card p-4 text-center"><p class="font-display text-2xl font-black text-ink">{{ summary.level.xp }}</p><p class="text-xs text-muted">total XP</p></div>
                <div class="card p-4 text-center"><p class="font-display text-2xl font-black text-accent-600">+{{ summary.xp_month }}</p><p class="text-xs text-muted">XP this month</p></div>
                <div class="card p-4 text-center"><p class="font-display text-2xl font-black text-brand-600">{{ earned }}/{{ summary.badges.length }}</p><p class="text-xs text-muted">badges</p></div>
            </div>

            <div>
                <h2 class="mb-3 font-display text-lg font-bold text-ink">Badges</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    <BadgeTile v-for="b in summary.badges" :key="b.key" :badge="b" />
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div v-for="board in leaderboards" :key="board.class.id" class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <p class="eyebrow">Class stars this month</p>
                    <p class="font-display font-bold text-ink">{{ board.class.name }}</p>
                </div>
                <ol class="divide-y divide-line">
                    <li v-for="(p, i) in board.top" :key="p.id" :class="p.me && 'bg-brand-50'" class="flex items-center gap-3 px-5 py-2.5">
                        <span class="w-6 text-center text-lg">{{ medal[i] ?? i + 1 }}</span>
                        <Avatar :initials="p.initials" :seed="p.id" size="sm" />
                        <span class="flex-1 text-sm font-semibold text-ink">{{ p.name }}<span v-if="p.me" class="text-brand-600"> (you)</span></span>
                        <span class="text-xs text-muted">Lv {{ p.level }}</span>
                        <span class="w-16 text-right text-sm font-bold text-ink">{{ p.xp }} XP</span>
                    </li>
                    <li v-if="!board.top.length" class="px-5 py-6 text-center text-sm text-muted">No XP yet this month. Let's get started!</li>
                </ol>
                <p v-if="board.my_rank && board.my_rank > 5" class="border-t border-line px-5 py-3 text-sm text-muted">{{ self ? 'You are' : `${student.name.split(' ')[0]} is` }} ranked {{ board.my_rank }} of {{ board.total }}.</p>
            </div>

            <div class="card overflow-hidden">
                <p class="border-b border-line px-5 py-4 font-display font-bold text-ink">Latest XP</p>
                <ul class="divide-y divide-line">
                    <li v-for="(e, i) in summary.recent" :key="i" class="flex items-center gap-3 px-5 py-2.5 text-sm">
                        <span class="min-w-0 flex-1"><span class="block truncate text-ink">{{ e.label }}</span><span class="text-xs text-muted">{{ e.at }}</span></span>
                        <span class="w-12 text-right font-bold text-emerald-700">+{{ e.points }}</span>
                    </li>
                    <li v-if="!summary.recent.length" class="px-5 py-6 text-center text-sm text-muted">No XP yet.</li>
                </ul>
            </div>

            <div class="card p-5">
                <p class="mb-3 font-display font-bold text-ink">How to earn XP</p>
                <ul class="space-y-2 text-sm">
                    <li v-for="r in summary.by_rule" :key="r.label" class="flex items-center gap-2">
                        <Icon name="sparkles" :size="14" class="text-amber-500" />
                        <span class="flex-1 text-ink">{{ r.label }}</span>
                        <span class="text-xs text-muted">{{ r.count }}×</span>
                        <span class="w-12 text-right font-semibold text-brand-600">+{{ r.points }}</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</template>
