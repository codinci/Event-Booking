<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { login, register } from '@/routes'
import AppLogoIcon from '@/components/AppLogoIcon.vue';

</script>

<template>
    <header class="border-b border-[#eadfd4] bg-[#fffaf5]">
        <div
            class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8"
        >
            <!-- Brand -->
            <Link
                href="/"
                class="flex items-center gap-3 text-[#2d2926]"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e9784f] text-white shadow-sm"
                >
                    <AppLogoIcon class="h-5 w-5" />
                </div>

                <span class="text-xl font-bold tracking-tight text-[#2d2926]">
                    Gather
                </span>
            </Link>

            <!-- Navigation -->
            <nav class="flex items-center gap-3">

                <Link
                    :href="login()"
                    class="rounded-full px-4 py-2.5 text-sm font-semibold text-[#4b4540] transition hover:bg-[#f4ebe3]"
                >
                    Log in
                </Link>

                <Link
                    :href="register()"
                    class="rounded-full bg-[#e9784f] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9653d]"
                >
                    Sign up
                </Link>
            </nav>
        </div>
    </header>
</template>