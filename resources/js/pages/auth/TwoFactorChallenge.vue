<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            buttonText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication code',
        description:
            'Enter the authentication code provided by your authenticator application.',
        buttonText: 'login using a recovery code',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <Head title="Two-factor authentication" />

    <div class="space-y-4">
        <!-- Authentication code -->
        <template v-if="!showRecoveryInput">
            <Form
                v-bind="store.form()"
                class="flex flex-col gap-4"
                reset-on-error
                @error="code = ''"
                #default="{ errors, processing, clearErrors }"
            >
                <input
                    type="hidden"
                    name="code"
                    :value="code"
                />

                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-full">
                        <p class="mb-3 text-sm text-[#625b55]">
                            Enter the 6-digit authentication code from your
                            authenticator app.
                        </p>

                        <div class="flex w-full justify-center">
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="processing"
                                autofocus
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                        class="border-[#eadfd4] text-[#2d2926] focus-within:border-[#e9784f] focus-within:ring-[#f4c9b8]"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                    </div>

                    <InputError :message="errors.code" />
                </div>

                <Button
                    type="submit"
                    class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                    :disabled="processing"
                >
                    Continue
                </Button>

                <div
                    class="border-t border-[#eadfd4] pt-3 text-center text-sm text-[#625b55]"
                >
                    <span>Or you can </span>

                    <button
                        type="button"
                        class="font-semibold text-[#e05f35] underline underline-offset-4 transition-colors hover:text-[#d4512b]"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>

        <!-- Recovery code -->
        <template v-else>
            <Form
                v-bind="store.form()"
                class="flex flex-col gap-4"
                reset-on-error
                #default="{ errors, processing, clearErrors }"
            >
                <div class="grid gap-1">
                    <label
                        for="recovery_code"
                        class="text-sm font-medium text-[#2d2926]"
                    >
                        Recovery code
                    </label>

                    <Input
                        id="recovery_code"
                        name="recovery_code"
                        type="text"
                        placeholder="Enter recovery code"
                        v-focus
                        required
                        class="h-9 border-[#eadfd4] bg-[#fffaf5] text-sm text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                    />

                    <InputError :message="errors.recovery_code" />
                </div>

                <Button
                    type="submit"
                    class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                    :disabled="processing"
                >
                    Continue
                </Button>

                <div
                    class="border-t border-[#eadfd4] pt-3 text-center text-sm text-[#625b55]"
                >
                    <span>Or you can </span>

                    <button
                        type="button"
                        class="font-semibold text-[#e05f35] underline underline-offset-4 transition-colors hover:text-[#d4512b]"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>
    </div>
</template>
