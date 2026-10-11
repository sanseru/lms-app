<script setup>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';
import { classColors } from '@/lib/format';

defineProps({ question: { type: Object, required: true }, compact: { type: Boolean, default: false } });
</script>

<template>
    <Link
        :href="route('forum.questions.show', question.id)"
        :class="question.pinned ? 'border-amber-200 bg-amber-50/60' : 'border-line bg-white'"
        class="group flex gap-4 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md sm:p-5"
    >
        <!-- Angka jawaban -->
        <div
            :class="{
                'bg-emerald-500 text-white': question.status === 'resolved',
                'bg-sky-50 text-sky-700 ring-1 ring-sky-200': question.status === 'answered',
                'bg-chip text-muted': question.status === 'unanswered',
            }"
            class="hidden w-16 shrink-0 flex-col items-center justify-center rounded-xl py-2 sm:flex"
        >
            <Icon v-if="question.status === 'resolved'" name="circle-check" :size="18" />
            <span class="font-display text-xl leading-tight font-black">{{ question.answers_count }}</span>
            <span class="text-[11px] font-semibold">jawaban</span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                <span v-if="question.pinned" class="inline-flex items-center gap-1 font-bold text-amber-700"><Icon name="pin" :size="12" /> Disematkan</span>
                <span class="inline-flex items-center gap-1.5 font-semibold">
                    <span :class="(classColors[question.class.color] ?? classColors.violet).dot" class="size-2 rounded-full" />{{ question.class.name }}
                </span>
                <span v-if="question.lesson" class="truncate">· {{ question.lesson.title }}</span>
                <span v-if="question.visibility === 'private'" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 font-semibold text-brand-700"><Icon name="lock" :size="11" /> Hanya guru</span>
            </div>
            <p class="mt-1 font-display text-base font-bold text-ink group-hover:text-brand-700 sm:text-lg">{{ question.title }}</p>
            <p v-if="!compact" class="mt-1 line-clamp-2 text-sm text-muted">{{ question.excerpt }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted">
                <span class="flex items-center gap-2">
                    <Avatar :initials="question.author.initials" :seed="question.author.id" size="sm" />
                    <span class="font-semibold text-ink">{{ question.author.name }}</span>
                    <span>{{ question.created_at }}</span>
                </span>
                <span v-if="question.votes_count" class="inline-flex items-center gap-1"><Icon name="hand" :size="13" /> {{ question.votes_count }} juga bingung</span>
                <span v-if="question.has_code" class="inline-flex items-center gap-1"><Icon name="code" :size="13" /> kode</span>
                <span v-if="question.has_image" class="inline-flex items-center gap-1"><Icon name="image" :size="13" /> gambar</span>
                <span class="inline-flex items-center gap-1 sm:hidden"><Icon name="message-circle" :size="13" /> {{ question.answers_count }} jawaban</span>
                <span v-if="question.staff_answered" class="inline-flex items-center gap-1 font-semibold text-emerald-700"><Icon name="badge-check" :size="13" /> Dijawab guru</span>
            </div>
        </div>
    </Link>
</template>
