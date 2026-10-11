<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';

defineOptions({ layout: null });
defineProps({ code: String, certificate: Object });
</script>

<template>
    <Head title="Verify certificate" />

    <div class="grid min-h-screen place-items-center bg-paper px-4 py-10">
        <div class="w-full max-w-md">
            <a href="/" class="mb-6 flex justify-center"><img src="/images/logo-header.png" alt="Alprogmatics" class="h-12 w-auto" /></a>
            <div class="card overflow-hidden">
                <div :class="certificate?.valid ? 'bg-emerald-700' : 'bg-rose-700'" class="p-6 text-center text-white">
                    <Icon :name="certificate?.valid ? 'shield-check' : 'shield-x'" :size="44" class="mx-auto" />
                    <p class="mt-2 font-display text-2xl font-extrabold">
                        {{ !certificate ? 'Certificate not found' : certificate.valid ? 'Genuine certificate' : 'This certificate has been revoked' }}
                    </p>
                    <p class="text-sm text-white">Code <span class="font-mono">{{ code }}</span></p>
                </div>
                <dl v-if="certificate" class="divide-y divide-line text-sm">
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Name</dt><dd class="text-right font-semibold text-ink">{{ certificate.student }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Course</dt><dd class="text-right font-semibold text-ink">{{ certificate.title }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Issue date</dt><dd class="text-right font-semibold text-ink">{{ certificate.issued_at }}</dd></div>
                    <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted">Number</dt><dd class="text-right font-mono text-ink">{{ certificate.number }}</dd></div>
                </dl>
                <p v-else class="p-6 text-center text-sm text-muted">Check the code on the certificate again. It is made up of 10 letters and numbers.</p>
            </div>
            <p class="mt-6 text-center text-xs text-muted">Issued by Alprogmatics, coding courses for kids.</p>
        </div>
    </div>
</template>
