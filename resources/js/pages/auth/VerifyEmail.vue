<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />

    <div class="space-y-4">
        <!-- Success message -->
        <div
            v-if="status === 'verification-link-sent'"
            class="rounded-lg border border-green-200 bg-green-50 px-3 py-2.5 text-center text-sm font-medium text-green-700"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <Form
            v-bind="send.form()"
            class="flex flex-col gap-4"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                :disabled="processing"
                class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
            >
                <Spinner v-if="processing" />
                Resend verification email
            </Button>

            <div
                class="border-t border-[#eadfd4] pt-3 text-center"
            >
                <TextLink
                    :href="logout()"
                    as="button"
                    class="text-sm font-semibold text-[#e05f35] hover:text-[#d4512b]"
                >
                    Log out
                </TextLink>
            </div>
        </Form>
    </div>
</template>