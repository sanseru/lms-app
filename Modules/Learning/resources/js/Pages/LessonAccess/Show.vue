<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

const props = defineProps({ classRoom: Object, levels: Array });

const toggle = (level, node) =>
    router.put(route('learning.access.update', props.classRoom.id), { course_id: level.id, node_key: node.key, open: !node.opened }, { preserveScroll: true });
const setAll = (level, open) => router.put(route('learning.access.all', props.classRoom.id), { course_id: level.id, open }, { preserveScroll: true });
const openedCount = (level) => level.nodes.filter((n) => n.opened).length;
</script>

<template>
    <Head :title="`Lesson access · ${classRoom.name}`" />
    <PageHeader
        eyebrow="Lesson access"
        :title="classRoom.name"
        description="Students open lessons one after another, and a level after the one before it is finished. Switch a lesson on to open it early for this whole class, also in a later level. Switch it off to go back to step by step."
    >
        <template #back>
            <Link :href="route('classes.show', classRoom.id)" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600"><Icon name="arrow-left" :size="16" /> Back to the class</Link>
        </template>
    </PageHeader>

    <div v-if="levels.length" class="space-y-5">
        <section v-for="(level, li) in levels" :key="level.id" class="card overflow-hidden" :aria-labelledby="`level-${level.id}`">
            <header class="flex flex-wrap items-center gap-3 border-b border-line p-5">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold tracking-wide text-muted uppercase">Level {{ level.level_order ?? li + 1 }}<template v-if="level.current"> · this class's course</template></p>
                    <h2 :id="`level-${level.id}`" class="font-display text-lg font-extrabold text-ink">{{ level.title }}</h2>
                    <p class="text-sm text-muted">{{ openedCount(level) }}/{{ level.nodes.length }} lessons opened early</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-ghost px-3 py-1.5 text-sm" :disabled="openedCount(level) === level.nodes.length" @click="setAll(level, true)"><Icon name="eye" :size="15" /> Open all</button>
                    <button type="button" class="btn-ghost px-3 py-1.5 text-sm" :disabled="!openedCount(level)" @click="setAll(level, false)"><Icon name="lock" :size="15" /> Lock all</button>
                </div>
            </header>
            <ul v-if="level.nodes.length" class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4 lg:grid-cols-6">
                <li v-for="node in level.nodes" :key="node.key">
                    <button
                        type="button"
                        :aria-pressed="node.opened"
                        :class="node.opened ? 'border-success bg-success-soft' : 'border-line bg-white hover:border-brand-300'"
                        class="flex h-full w-full flex-col items-start gap-1 rounded-2xl border-2 p-3 text-left transition"
                        @click="toggle(level, node)"
                    >
                        <span class="font-display font-extrabold text-ink">Lesson {{ node.number }}</span>
                        <span class="line-clamp-2 text-xs text-muted">{{ node.title }}</span>
                        <span :class="node.opened ? 'text-success-ink' : 'text-muted'" class="mt-auto inline-flex items-center gap-1 pt-1 text-xs font-bold">
                            <Icon :name="node.opened ? 'eye' : 'lock'" :size="12" /> {{ node.opened ? 'Open' : 'Step by step' }}
                        </span>
                    </button>
                </li>
            </ul>
            <p v-else class="px-5 py-6 text-center text-sm text-muted">No published lessons in this level yet.</p>
        </section>
    </div>
    <div v-else class="card">
        <EmptyState icon="book-open" title="This class has no course yet" description="Attach a course to the class first, then you can open its lessons here." />
    </div>
</template>
