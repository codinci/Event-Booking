```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';

import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col bg-[#fffaf5] px-6 py-6 md:px-10 md:py-8"
    >
        <div class="mx-auto w-full max-w-sm">
            <div class="flex flex-col gap-5">
                <!-- Logo and heading -->
                <div class="flex flex-col items-center gap-3">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-1.5 font-medium"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#e9784f] text-white shadow-sm"
                        >
                            <AppLogoIcon
                                class="size-7 fill-current text-white"
                            />
                        </div>

                        <span
                            class="text-xl font-bold tracking-tight text-[#2d2926]"
                        >
                            Gather
                        </span>
                    </Link>

                    <div class="space-y-1 text-center">
                        <h1 class="text-xl font-semibold text-[#2d2926]">
                            {{ title }}
                        </h1>

                        <p class="text-sm text-[#625b55]">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <!-- Page content -->
                <div
                    class="rounded-2xl border border-[#eadfd4] bg-white p-5 shadow-sm sm:p-6"
                >
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
