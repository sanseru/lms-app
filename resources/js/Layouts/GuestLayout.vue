<script setup>
import Icon from '@/Components/ui/Icon.vue';
</script>

<template>
    <div class="relative flex min-h-screen overflow-hidden bg-paper">
        <!-- Illustration panel -->
        <div class="relative hidden w-1/2 flex-col justify-between panel-brand !rounded-none p-12 lg:flex">
            <img src="/images/logo-header.png" alt="Alprogmatics" class="h-14 w-fit rounded-xl bg-white/95 px-3 py-1.5" />

            <div class="relative z-10 max-w-md">
                <h2 class=" text-4xl leading-tight font-extrabold tracking-tight">Learn, create and grow together.</h2>
                <p class="mt-4 text-white/85">One place for online classes, Zoom schedules, learning progress and course payments.</p>

                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div v-for="item in [['code', 'Python'], ['rocket', 'Scratch'], ['sparkles', 'AI & Robotics']]" :key="item[1]" class="rounded-2xl bg-white/15 p-4 ring-1 ring-white/10">
                        <Icon :name="item[0]" :size="22" class="text-accent-300" />
                        <p class="mt-2 text-sm font-semibold">{{ item[1] }}</p>
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-sm text-brand-200">© {{ new Date().getFullYear() }} Alprogmatics · Learn. Create. Grow.</p>

        </div>

        <!-- Form -->
        <div class="flex w-full items-center justify-center px-4 py-12 lg:w-1/2">
            <div class="w-full max-w-md">
                <img src="/images/logo-header.png" alt="Alprogmatics" class="mx-auto mb-8 h-16 w-auto lg:hidden" />
                <div class="card p-8 shadow-xl shadow-brand-900/5">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
