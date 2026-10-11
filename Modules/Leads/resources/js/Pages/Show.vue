<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

const props = defineProps({
    lead: Object, statuses: Object, interests: Array, sources: Object, teachers: Array, classes: Array, existingGuardian: Object, suggested: Object,
});

const open = computed(() => ['new', 'contacted', 'trial_scheduled', 'trial_done'].includes(props.lead.status));
const steps = ['new', 'contacted', 'trial_scheduled', 'trial_done', 'won'];
const stepIndex = computed(() => steps.indexOf(props.lead.status));

const waText = computed(() => {
    const l = props.lead;
    if (l.status === 'trial_scheduled' && l.trial_at_label) {
        return `Hi ${l.parent_name}, a quick reminder about ${l.child_name}'s free trial class on ${l.trial_at_label} (WIB) with ${l.trial_teacher}.${l.trial_zoom_url ? ` Zoom link: ${l.trial_zoom_url}` : ''} See you there!`;
    }
    return `Hi ${l.parent_name}, thank you for signing ${l.child_name} up for a free Alprogmatics trial class. When would suit you best for a 45-minute trial on Zoom?`;
});
const waUrl = computed(() => `https://wa.me/${props.lead.wa}?text=${encodeURIComponent(waText.value)}`);

const post = (name, data = {}) => router.post(route(name, props.lead.id), data, { preserveScroll: true });

const note = useForm({ body: '' });
const addNote = () => note.post(route('leads.note', props.lead.id), { preserveScroll: true, onSuccess: () => note.reset() });

const scheduling = ref(false);
const trial = useForm({ trial_at: props.lead.trial_at ?? '', trial_teacher_id: props.lead.trial_teacher_id ?? '', trial_zoom_url: props.lead.trial_zoom_url ?? '' });
const schedule = () => trial.post(route('leads.schedule', props.lead.id), { preserveScroll: true, onSuccess: () => (scheduling.value = false) });

const recording = ref(false);
const result = useForm({ trial_result: props.lead.trial_result ?? '', trial_interest: props.lead.trial_interest ?? 4, recommended_class_id: props.lead.recommended_class_id ?? '' });
const saveResult = () => result.post(route('leads.result', props.lead.id), { preserveScroll: true, onSuccess: () => (recording.value = false) });

const losing = ref(false);
const lost = useForm({ lost_reason: '' });
const lose = () => lost.post(route('leads.lose', props.lead.id), { preserveScroll: true, onSuccess: () => (losing.value = false) });

const converting = ref(false);
const convert = useForm({
    student_username: props.suggested.student, guardian_username: props.existingGuardian ? '' : props.suggested.guardian,
    password: '', relationship: 'ibu', class_room_id: props.lead.recommended_class_id ?? '',
});
const doConvert = () => convert.post(route('leads.convert', props.lead.id), { preserveScroll: true, onSuccess: () => (converting.value = false) });

const editing = ref(false);
const edit = useForm({ ...Object.fromEntries(['parent_name', 'phone', 'email', 'child_name', 'child_age', 'child_grade', 'interest', 'preferred_time', 'source', 'message'].map((k) => [k, props.lead[k] ?? ''])) });
const saveEdit = () => edit.put(route('leads.update', props.lead.id), { preserveScroll: true, onSuccess: () => (editing.value = false) });
</script>

<template>
    <Head :title="`${lead.child_name} · Leads`" />

    <PageHeader eyebrow="Lead" :title="lead.child_name" :description="`${lead.parent_name} · signed up ${lead.created}${lead.source_label ? ` · via ${lead.source_label}` : ''}`">
        <template #back>
            <Link :href="route('leads.index')" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> All leads</Link>
        </template>
        <template #actions>
            <a :href="waUrl" target="_blank" rel="noopener" class="btn-accent"><Icon name="message-circle" :size="18" /> Chat WhatsApp</a>
        </template>
    </PageHeader>

    <!-- Stages -->
    <ol v-if="lead.status !== 'lost'" class="mb-6 grid grid-cols-5 gap-1 text-center text-xs font-semibold" aria-label="Stages">
        <li v-for="(s, i) in steps" :key="s" class="rounded-lg px-1 py-2" :class="i < stepIndex ? 'bg-brand-100 text-brand-800' : i === stepIndex ? 'bg-brand-600 text-white' : 'bg-chip text-muted'" :aria-current="i === stepIndex ? 'step' : undefined">
            {{ statuses[s] }}
        </li>
    </ol>
    <div v-else class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl bg-chip p-4 text-sm">
        <span class="font-bold text-ink">Not continuing:</span> <span class="text-ink">{{ lead.lost_reason }}</span>
        <button class="btn-ghost ml-auto py-2" @click="post('leads.reopen')"><Icon name="rotate-ccw" :size="16" /> Reopen</button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <!-- Next step -->
            <div v-if="open" class="card p-5">
                <h2 class="font-display font-bold text-ink">Next step</h2>
                <template v-if="lead.status === 'new'">
                    <p class="mt-1 text-sm text-muted">Say hello to the parent on WhatsApp and ask what time suits them. An opening message is ready in the WhatsApp chat button.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="post('leads.contacted')"><Icon name="check" :size="16" /> I have contacted them</button>
                        <button class="btn-ghost" @click="scheduling = true"><Icon name="calendar-days" :size="16" /> Schedule a trial now</button>
                    </div>
                </template>
                <template v-else-if="lead.status === 'contacted'">
                    <p class="mt-1 text-sm text-muted">Once you agree on a time, record it here. The teacher will get a notification.</p>
                    <button class="btn-primary mt-4" @click="scheduling = true"><Icon name="calendar-days" :size="16" /> Schedule trial</button>
                </template>
                <template v-else-if="lead.status === 'trial_scheduled'">
                    <p class="mt-1 text-sm text-ink">Trial on <strong>{{ lead.trial_at_label }}</strong> with <strong>{{ lead.trial_teacher }}</strong>.</p>
                    <p v-if="lead.trial_zoom_url" class="text-sm"><a :href="lead.trial_zoom_url" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Zoom link</a></p>
                    <p class="mt-1 text-sm text-muted">Send a reminder with the WhatsApp chat button the day before. The teacher records the trial result after the session.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="recording = true"><Icon name="clipboard-check" :size="16" /> Record trial result</button>
                        <button class="btn-ghost" @click="scheduling = true">Reschedule</button>
                    </div>
                </template>
                <template v-else-if="lead.status === 'trial_done'">
                    <div class="mt-2 rounded-xl bg-emerald-50 p-4 text-sm ring-1 ring-emerald-200">
                        <p class="font-semibold text-emerald-900">Trial result · enthusiasm {{ lead.trial_interest }}/5<span v-if="lead.recommended_class"> · recommended: {{ lead.recommended_class }}</span></p>
                        <p class="mt-1 whitespace-pre-line text-ink">{{ lead.trial_result }}</p>
                    </div>
                    <p class="mt-3 text-sm text-muted">Offer a class to the parent. If they agree, create the accounts here, then sell a session package from the Finance menu.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="btn-primary" @click="converting = true"><Icon name="user-plus" :size="16" /> Enrol as student</button>
                        <button class="btn-ghost" @click="recording = true">Edit trial result</button>
                    </div>
                </template>
                <div class="mt-4 flex flex-wrap gap-x-4 border-t border-line pt-3 text-sm">
                    <button v-if="lead.status !== 'trial_done'" class="font-semibold text-brand-600 hover:underline" @click="converting = true">Enrol as student now</button>
                    <button class="font-semibold text-muted hover:text-rose-700" @click="losing = true">Mark as not continuing</button>
                </div>
            </div>

            <div v-if="lead.status === 'won' && lead.student" class="card flex flex-wrap items-center gap-3 border-emerald-200 bg-emerald-50 p-5">
                <Icon name="circle-check" :size="24" class="text-emerald-700" />
                <p class="text-sm text-ink"><strong>{{ lead.student.name }}</strong> is now a student (username <span class="font-mono">{{ lead.student.username }}</span>).</p>
                <Link :href="route('finance.packages.index')" class="btn-ghost ml-auto py-2">Sell session package</Link>
            </div>

            <!-- History -->
            <div class="card p-5">
                <h2 class="font-display font-bold text-ink">History and notes</h2>
                <form class="mt-3 flex gap-2" @submit.prevent="addNote">
                    <label for="note" class="sr-only">New note</label>
                    <input id="note" v-model="note.body" class="field" placeholder="e.g. Mum asked us to call back on Monday afternoon" />
                    <button class="btn-primary shrink-0" :disabled="note.processing || !note.body">Save</button>
                </form>
                <p v-if="note.errors.body" class="mt-1 text-sm text-rose-600">{{ note.errors.body }}</p>
                <ol class="mt-4 space-y-3 border-l-2 border-line pl-4">
                    <li v-for="n in lead.notes" :key="n.id" class="relative">
                        <span class="absolute top-1.5 -left-[1.3rem] size-2.5 rounded-full" :class="n.type === 'status' ? 'bg-brand-500' : 'bg-accent-500'" />
                        <p class="text-sm whitespace-pre-line text-ink">{{ n.body }}</p>
                        <p class="text-xs text-muted">{{ n.at }}<span v-if="n.user"> · {{ n.user }}</span></p>
                    </li>
                </ol>
            </div>
        </div>

        <!-- Details -->
        <aside class="card h-fit p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-display font-bold text-ink">Lead details</h2>
                <button class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink" title="Edit details" aria-label="Edit details" @click="editing = true"><Icon name="pencil" :size="15" /></button>
            </div>
            <dl class="mt-3 space-y-3 text-sm">
                <div><dt class="text-muted">Parent</dt><dd class="font-semibold text-ink">{{ lead.parent_name }}</dd></div>
                <div><dt class="text-muted">WhatsApp</dt><dd><a :href="`https://wa.me/${lead.wa}`" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">{{ lead.phone }}</a></dd></div>
                <div v-if="lead.email"><dt class="text-muted">Email</dt><dd class="text-ink">{{ lead.email }}</dd></div>
                <div><dt class="text-muted">Child</dt><dd class="text-ink">{{ lead.child_name }}<span v-if="lead.child_age">, {{ lead.child_age }} years old</span><span v-if="lead.child_grade"> · {{ lead.child_grade }}</span></dd></div>
                <div><dt class="text-muted">Interest</dt><dd class="text-ink">{{ lead.interest ?? 'Not sure yet' }}</dd></div>
                <div v-if="lead.preferred_time"><dt class="text-muted">Preferred time</dt><dd class="text-ink">{{ lead.preferred_time }}</dd></div>
                <div v-if="lead.message"><dt class="text-muted">Message</dt><dd class="whitespace-pre-line text-ink">{{ lead.message }}</dd></div>
            </dl>
        </aside>
    </div>

    <!-- Schedule trial -->
    <Modal :show="scheduling" max-width="lg" @close="scheduling = false">
        <form class="space-y-4 p-6" @submit.prevent="schedule">
            <h3 class="font-display text-lg font-bold text-ink">Schedule a trial class</h3>
            <Field id="t_at" label="Time" required :error="trial.errors.trial_at"><input id="t_at" v-model="trial.trial_at" type="datetime-local" class="field" /></Field>
            <Field id="t_teacher" label="Teacher" required :error="trial.errors.trial_teacher_id">
                <select id="t_teacher" v-model="trial.trial_teacher_id" class="field"><option value="">Choose a teacher</option><option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option></select>
            </Field>
            <Field id="t_zoom" label="Zoom link" :error="trial.errors.trial_zoom_url" hint="Leave empty if the teacher uses their personal link."><input id="t_zoom" v-model="trial.trial_zoom_url" type="url" class="field" placeholder="https://zoom.us/j/…" /></Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="scheduling = false">Cancel</button><button class="btn-primary" :disabled="trial.processing">Save schedule</button></div>
        </form>
    </Modal>

    <!-- Trial result -->
    <Modal :show="recording" max-width="lg" @close="recording = false">
        <form class="space-y-4 p-6" @submit.prevent="saveResult">
            <h3 class="font-display text-lg font-bold text-ink">Trial class result</h3>
            <Field id="r_result" label="How did the trial go?" required :error="result.errors.trial_result"><textarea id="r_result" v-model="result.trial_result" rows="4" class="field" placeholder="What the child made, how quickly they picked things up, what they enjoyed." /></Field>
            <Field id="r_interest" label="Child’s enthusiasm (1–5)" required :error="result.errors.trial_interest">
                <div class="flex gap-2">
                    <button v-for="n in 5" :key="n" type="button" class="grid size-11 place-items-center rounded-xl border font-bold" :class="result.trial_interest === n ? 'border-brand-600 bg-brand-600 text-white' : 'border-line text-ink hover:border-brand-300'" :aria-pressed="result.trial_interest === n" @click="result.trial_interest = n">{{ n }}</button>
                </div>
            </Field>
            <Field id="r_class" label="Recommended class" :error="result.errors.recommended_class_id">
                <select id="r_class" v-model="result.recommended_class_id" class="field"><option value="">None yet</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="recording = false">Cancel</button><button class="btn-primary" :disabled="result.processing">Save result</button></div>
        </form>
    </Modal>

    <!-- Not continuing -->
    <Modal :show="losing" max-width="md" @close="losing = false">
        <form class="space-y-4 p-6" @submit.prevent="lose">
            <h3 class="font-display text-lg font-bold text-ink">Mark as not continuing</h3>
            <Field id="l_reason" label="Reason" required :error="lost.errors.lost_reason">
                <input id="l_reason" v-model="lost.lost_reason" list="reasons" class="field" />
                <datalist id="reasons"><option>Price does not work</option><option>Schedule does not work</option><option>No reply</option><option>Child not interested yet</option><option>Chose another provider</option></datalist>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="losing = false">Cancel</button><button class="btn-primary" :disabled="lost.processing">Save</button></div>
        </form>
    </Modal>

    <!-- Enrol as student -->
    <Modal :show="converting" max-width="lg" @close="converting = false">
        <form class="space-y-4 p-6" @submit.prevent="doConvert">
            <h3 class="font-display text-lg font-bold text-ink">Enrol {{ lead.child_name }} as a student</h3>
            <p class="text-sm text-muted">The child and parent accounts are created with the same password. Send it to the parent on WhatsApp and ask them to change it after signing in.</p>
            <Field id="c_student" label="Child’s username" required :error="convert.errors.student_username"><input id="c_student" v-model="convert.student_username" class="field font-mono" /></Field>
            <div v-if="existingGuardian" class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900 ring-1 ring-sky-200">
                This number already belongs to parent <strong>{{ existingGuardian.name }}</strong> ({{ existingGuardian.username }}). The child will be added to that account.
            </div>
            <Field v-else id="c_guardian" label="Parent’s username" required :error="convert.errors.guardian_username"><input id="c_guardian" v-model="convert.guardian_username" class="field font-mono" /></Field>
            <div class="grid grid-cols-2 gap-3">
                <Field id="c_password" label="Initial password" required :error="convert.errors.password"><input id="c_password" v-model="convert.password" type="text" class="field font-mono" autocomplete="off" /></Field>
                <Field id="c_rel" label="Relationship" :error="convert.errors.relationship">
                    <select id="c_rel" v-model="convert.relationship" class="field"><option value="ibu">Mother</option><option value="ayah">Father</option><option value="wali">Guardian</option></select>
                </Field>
            </div>
            <Field id="c_class" label="Enrol in class" :error="convert.errors.class_room_id">
                <select id="c_class" v-model="convert.class_room_id" class="field"><option value="">Later</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="converting = false">Cancel</button><button class="btn-primary" :disabled="convert.processing">Create accounts</button></div>
        </form>
    </Modal>

    <!-- Edit details -->
    <Modal :show="editing" max-width="2xl" @close="editing = false">
        <form class="grid gap-4 p-6 sm:grid-cols-2" @submit.prevent="saveEdit">
            <h3 class="font-display text-lg font-bold text-ink sm:col-span-2">Edit lead details</h3>
            <Field id="e_parent" label="Parent’s name" :error="edit.errors.parent_name"><input id="e_parent" v-model="edit.parent_name" class="field" /></Field>
            <Field id="e_phone" label="WhatsApp number" :error="edit.errors.phone"><input id="e_phone" v-model="edit.phone" class="field" /></Field>
            <Field id="e_child" label="Child’s name" :error="edit.errors.child_name"><input id="e_child" v-model="edit.child_name" class="field" /></Field>
            <div class="grid grid-cols-2 gap-3">
                <Field id="e_age" label="Age" :error="edit.errors.child_age"><input id="e_age" v-model="edit.child_age" type="number" class="field" /></Field>
                <Field id="e_grade" label="School grade" :error="edit.errors.child_grade"><input id="e_grade" v-model="edit.child_grade" class="field" /></Field>
            </div>
            <Field id="e_email" label="Email" :error="edit.errors.email"><input id="e_email" v-model="edit.email" class="field" /></Field>
            <Field id="e_interest" label="Interest" :error="edit.errors.interest">
                <select id="e_interest" v-model="edit.interest" class="field"><option value="">Not sure yet</option><option v-for="i in interests" :key="i" :value="i">{{ i }}</option></select>
            </Field>
            <Field id="e_source" label="Source" :error="edit.errors.source">
                <select id="e_source" v-model="edit.source" class="field"><option value="">–</option><option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option></select>
            </Field>
            <Field id="e_time" label="Preferred time"><input id="e_time" v-model="edit.preferred_time" class="field" /></Field>
            <Field id="e_msg" label="Message" class="sm:col-span-2"><textarea id="e_msg" v-model="edit.message" rows="2" class="field" /></Field>
            <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn-ghost" @click="editing = false">Cancel</button><button class="btn-primary" :disabled="edit.processing">Save</button></div>
        </form>
    </Modal>
</template>
