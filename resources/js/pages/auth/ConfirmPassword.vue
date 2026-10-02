<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
/* @chisel-passkeys */
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
/* @end-chisel-passkeys */

defineOptions({
    layout: {
        title: 'Confirm password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    },
});
</script>

<template>
    <Head title="Confirm password" />

    <!-- Passkeys -->
    <!-- @chisel-passkeys -->
    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Confirm with passkey"
        loading-label="Confirming..."
        separator="Or confirm with password"
    />
    <!-- @end-chisel-passkeys -->

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <div class="grid gap-4">
            <!-- Password -->
            <div class="grid gap-1">
                <Label
                    htmlFor="password"
                    class="text-sm font-medium text-[#2d2926]"
                >
                    Password
                </Label>

                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="Enter your password"
                    class="border-[#eadfd4] bg-[#fffaf5] text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.password" />
            </div>

            <!-- Confirm -->
            <Button
                type="submit"
                class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                :disabled="processing"
                data-test="confirm-password-button"
            >
                <Spinner v-if="processing" />
                Confirm password
            </Button>
        </div>
    </Form>
</template>
