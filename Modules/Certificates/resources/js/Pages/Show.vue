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
const copy = async () => { try { await navigator.clipboard.writeText(props.certificate.verify_url); copied.value = true; setTimeout(() => (copied.value = false), 1500); } catch { /* not available */ } };
</script>

<template>
    <Head :title="`${certificate.student.name}'s certificate`" />

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <Link :href="route('certificates.index')" class="mr-auto inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> All certificates</Link>
        <button class="btn-ghost" @click="copy"><Icon name="link" :size="16" /> {{ copied ? 'Link copied' : 'Copy verification link' }}</button>
        <button v-if="canRevoke" class="btn-ghost" :class="!certificate.revoked && 'text-rose-600'" @click="certificate.revoked ? revoke() : (confirming = true)">
            <Icon :name="certificate.revoked ? 'rotate-ccw' : 'shield-x'" :size="16" /> {{ certificate.revoked ? 'Make valid again' : 'Revoke' }}
        </button>
        <button class="btn-primary" @click="print"><Icon name="printer" :size="16" /> Print / save as PDF</button>
    </div>

    <div class="print-area mx-auto max-w-5xl">
        <CertificateArt :certificate="certificate" />
    </div>

    <p class="mx-auto mt-4 max-w-5xl text-center text-xs text-muted">Anyone can check this certificate is genuine by scanning the QR code or opening {{ certificate.verify_url }}</p>

    <ConfirmDialog :show="confirming" title="Revoke certificate?" message="The verification page will show this certificate as no longer valid. You can make it valid again later." confirm-text="Revoke" @close="confirming = false" @confirm="revoke" />
</template>
