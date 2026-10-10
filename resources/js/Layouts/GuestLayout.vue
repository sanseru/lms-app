<template>
    <div class="relative flex min-h-screen overflow-hidden bg-gradient-to-br from-[#FAF8F5] via-white to-brand-50">
        <!-- Panel ilustrasi -->
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-700 via-violet-600 to-accent-500 p-12 text-white lg:flex">
            <img src="/images/logo-header.png" alt="Alprogmatics" class="h-14 w-fit rounded-xl bg-white/95 px-3 py-1.5" />

            <div class="relative z-10 max-w-md">
                <p class="eyebrow !text-white/80">Coding untuk anak dan remaja</p>
                <h2 class="mt-3 text-4xl leading-tight font-extrabold tracking-tight">Belajar, berkarya, dan tumbuh bersama.</h2>
                <p class="mt-4 text-white/80">Satu tempat untuk kelas online, jadwal Zoom, progres belajar, dan pembayaran kursus.</p>

                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div v-for="item in [['🐍', 'Python'], ['🧩', 'Scratch'], ['🤖', 'AI & Robotik']]" :key="item[1]" class="rounded-2xl bg-white/15 p-4 backdrop-blur-sm">
                        <p class="text-2xl">{{ item[0] }}</p>
                        <p class="mt-2 text-sm font-semibold">{{ item[1] }}</p>
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-sm text-white/70">© {{ new Date().getFullYear() }} Alprogmatics · Learn. Create. Grow.</p>

            <span class="pointer-events-none absolute -right-10 -bottom-16 font-mono text-[18rem] leading-none font-black text-white/10 select-none">{ }</span>
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
