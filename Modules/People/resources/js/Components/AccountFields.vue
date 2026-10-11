<script setup>
import Field from '@/Components/ui/Field.vue';

const props = defineProps({
    form: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    emailRequired: { type: Boolean, default: false },
});
</script>

<template>
    <Field id="name" label="Full name" :error="form.errors.name" required class="sm:col-span-2">
        <input id="name" v-model="form.name" type="text" class="field" required />
    </Field>
    <Field id="username" label="Username" :error="form.errors.username" hint="Used to log in. Lowercase letters, numbers, - or _.">
        <input id="username" v-model="form.username" type="text" class="field font-mono" autocomplete="off" />
    </Field>
    <Field id="email" label="Email" :error="form.errors.email" :required="emailRequired">
        <input id="email" v-model="form.email" type="email" class="field" autocomplete="off" />
    </Field>
    <Field id="phone" label="Phone / WhatsApp" :error="form.errors.phone">
        <input id="phone" v-model="form.phone" type="tel" class="field" placeholder="08xx" />
    </Field>
    <Field id="status" label="Account status" :error="form.errors.status">
        <select id="status" v-model="form.status" class="field">
            <option value="active">Active</option>
            <option value="inactive">Inactive (cannot log in)</option>
        </select>
    </Field>
    <Field
        id="password"
        label="Password"
        :error="form.errors.password"
        :required="!editing"
        :hint="editing ? 'Leave empty to keep the current password.' : 'At least 8 characters.'"
        class="sm:col-span-2"
    >
        <input id="password" v-model="form.password" type="text" class="field font-mono" autocomplete="new-password" />
    </Field>
</template>
