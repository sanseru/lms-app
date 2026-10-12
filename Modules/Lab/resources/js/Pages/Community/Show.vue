<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Heart, MessageCircle } from 'lucide-vue-next';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Icon from '@/Components/ui/Icon.vue';
import CodeEditor from '../../../../../Coding/resources/js/Components/CodeEditor.vue';
import ProjectThumb from '../../Components/ProjectThumb.vue';
import RunPanel from '../../Components/RunPanel.vue';
import ScratchFrame from '../../Components/ScratchFrame.vue';

const props = defineProps({ project: Object, comments: Array, runtime: Object, can: Object });

// Code is open for code projects; websites and slides show their result first.
const showCode = ref(!['html', 'presentation'].includes(props.project.language));
const comment = useForm({ body: '' });
const post = () => comment.post(route('community.comments.store', props.project.id), { preserveScroll: true, onSuccess: () => comment.reset() });
const like = () => router.post(route('community.like', props.project.id), {}, { preserveScroll: true, preserveState: true });
const removing = ref(null);
const removeComment = () => router.delete(route('community.comments.destroy', removing.value), { preserveScroll: true, onFinish: () => (removing.value = null) });
const hiding = ref(false);
const hide = () => router.post(route('community.hide', props.project.id));
const restore = () => router.delete(route('community.restore', props.project.id), { preserveScroll: true });

const when = (iso) => new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head :title="`${project.title} · Community`" />

    <Link :href="route('community.index')" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-brand-600 hover:text-brand-700"><Icon name="arrow-left" :size="16" /> Community</Link>

    <p v-if="project.hidden" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-accent-50 px-4 py-3 text-sm font-semibold text-accent-700">
        <span class="flex items-center gap-2"><Icon name="eye-off" :size="18" /> Removed from the Community. Only you and teachers can see it.</span>
        <button v-if="can.moderate" type="button" class="btn-ghost" @click="restore">Put it back</button>
    </p>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-4">
            <div class="card overflow-hidden">
                <ProjectThumb :language="project.language" compact><template #label>{{ project.language_label }}</template></ProjectThumb>
                <div class="p-5">
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-ink">{{ project.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted">
                        <span class="flex items-center gap-2"><Avatar :initials="project.author.initials" :seed="project.author.id" size="sm" /><b class="text-ink">{{ project.author.name }}</b></span>
                        <span v-if="project.shared_at">Shared {{ when(project.shared_at) }}</span>
                    </div>
                    <p v-if="project.description" class="mt-3 text-ink">{{ project.description }}</p>
                </div>
            </div>

            <!-- Scratch: the player (green flag) for the shared project -->
            <template v-if="project.language === 'scratch'">
                <ScratchFrame v-if="project.file_url" :src="project.file_url" mode="player" :title="`${project.title}, a Scratch project`" class="aspect-[4/3.3] max-h-[78vh] w-full" />
                <p v-else class="card p-5 text-sm text-muted">This Scratch project has not been saved yet.</p>
            </template>
            <RunPanel v-else :language="project.language" :code="project.code" :runtime="runtime" />

            <div v-if="project.language !== 'scratch'" class="card overflow-hidden">
                <button type="button" class="flex w-full items-center justify-between px-5 py-3 text-sm font-bold text-ink hover:bg-chip" :aria-expanded="showCode" @click="showCode = !showCode">
                    <span class="flex items-center gap-2"><Icon name="code" :size="16" /> {{ project.language === 'presentation' ? 'Slides text' : 'Code' }}</span>
                    <Icon name="chevron-down" :size="16" :class="showCode ? 'rotate-180' : ''" class="transition" />
                </button>
                <div v-if="showCode" class="border-t border-line p-3">
                    <CodeEditor :model-value="project.code" :language="project.language" readonly min-height="10rem" :label="`${project.language_label} code by ${project.author.name}`" />
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card grid grid-cols-3 divide-x divide-line text-center">
                <button
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-l-2xl py-4 transition enabled:hover:bg-accent-50 disabled:cursor-default"
                    :class="project.liked ? 'text-accent-600' : 'text-ink'"
                    :disabled="!can.interact"
                    :aria-pressed="project.liked"
                    :aria-label="project.liked ? 'Unlike' : 'Like'"
                    @click="like"
                >
                    <Heart :size="22" :fill="project.liked ? 'currentColor' : 'none'" aria-hidden="true" />
                    <span class="text-sm font-bold">{{ project.likes_count }}</span>
                </button>
                <div class="flex flex-col items-center gap-1 py-4 text-ink"><Eye :size="22" aria-hidden="true" /><span class="text-sm font-bold">{{ project.views_count }}</span><span class="sr-only">views</span></div>
                <div class="flex flex-col items-center gap-1 py-4 text-ink"><MessageCircle :size="22" aria-hidden="true" /><span class="text-sm font-bold">{{ project.comments_count }}</span><span class="sr-only">comments</span></div>
            </div>

            <Link v-if="can.edit" :href="route('lab.projects.edit', project.id)" class="btn-primary w-full"><Icon name="pencil" :size="16" /> Edit in Laboratory</Link>
            <button v-if="can.moderate && !project.hidden" type="button" class="btn-ghost w-full text-accent-700" @click="hiding = true"><Icon name="eye-off" :size="16" /> Remove from Community</button>

            <section class="card p-5" aria-labelledby="comments-title">
                <h2 id="comments-title" class="font-display text-base font-extrabold text-ink">Comments</h2>
                <form v-if="can.interact" class="mt-3" @submit.prevent="post">
                    <label for="c-body" class="sr-only">Write a comment</label>
                    <textarea id="c-body" v-model="comment.body" rows="3" maxlength="1000" class="field" placeholder="Say something kind or ask how it works" required />
                    <p v-if="comment.errors.body" class="mt-1 text-sm text-red-600">{{ comment.errors.body }}</p>
                    <button type="submit" class="btn-primary mt-2 w-full" :disabled="comment.processing || !comment.body.trim()"><Icon name="send" :size="16" /> Post comment</button>
                </form>
                <ul v-if="comments.length" class="mt-4 space-y-4">
                    <li v-for="c in comments" :key="c.id" class="flex gap-3">
                        <Avatar :initials="c.author.initials" :seed="c.author.id" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center justify-between gap-2 text-xs text-muted">
                                <span><b class="text-ink">{{ c.author.name }}</b> · {{ when(c.created_at) }}</span>
                                <button v-if="c.can_delete" type="button" class="text-muted hover:text-red-600" aria-label="Delete comment" @click="removing = c.id"><Icon name="trash" :size="14" /></button>
                            </p>
                            <p class="mt-0.5 text-sm break-words whitespace-pre-line text-ink">{{ c.body }}</p>
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-muted">No comments yet.</p>
            </section>
        </aside>
    </div>

    <ConfirmDialog :show="removing !== null" title="Delete this comment?" confirm-text="Delete" @close="removing = null" @confirm="removeComment" />
    <ConfirmDialog :show="hiding" title="Remove from the Community?" message="The project stays in its owner's Laboratory, but nobody else can see it and it can't be shared again until you put it back." confirm-text="Remove" @close="hiding = false" @confirm="hide" />
</template>
