<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { rupiah } from '@/lib/format';

const props = defineProps({ packages: Array, studentPackages: Object, filters: Object, students: Array, classes: Array, statuses: Object });

const filters = useFilters({ search: props.filters.search ?? '', status: props.filters.status ?? '' }, 'finance.packages.index');
const confirm = useConfirm();

// Package catalogue
const editing = ref(null);
const pkgForm = useForm({ name: '', sessions_count: 8, price: 600000, valid_days: 60, description: '', is_active: true });
const openPackage = (pkg = null) => {
    editing.value = pkg ?? {};
    pkgForm.defaults(pkg ? { ...pkg, description: pkg.description ?? '' } : { name: '', sessions_count: 8, price: 600000, valid_days: 60, description: '', is_active: true });
    pkgForm.reset();
    pkgForm.clearErrors();
};
const savePackage = () => {
    const options = { preserveScroll: true, onSuccess: () => (editing.value = null) };
    editing.value?.id ? pkgForm.put(route('finance.packages.update', editing.value.id), options) : pkgForm.post(route('finance.packages.store'), options);
};

// Sell a package to a student
const selling = ref(false);
const sellForm = useForm({ package_id: '', student_id: '', class_room_id: '', due_date: new Date(Date.now() + 7 * 864e5).toLocaleDateString('sv-SE'), activate_now: false });
const openSell = (pkg = null) => {
    sellForm.reset();
    sellForm.package_id = pkg?.id ?? '';
    selling.value = true;
};
const sell = () => sellForm.post(route('finance.packages.assign'), { preserveScroll: true, onSuccess: () => (selling.value = false) });

const statusTone = { active: 'active', pending_payment: 'unpaid', used_up: 'inactive', expired: 'inactive', cancelled: 'cancelled' };
</script>

<template>
    <Head title="Session packages" />

    <PageHeader eyebrow="Finance" title="Session packages" description="Parents pay for a package up front. Each attended session is taken off the quota automatically.">
        <template #actions>
            <button class="btn-ghost" @click="openPackage()"><Icon name="plus" :size="18" /> New package</button>
            <button class="btn-accent" @click="openSell()"><Icon name="ticket" :size="18" /> Sell package</button>
        </template>
    </PageHeader>

    <!-- Catalogue -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="pkg in packages" :key="pkg.id" :class="!pkg.is_active && 'opacity-60'" class="card flex flex-col p-5">
            <div class="flex items-start justify-between">
                <p class="font-display text-lg font-bold text-ink">{{ pkg.name }}</p>
                <button class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink" title="Edit" @click="openPackage(pkg)"><Icon name="pencil" :size="15" /></button>
            </div>
            <p class="mt-2 font-display text-3xl font-extrabold text-brand-600">{{ pkg.sessions_count }}<span class="text-base font-semibold text-muted"> sessions</span></p>
            <p class="font-semibold text-ink">{{ rupiah(pkg.price) }}</p>
            <p class="text-xs text-muted">{{ rupiah(Math.round(pkg.price / pkg.sessions_count)) }}/session · valid for {{ pkg.valid_days }} days</p>
            <p v-if="pkg.description" class="mt-2 text-sm text-muted">{{ pkg.description }}</p>
            <button v-if="pkg.is_active" class="btn-ghost mt-4 py-2" @click="openSell(pkg)">Sell to a student</button>
            <p v-else class="mt-4 text-center text-xs font-semibold text-muted">Not for sale</p>
        </div>
        <button v-if="!packages.length" class="card grid place-items-center border-dashed p-8 text-muted hover:text-brand-600" @click="openPackage()">
            <span class="text-center"><Icon name="plus" class="mx-auto" /> <span class="mt-2 block text-sm font-semibold">Create your first package</span></span>
        </button>
    </div>

    <!-- Student packages -->
    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <h2 class="font-display font-bold text-ink sm:mr-auto">Student packages</h2>
            <SearchInput v-model="filters.search" placeholder="Search student name…" />
            <select v-model="filters.status" class="field sm:w-52">
                <option value="">All statuses</option>
                <option value="low">Running low</option>
                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <div v-if="studentPackages.data.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-chip/60 text-xs font-bold tracking-wide text-muted uppercase">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Package</th>
                        <th class="px-5 py-3">Usage</th>
                        <th class="px-5 py-3">Valid until</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="p in studentPackages.data" :key="p.id">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2"><Avatar :initials="p.student.initials" :seed="p.student.id" size="sm" /><span class="font-semibold text-ink">{{ p.student.name }}</span></div>
                        </td>
                        <td class="px-5 py-3"><p class="text-ink">{{ p.name }}</p><p v-if="p.class" class="text-xs text-muted">{{ p.class }}</p></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-chip">
                                    <div class="h-full rounded-full" :class="p.remaining <= 2 && p.status === 'active' ? 'bg-amber-400' : 'bg-brand-500'" :style="{ width: `${(p.used / p.total) * 100}%` }" />
                                </div>
                                <span class="text-xs font-semibold text-ink">{{ p.used }}/{{ p.total }}</span>
                            </div>
                            <p v-if="p.status === 'active'" class="text-xs" :class="p.remaining <= 2 ? 'font-semibold text-amber-700' : 'text-muted'">{{ p.remaining }} session(s) left</p>
                        </td>
                        <td class="px-5 py-3 text-muted">{{ p.expires_at ?? '–' }}</td>
                        <td class="px-5 py-3"><StatusBadge :status="statusTone[p.status]" :label="p.status_label" /></td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <Link v-if="p.invoice_id" :href="route('finance.invoices.show', p.invoice_id)" class="text-xs font-semibold text-brand-600 hover:underline">Invoice</Link>
                            <button
                                v-if="p.used === 0 && ['active', 'pending_payment'].includes(p.status)"
                                class="ml-3 text-xs font-semibold text-rose-600 hover:underline"
                                @click="confirm.ask({ title: 'Cancel this package?', message: `${p.name} for ${p.student.name}.`, confirmText: 'Yes, cancel it', method: 'post', url: route('finance.packages.cancel', p.id) })"
                            >
                                Cancel
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else icon="ticket" title="No student packages yet" description="Sell a package to a student. It becomes active once its invoice is paid." />
        <Pagination :meta="studentPackages" />
    </div>

    <!-- Package modal -->
    <Modal :show="!!editing" max-width="lg" @close="editing = null">
        <form class="p-6" @submit.prevent="savePackage">
            <h3 class="mb-5 font-display text-lg font-bold text-ink">{{ editing?.id ? 'Edit package' : 'New package' }}</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field id="p_name" label="Package name" :error="pkgForm.errors.name" required class="sm:col-span-2">
                    <input id="p_name" v-model="pkgForm.name" type="text" class="field" placeholder="e.g. Regular 8 sessions" />
                </Field>
                <Field id="p_sessions" label="Number of sessions" :error="pkgForm.errors.sessions_count" required>
                    <input id="p_sessions" v-model.number="pkgForm.sessions_count" type="number" min="1" class="field" />
                </Field>
                <Field id="p_valid" label="Valid for (days)" :error="pkgForm.errors.valid_days" required>
                    <input id="p_valid" v-model.number="pkgForm.valid_days" type="number" min="1" class="field" />
                </Field>
                <Field id="p_price" label="Price (Rp)" :error="pkgForm.errors.price" required :hint="`${rupiah(pkgForm.price)} · enter 0 for a free trial class`" class="sm:col-span-2">
                    <input id="p_price" v-model.number="pkgForm.price" type="number" min="0" step="1000" class="field" />
                </Field>
                <Field id="p_desc" label="Description" :error="pkgForm.errors.description" class="sm:col-span-2">
                    <input id="p_desc" v-model="pkgForm.description" type="text" class="field" />
                </Field>
                <label class="flex items-center gap-2 text-sm text-ink sm:col-span-2">
                    <input v-model="pkgForm.is_active" type="checkbox" class="rounded border-line text-brand-600" /> For sale
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="editing = null">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="pkgForm.processing">Save</button>
            </div>
        </form>
    </Modal>

    <!-- Sell modal -->
    <Modal :show="selling" max-width="lg" @close="selling = false">
        <form class="p-6" @submit.prevent="sell">
            <h3 class="font-display text-lg font-bold text-ink">Sell a package to a student</h3>
            <p class="mb-5 text-sm text-muted">An invoice is created automatically. The package becomes active once the invoice is paid.</p>
            <div class="grid gap-4">
                <Field id="s_pkg" label="Package" :error="sellForm.errors.package_id" required>
                    <select id="s_pkg" v-model="sellForm.package_id" class="field">
                        <option value="">Choose a package…</option>
                        <option v-for="p in packages.filter((p) => p.is_active)" :key="p.id" :value="p.id">{{ p.name }} · {{ p.sessions_count }} sessions · {{ rupiah(p.price) }}</option>
                    </select>
                </Field>
                <Field id="s_student" label="Student" :error="sellForm.errors.student_id" required>
                    <select id="s_student" v-model="sellForm.student_id" class="field">
                        <option value="">Choose a student…</option>
                        <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </Field>
                <Field id="s_class" label="Only for class" :error="sellForm.errors.class_room_id" hint="Leave empty so the quota works for all of the student's classes.">
                    <select id="s_class" v-model="sellForm.class_room_id" class="field">
                        <option value="">All classes</option>
                        <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </Field>
                <Field id="s_due" label="Invoice due date" :error="sellForm.errors.due_date" required>
                    <input id="s_due" v-model="sellForm.due_date" type="date" class="field" />
                </Field>
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input v-model="sellForm.activate_now" type="checkbox" class="rounded border-line text-brand-600" /> Activate now without waiting for payment
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="selling = false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="sellForm.processing">Create package</button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
