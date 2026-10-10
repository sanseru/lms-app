<script setup>
const points = [
    ['Catatan sesi', 'berisi materi, tugas, dan pesan tutor, dikirim hari itu juga.'],
    ['Rapor level', 'dengan penilaian logika, kreativitas, dan kemandirian.'],
    ['Riwayat kehadiran & sisa sesi', 'paket, plus pengingat sebelum kuota habis.'],
];
const tutorFacts = [
    ['3 tahap', 'seleksi: tes teknis, demo mengajar, wawancara'],
    ['S1', 'Informatika, Teknik, atau Pendidikan'],
    ['Rutin', 'pelatihan cara mengajar anak tiap bulan'],
];
</script>

<template>
    <section id="laporan" class="bg-brand-900 text-white" aria-labelledby="h-laporan">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
            <div class="min-w-0">
                <p class="text-sm font-extrabold uppercase tracking-[.12em] text-amber-300">Untuk orang tua</p>
                <h2 id="h-laporan" class="mt-2 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Anda selalu tahu apa yang dipelajari anak</h2>
                <p class="mt-4 max-w-xl text-lg leading-relaxed text-white/75">Tidak perlu menebak-nebak. Setiap sesi tercatat, dan sisa kuota pertemuan bisa dicek kapan saja lewat portal orang tua.</p>
                <ul class="mt-8 grid gap-4">
                    <li v-for="[b, t] in points" :key="b" class="flex gap-3">
                        <span class="mt-1 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-success/25 text-sm font-bold text-emerald-300">✓</span>
                        <span><b>{{ b }}</b> {{ t }}</span>
                    </li>
                </ul>
            </div>
            <div class="min-w-0 rounded-3xl border border-white/10 bg-white/[.06] p-6 sm:p-8">
                <p class="text-sm font-extrabold uppercase tracking-[.12em] text-amber-300">Tentang tutor kami</p>
                <h3 class="mt-2 text-2xl font-bold">Pengajar yang paham coding dan paham anak</h3>
                <div class="mt-6 grid gap-4 text-center sm:grid-cols-3">
                    <div v-for="[n, t] in tutorFacts" :key="n" class="rounded-2xl bg-white/[.06] p-4">
                        <p class="font-display text-3xl font-extrabold text-amber-300">{{ n }}</p>
                        <p class="mt-1 text-sm text-white/70">{{ t }}</p>
                    </div>
                </div>
                <p class="mt-6 leading-relaxed text-white/75">Tutor yang sama mendampingi anak dari awal level, sehingga anak nyaman bertanya dan tutor hafal gaya belajarnya.</p>
            </div>
        </div>
    </section>
</template>
