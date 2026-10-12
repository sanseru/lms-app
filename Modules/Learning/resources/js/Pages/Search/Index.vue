<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ q: String, courses: Array, lessons: Array });

const term = ref(props.q);
const submit = () => router.get(route('learning.search'), { q: term.value }, { preserveState: true, replace: true });
// Own courses open the course map; the rest open the catalog preview.
const courseHref = (c) => (c.mine ? route('learning.courses.index', { course: c.id }) : route('learning.courses.show', c.id));
</script>

<template>
    <Head :title="q ? `Search: ${q}` : 'Search'" />
    <PageHeader eyebrow="Search" title="Find courses and lessons" />

    <form class="mb-6 flex max-w-2xl gap-2" role="search" @submit.prevent="submit">
        <label for="search-page" class="sr-only">Search for courses or lessons</label>
        <input id="search-page" v-model="term" type="search" class="field" placeholder="Search for courses or lessons..." minlength="2" maxlength="80" autofocus />
        <button type="submit" class="btn-primary shrink-0"><Icon name="search" :size="16" /> Search</button>
    </form>

    <template v-if="q.length >= 2">
        <div v-if="courses.length || lessons.length" class="grid gap-6 lg:grid-cols-2">
            <section aria-labelledby="found-courses">
                <h2 id="found-courses" class="mb-3 font-display text-lg font-extrabold text-ink">Courses <span class="text-muted">({{ courses.length }})</span></h2>
                <ul v-if="courses.length" class="space-y-2">
                    <li v-for="c in courses" :key="c.id">
                        <Link :href="courseHref(c)" class="card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            <span :class="(classColors[c.color] ?? classColors.violet).solid" class="size-3 shrink-0 rounded-full" />
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink">{{ c.title }}</span>
                                <span class="block truncate text-sm text-muted">{{ [c.program, c.description].filter(Boolean).join(' · ') }}</span>
                            </span>
                            <span v-if="c.mine" class="rounded-full bg-success-soft px-2.5 py-0.5 text-xs font-bold text-success-ink">Your course</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">No courses match.</p>
            </section>
            <section aria-labelledby="found-lessons">
                <h2 id="found-lessons" class="mb-3 font-display text-lg font-extrabold text-ink">Lessons <span class="text-muted">({{ lessons.length }})</span></h2>
                <ul v-if="lessons.length" class="space-y-2">
                    <li v-for="l in lessons" :key="l.id">
                        <Link :href="route('learning.lessons.show', l.id)" class="card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><Icon :name="l.icon" :size="18" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink">{{ l.title }}</span>
                                <span class="block truncate text-sm text-muted">{{ [l.course, l.unit, l.type_label].filter(Boolean).join(' · ') }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">No lessons in your courses match.</p>
            </section>
        </div>
        <div v-else class="card">
            <EmptyState icon="search" title="Nothing found" :description="`No course or lesson matches “${q}”. Try another word, or browse the catalog.`">
                <Link :href="route('learning.courses.explore')" class="btn-primary"><Icon name="compass" :size="16" /> Course catalog</Link>
            </EmptyState>
        </div>
    </template>
    <p v-else class="text-sm text-muted">Type at least 2 letters, for example “loop” or “Python”.</p>
</template>
