<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';

defineOptions({ layout: null });
const props = defineProps({ interests: Array, sources: Object, interest: String, sent: String });

const form = useForm({
    parent_name: '', phone: '', email: '', child_name: '', child_age: '', child_grade: '',
    interest: props.interests.includes(props.interest) ? props.interest : '', preferred_time: '', source: '', message: '', website: '',
});
const submit = () => form.post(route('leads.apply.store'), { preserveScroll: true, onSuccess: () => form.reset() });

const steps = [
    ['Fill in this form', 'It takes a minute. No account needed.'],
    ['We message you on WhatsApp', 'We ask about your child’s interests and find a time that suits you.'],
    ['A 45-minute trial class on Zoom', 'Free. Your child builds a small project with a teacher right away.'],
    ['Get a class recommendation', 'The teacher shares how the trial went and which class fits best.'],
];
</script>

<template>
    <Head title="Book a Free Trial Class">
        <meta name="description" content="Book a free trial coding class on Zoom for your child. We will contact you on WhatsApp." />
    </Head>

    <div class="min-h-screen bg-paper text-ink">
        <header class="border-b border-line bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <Link href="/"><img src="/images/logo-header.png" alt="Alprogmatics" class="h-10 w-auto" /></Link>
                <Link href="/" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Home</Link>
            </div>
        </header>

        <main class="mx-auto grid max-w-5xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_22rem] lg:py-14">
            <div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Book a free trial class</h1>
                <p class="mt-3 max-w-xl text-lg text-muted">One session with a teacher on Zoom, at no cost. Afterwards, you decide whether to continue.</p>

                <div v-if="sent" class="card mt-8 p-8 text-center" role="status">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-50 text-emerald-700"><Icon name="circle-check" :size="30" /></span>
                    <h2 class="mt-4 font-display text-2xl font-bold">Thank you, {{ sent }}!</h2>
                    <p class="mx-auto mt-2 max-w-md text-muted">We have received your sign-up. We will message you on WhatsApp during office hours to pick a time for the trial.</p>
                    <Link href="/" class="btn-ghost mt-6">Back to home</Link>
                </div>

                <form v-else class="card mt-8 grid gap-5 p-6 sm:grid-cols-2 sm:p-8" novalidate @submit.prevent="submit">
                    <p class="text-sm font-bold text-brand-700 sm:col-span-2">About you</p>
                    <Field id="parent_name" label="Parent’s name" required :error="form.errors.parent_name">
                        <input id="parent_name" v-model="form.parent_name" class="field" autocomplete="name" required />
                    </Field>
                    <Field id="phone" label="WhatsApp number" required :error="form.errors.phone" hint="We will only contact you on this number.">
                        <input id="phone" v-model="form.phone" type="tel" inputmode="tel" class="field" autocomplete="tel" placeholder="08xx-xxxx-xxxx" required />
                    </Field>
                    <Field id="email" label="Email (optional)" :error="form.errors.email" class="sm:col-span-2">
                        <input id="email" v-model="form.email" type="email" class="field" autocomplete="email" />
                    </Field>

                    <p class="mt-2 text-sm font-bold text-brand-700 sm:col-span-2">About your child</p>
                    <Field id="child_name" label="Child’s name" required :error="form.errors.child_name">
                        <input id="child_name" v-model="form.child_name" class="field" required />
                    </Field>
                    <div class="grid grid-cols-2 gap-3">
                        <Field id="child_age" label="Age" :error="form.errors.child_age">
                            <input id="child_age" v-model="form.child_age" type="number" min="4" max="18" class="field" placeholder="years" />
                        </Field>
                        <Field id="child_grade" label="School grade" :error="form.errors.child_grade">
                            <input id="child_grade" v-model="form.child_grade" class="field" placeholder="e.g. Grade 4" />
                        </Field>
                    </div>
                    <Field id="interest" label="Program of interest" :error="form.errors.interest">
                        <select id="interest" v-model="form.interest" class="field">
                            <option value="">Choose a program</option>
                            <option v-for="i in interests" :key="i" :value="i">{{ i }}</option>
                        </select>
                    </Field>
                    <Field id="preferred_time" label="Preferred time" :error="form.errors.preferred_time">
                        <input id="preferred_time" v-model="form.preferred_time" class="field" placeholder="e.g. Saturday morning or weekdays after 4 pm" />
                    </Field>
                    <Field id="source" label="How did you hear about Alprogmatics?" :error="form.errors.source">
                        <select id="source" v-model="form.source" class="field">
                            <option value="">Choose</option>
                            <option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </Field>
                    <Field id="message" label="Message for us (optional)" :error="form.errors.message" class="sm:col-span-2">
                        <textarea id="message" v-model="form.message" rows="3" class="field" placeholder="e.g. loves Minecraft, has never learned coding" />
                    </Field>

                    <!-- Bot trap, hidden from humans -->
                    <div class="hidden" aria-hidden="true"><label>Website <input v-model="form.website" tabindex="-1" autocomplete="off" /></label></div>

                    <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                        <button class="btn-accent px-6 py-3 text-base" :disabled="form.processing">Send sign-up</button>
                        <p class="text-sm text-muted">Free, with no obligation to continue.</p>
                    </div>
                </form>
            </div>

            <aside class="lg:pt-20">
                <h2 class="font-display text-lg font-bold">What happens next</h2>
                <ol class="mt-4 space-y-5">
                    <li v-for="(s, i) in steps" :key="i" class="flex gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ i + 1 }}</span>
                        <div><p class="font-semibold">{{ s[0] }}</p><p class="text-sm text-muted">{{ s[1] }}</p></div>
                    </li>
                </ol>
            </aside>
        </main>
    </div>
</template>
