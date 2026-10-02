<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Reset password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <div class="grid gap-4">
            <!-- Email -->
            <div class="grid gap-1">
                <Label
                    for="email"
                    class="text-sm font-medium text-[#2d2926]"
                >
                    Email
                </Label>

                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    readonly
                    class="h-9 border-[#eadfd4] bg-[#f7f0ea] text-sm text-[#625b55] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.email" />
            </div>

            <!-- Password -->
            <div class="grid gap-1">
                <Label
                    for="password"
                    class="text-sm font-medium text-[#2d2926]"
                >
                    Password
                </Label>

                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    placeholder="Password"
                    :passwordrules="passwordRules"
                    class="border-[#eadfd4] bg-[#fffaf5] text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.password" />
            </div>

            <!-- Confirm password -->
            <div class="grid gap-1">
                <Label
                    for="password_confirmation"
                    class="text-sm font-medium text-[#2d2926]"
                >
                    Confirm password
                </Label>

                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                    class="border-[#eadfd4] bg-[#fffaf5] text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                Reset password
            </Button>
        </div>
    </Form>
</template>
