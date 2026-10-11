<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import CtaSection from '../Components/CtaSection.vue';
import FaqSection from '../Components/FaqSection.vue';
import FeatureSection from '../Components/FeatureSection.vue';
import HeroSection from '../Components/HeroSection.vue';
import LandingFooter from '../Components/LandingFooter.vue';
import LandingHeader from '../Components/LandingHeader.vue';
import ParentSection from '../Components/ParentSection.vue';
import PricingSection from '../Components/PricingSection.vue';
import ProgramSection from '../Components/ProgramSection.vue';
import StepsSection from '../Components/StepsSection.vue';
import TestimonialSection from '../Components/TestimonialSection.vue';

defineOptions({ layout: null });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Online Coding Classes for Kids">
        <meta name="description" content="Online coding classes on Zoom for kids aged 6–17. Private or small-group lessons with experienced teachers, plus progress reports for parents." />
    </Head>
    <div class="landing min-h-full bg-paper text-base text-ink">
        <LandingHeader :user="user" />
        <main id="top">
            <HeroSection />
            <FeatureSection />
            <ProgramSection />
            <StepsSection />
            <ParentSection />
            <TestimonialSection />
            <PricingSection />
            <FaqSection />
            <CtaSection />
        </main>
        <LandingFooter />
    </div>
</template>

<style>
html:has(.landing) {
    scroll-behavior: smooth;
    scroll-padding-top: 84px;
}
.landing-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.landing-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px -18px rgb(29 41 52 / 0.45);
}
@media (prefers-reduced-motion: reduce) {
    html:has(.landing) { scroll-behavior: auto; }
    .landing-lift, .landing-lift:hover { transition: none; transform: none; }
}
</style>
