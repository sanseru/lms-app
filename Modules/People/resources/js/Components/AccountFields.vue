<script setup>
import Field from '@/Components/ui/Field.vue';

const props = defineProps({
    form: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    emailRequired: { type: Boolean, default: false },
});
</script>

<template>
    <Field id="name" label="Nama lengkap" :error="form.errors.name" required class="sm:col-span-2">
        <input id="name" v-model="form.name" type="text" class="field" required />
    </Field>
    <Field id="username" label="Username" :error="form.errors.username" hint="Dipakai untuk login. Huruf kecil, angka, - atau _.">
        <input id="username" v-model="form.username" type="text" class="field font-mono" autocomplete="off" />
    </Field>
    <Field id="email" label="Email" :error="form.errors.email" :required="emailRequired">
        <input id="email" v-model="form.email" type="email" class="field" autocomplete="off" />
    </Field>
    <Field id="phone" label="Nomor HP / WhatsApp" :error="form.errors.phone">
        <input id="phone" v-model="form.phone" type="tel" class="field" placeholder="08xx" />
    </Field>
    <Field id="status" label="Status akun" :error="form.errors.status">
        <select id="status" v-model="form.status" class="field">
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif (tidak bisa login)</option>
        </select>
    </Field>
    <Field
        id="password"
        label="Kata sandi"
        :error="form.errors.password"
        :required="!editing"
        :hint="editing ? 'Kosongkan jika tidak ingin mengganti.' : 'Minimal 8 karakter.'"
        class="sm:col-span-2"
    >
        <input id="password" v-model="form.password" type="text" class="field font-mono" autocomplete="new-password" />
    </Field>
</template>
