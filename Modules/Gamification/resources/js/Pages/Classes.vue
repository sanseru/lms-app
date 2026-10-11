<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { classColors } from '@/lib/format';

defineProps({ classes: Array, current: Number, rows: Array });
const pick = (id) => router.get(route('gamification.classes'), { class: id }, { preserveScroll: true });
</script>

<template>
    <Head title="Student achievements" />

    <PageHeader eyebrow="Learning" title="Student achievements" description="Student levels, XP and badges per class. XP is worked out automatically from attendance, lessons, quizzes, assignments, coding exercises, projects and Q&A." />

    <div v-if="classes.length" class="mb-5 flex flex-wrap gap-2">
        <button
            v-for="c in classes"
            :key="c.id"
            :class="c.id === current ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
            @click="pick(c.id)"
        >
            <span :class="(classColors[c.color] ?? classColors.violet).dot" class="size-2 rounded-full" /> {{ c.name }}
        </button>
    </div>

    <div v-if="rows.length" class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-chip/60 text-left text-xs text-muted uppercase">
                <tr><th class="px-5 py-3">#</th><th class="px-3 py-3">Student</th><th class="hidden px-3 py-3 sm:table-cell">Level</th><th class="px-3 py-3 text-right">XP</th><th class="hidden px-3 py-3 text-right md:table-cell">This month</th><th class="hidden px-5 py-3 lg:table-cell">Badges</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                <tr v-for="(r, i) in rows" :key="r.id" class="hover:bg-chip/30">
                    <td class="px-5 py-3 font-bold text-muted">{{ i + 1 }}</td>
                    <td class="px-3 py-3">
                        <Link :href="route('gamification.show', r.id)" class="flex items-center gap-3 font-semibold text-ink hover:text-brand-600">
                            <Avatar :initials="r.initials" :seed="r.id" size="sm" /> {{ r.name }}
                        </Link>
                    </td>
                    <td class="hidden w-56 px-3 py-3 sm:table-cell">
                        <p class="text-xs font-semibold text-ink">Lv {{ r.level.level }} · {{ r.level.name }}</p>
                        <ProgressBar :value="r.level.percent" size="sm" class="mt-1" />
                    </td>
                    <td class="px-3 py-3 text-right font-display font-bold text-ink">{{ r.level.xp }}</td>
                    <td class="hidden px-3 py-3 text-right text-emerald-700 md:table-cell">+{{ r.xp_month }}</td>
                    <td class="hidden px-5 py-3 text-lg lg:table-cell">{{ r.badges.join(' ') || '–' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div v-else class="card"><EmptyState icon="trophy" title="No students yet" description="Students enrolled in the class will show up here." /></div>
</template>
