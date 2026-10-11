<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';

defineOptions({ layout: null });
defineProps({ code: String, certificate: Object });
</script>

<template>
    <Head title="Verifikasi sertifikat" />

    <div class="grid min-h-screen place-items-center bg-gradient-to-br from-brand-50 via-white to-accent-50 px-4 py-10">
        <div class="w-full max-w-md">
            <a href="/" class="mb-6 flex justify-center"><img src="/images/logo-header.png" alt="Alprogmatics" class="h-12 w-auto" /></a>
            <div class="card overflow-hidden">
                <div :class="certificate?.valid ? 'from-emerald-500 to-teal-400' : 'from-rose-500 to-orange-400'" class="bg-gradient-to-br p-6 text-center text-white">
                    <Icon :name="certificate?.valid ? 'shield-check' : 'shield-x'" :size="44" class="mx-auto" />
                    <p class="mt-2 font-display text-2xl font-extrabold">
                        {{ !certificate ? 'Sertifikat tidak ditemukan' : certificate.valid ? 'Sertifikat asli' : 'Sertifikat sudah dicabut' }}
                    </p>
                    <p class="text-sm text-white/90">Kode {{ code }}</p>
                </div>
                <dl v-if="certificate" class="divide-y divide-line text-sm">
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Nama</dt><dd class="text-right font-semibold text-ink">{{ certificate.student }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Kursus</dt><dd class="text-right font-semibold text-ink">{{ certificate.title }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Tanggal terbit</dt><dd class="text-right font-semibold text-ink">{{ certificate.issued_at }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Nomor</dt><dd class="text-right font-mono text-ink">{{ certificate.number }}</dd></div>
                </dl>
                <p v-else class="p-6 text-center text-sm text-muted">Periksa kembali kode pada sertifikat. Kode terdiri dari 10 huruf dan angka.</p>
            </div>
            <p class="mt-6 text-center text-xs text-muted">Diterbitkan oleh Alprogmatics, kursus coding untuk anak.</p>
        </div>
    </div>
</template>
