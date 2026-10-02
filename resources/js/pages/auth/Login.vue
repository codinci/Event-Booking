```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */

import { store } from '@/routes/login';
import { request } from '@/routes/password';

/* @chisel-passkeys */
import PasskeyVerify from '@/components/PasskeyVerify.vue';
/* @end-chisel-passkeys */

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <!-- Status -->
    <div
        v-if="status"
        class="mb-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-center text-sm text-green-700"
    >
        {{ status }}
    </div>

    <!-- Passkeys -->
    <!-- @chisel-passkeys -->
    <PasskeyVerify />
    <!-- @end-chisel-passkeys -->

    <!-- Login Card -->
    <div
        class="rounded-2xl border border-[#eadfd4] bg-white p-4 shadow-sm"
    >
        <!-- Login with email label -->
        <div
            class="mb-4 rounded-lg bg-[#fffaf5] px-3 py-2.5 text-center"
        >
            <p class="text-sm font-medium text-[#625b55]">
                Or log in with email
            </p>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
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
                        Email address
                    </Label>

                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="h-9 border-[#eadfd4] bg-[#fffaf5] text-sm text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                    />

                    <InputError :message="errors.email" />
                </div>

                <!-- Password -->
                <div class="grid gap-1">
                    <div class="flex items-center justify-between">
                        <Label
                            for="password"
                            class="text-sm font-medium text-[#2d2926]"
                        >
                            Password
                        </Label>

                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs font-medium text-[#e05f35] hover:text-[#d4512b]"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>

                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />

                    <InputError :message="errors.password" />
                </div>

                <!-- Remember me -->
                <div class="flex items-center">
                    <Label
                        for="remember"
                        class="flex items-center gap-2 text-sm text-[#625b55]"
                    >
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="data-[state=checked]:border-[#e9784f] data-[state=checked]:bg-[#e9784f]"
                        />

                        <span>Remember me</span>
                    </Label>
                </div>

                <!-- Submit -->
                <Button
                    type="submit"
                    class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </div>

            <!-- Registration -->
            <!-- @chisel-registration -->
            <div
                class="border-t border-[#eadfd4] pt-3 text-center text-sm text-[#625b55]"
            >
                Don't have an account?

                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="font-semibold text-[#e05f35] hover:text-[#d4512b]"
                >
                    Sign up
                </TextLink>
            </div>
            <!-- @end-chisel-registration -->
        </Form>
    </div>

    <p class="mt-3 text-center text-xs text-[#9a918a]">
        Discover events. Reserve your spot. Gather together.
    </p>
</template>


