<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>
<template>
    <Head title="Forgot password" />

    <!-- Status -->
    <div
        v-if="status"
        class="mb-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-center text-sm font-medium text-green-700"
    >
        {{ status }}
    </div>

    <div class="space-y-4">
        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
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
                    autocomplete="email"
                    v-focus
                    placeholder="email@example.com"
                    class="h-9 border-[#eadfd4] bg-[#fffaf5] text-sm text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.email" />
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                <Spinner v-if="processing" />
                Email password reset link
            </Button>
        </Form>

        <!-- Return to login -->
        <div
            class="border-t border-[#eadfd4] pt-3 text-center text-sm text-[#625b55]"
        >
            <span>Or, return to </span>

            <TextLink
                :href="login()"
                class="font-semibold text-[#e05f35] hover:text-[#d4512b]"
            >
                log in
            </TextLink>
        </div>
    </div>
</template>
