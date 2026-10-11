<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Icon from '@/Components/ui/Icon.vue';
import CertificateArt from '../Components/CertificateArt.vue';

const props = defineProps({ certificate: Object, canRevoke: Boolean });
const confirming = ref(false);
const revoke = () => router.post(route('certificates.revoke', props.certificate.id), {}, { preserveScroll: true, onFinish: () => (confirming.value = false) });
const print = () => window.print();
const copied = ref(false);
const copy = async () => { try { await navigator.clipboard.writeText(props.certificate.verify_url); copied.value = true; setTimeout(() => (copied.value = false), 1500); } catch { /* tidak tersedia */ } };
</script>

<template>
    <Head :title="`Sertifikat ${certificate.student.name}`" />

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <Link :href="route('certificates.index')" class="mr-auto inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Semua sertifikat</Link>
        <button class="btn-ghost" @click="copy"><Icon name="link" :size="16" /> {{ copied ? 'Tautan tersalin' : 'Salin tautan verifikasi' }}</button>
        <button v-if="canRevoke" class="btn-ghost" :class="!certificate.revoked && 'text-rose-600'" @click="certificate.revoked ? revoke() : (confirming = true)">
            <Icon :name="certificate.revoked ? 'rotate-ccw' : 'shield-x'" :size="16" /> {{ certificate.revoked ? 'Berlakukan lagi' : 'Cabut' }}
        </button>
        <button class="btn-primary" @click="print"><Icon name="printer" :size="16" /> Cetak / simpan PDF</button>
    </div>

    <div class="print-area mx-auto max-w-5xl">
        <CertificateArt :certificate="certificate" />
    </div>

    <p class="mx-auto mt-4 max-w-5xl text-center text-xs text-muted">Siapa pun bisa memeriksa keaslian sertifikat ini dengan memindai QR atau membuka {{ certificate.verify_url }}</p>

    <ConfirmDialog :show="confirming" title="Cabut sertifikat?" message="Halaman verifikasi akan menyatakan sertifikat ini tidak berlaku. Bisa diberlakukan lagi nanti." confirm-text="Cabut" @close="confirming = false" @confirm="revoke" />
</template>
