<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const isOrganizer = ref(false);

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>
<template>
    <Head title="Create account" />

    <Form
        v-bind="store.form()"
        :transform="(data) => ({
            ...data,
            is_organizer: isOrganizer,
        })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <div class="grid gap-4">
            <!-- Name -->
            <div class="grid gap-1">
                <Label
                    for="name"
                    class="text-sm font-medium text-[#2d2926]"
                >
                    Full name
                </Label>

                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                    class="h-9 border-[#eadfd4] bg-[#fffaf5] text-sm text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
                />

                <InputError :message="errors.name" />
            </div>

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
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    class="h-9 border-[#eadfd4] bg-[#fffaf5] text-sm text-[#2d2926] placeholder:text-[#9a918a] focus:border-[#e9784f] focus:ring-[#f4c9b8]"
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
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
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
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />

                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Organizer -->
            <div
                class="rounded-lg border border-[#eadfd4] bg-[#fffaf5] px-3 py-2.5"
            >
                <Label
                    for="is_organizer"
                    class="flex cursor-pointer items-start gap-2.5"
                >
                    <Checkbox
                        id="is_organizer"
                        name="is_organizer"
                        v-model="isOrganizer"
                        :tabindex="5"
                        class="mt-0.5 data-[state=checked]:border-[#e9784f] data-[state=checked]:bg-[#e9784f]"
                    />

                    <span class="flex flex-col gap-0.5">
                        <span
                            class="text-sm font-medium text-[#2d2926]"
                        >
                            Register as an organizer
                        </span>

                        <span class="text-xs text-[#625b55]">
                            Create and manage your own events.
                        </span>
                    </span>
                </Label>
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="h-9 w-full bg-[#e9784f] text-sm font-semibold text-white hover:bg-[#d9653d]"
                :tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <!-- Login -->
        <div
            class="border-t border-[#eadfd4] pt-3 text-center text-sm text-[#625b55]"
        >
            Already have an account?

            <TextLink
                :href="login()"
                class="font-semibold text-[#e05f35] hover:text-[#d4512b]"
                :tabindex="7"
            >
                Log in
            </TextLink>
        </div>
    </Form>
</template>
