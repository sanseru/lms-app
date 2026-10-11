<script setup>
import QRCode from 'qrcode';
import { onMounted, ref } from 'vue';

/** A4 landscape certificate; text sizes follow the width (cqw) so it looks the same on screen and in print. */
const props = defineProps({ certificate: { type: Object, required: true } });
const qr = ref('');
onMounted(async () => {
    qr.value = await QRCode.toString(props.certificate.verify_url, { type: 'svg', margin: 0, color: { dark: '#2b1f3f', light: '#ffffff00' } });
});
</script>

<template>
    <div class="certificate @container relative aspect-[297/210] w-full overflow-hidden bg-white text-ink shadow-xl">
        <!-- Frame -->
        <div class="absolute inset-[2.2cqw] rounded-[1.5cqw] border-[0.35cqw] border-brand-600" />
        <div class="absolute inset-[3.1cqw] rounded-[1cqw] border-[0.12cqw] border-accent-400" />
        <div class="absolute -top-[9cqw] -left-[9cqw] size-[24cqw] rounded-full bg-gradient-to-br from-brand-500 to-accent-500 opacity-90" />
        <div class="absolute -right-[7cqw] -bottom-[10cqw] size-[22cqw] rounded-full bg-gradient-to-br from-accent-400 to-brand-500 opacity-80" />
        <span class="absolute top-[4cqw] right-[6cqw] text-[5cqw] opacity-80">🚀</span>
        <span class="absolute bottom-[5cqw] left-[6cqw] font-mono text-[3.5cqw] font-black text-brand-100">&lt;/&gt;</span>

        <div class="relative flex h-full flex-col items-center px-[12cqw] pt-[6cqw] pb-[6cqw] text-center">
            <img src="/images/logo-header.png" alt="Alprogmatics" class="h-[5cqw] w-auto" />
            <div class="flex flex-1 flex-col items-center justify-center">
            <p class="font-display text-[1.5cqw] font-extrabold tracking-[0.5em] text-accent-600 uppercase">Certificate of Achievement</p>
            <p class="mt-[1.6cqw] text-[1.5cqw] text-muted">Presented to</p>
            <p class="mt-[0.8cqw] font-display text-[5.2cqw] leading-tight font-black text-brand-700">{{ certificate.student.name }}</p>
            <div class="mt-[0.8cqw] h-[0.25cqw] w-[30cqw] rounded-full bg-gradient-to-r from-transparent via-accent-400 to-transparent" />
            <p class="mt-[1.6cqw] text-[1.6cqw] text-ink/80">{{ certificate.achievement }}</p>
            <p class="mt-[0.8cqw] font-display text-[2.9cqw] font-extrabold text-ink">{{ certificate.title }}</p>
            <p class="mt-[0.6cqw] text-[1.3cqw] text-muted">at Alprogmatics, {{ certificate.issued_at }}</p>
            </div>

            <div class="flex w-full items-end justify-between gap-[3cqw]">
                <div class="w-[24cqw] text-center">
                    <p class="font-display text-[1.6cqw] font-bold text-ink">{{ certificate.signer_name }}</p>
                    <div class="mx-auto mt-[0.4cqw] h-[0.12cqw] w-full bg-ink/40" />
                    <p class="mt-[0.4cqw] text-[1.15cqw] text-muted">Tutor</p>
                </div>
                <div class="flex items-end gap-[1.2cqw] text-left">
                    <div class="size-[8cqw] shrink-0" v-html="qr" />
                    <div class="text-[1cqw] leading-snug text-muted">
                        <p class="font-semibold text-ink">No. {{ certificate.number }}</p>
                        <p>Verification code</p>
                        <p class="font-mono text-[1.3cqw] font-bold tracking-wider text-brand-700">{{ certificate.code }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="certificate.revoked" class="absolute inset-0 grid place-items-center bg-white/60">
            <span class="-rotate-12 rounded-[1cqw] border-[0.4cqw] border-rose-600 px-[3cqw] py-[1cqw] font-display text-[5cqw] font-black text-rose-600">REVOKED</span>
        </div>
    </div>
</template>
