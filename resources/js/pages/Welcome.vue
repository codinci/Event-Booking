<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import TopBar from '@/components/TopBar.vue';

import { dashboard, login } from '@/routes'

/* @chisel-registration */
import { register } from '@/routes'
/* @end-chisel-registration */
</script>

<template>
    <Head title="Discover Events" />

    <div class="min-h-screen bg-surface text-foreground">
        <!-- Navigation -->
        <TopBar />

        <!-- Hero -->
        <main>
            <section class="relative overflow-hidden">
                <!-- Decorative shapes -->
                <div
                    class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-brand/25 blur-3xl dark:bg-brand/15"
                ></div>

                <div
                    class="absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-amber-300/40 blur-3xl dark:bg-amber-500/10"
                ></div>

                <div
                    class="relative mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28"
                >
                    <!-- Hero copy -->
                    <div>
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-line bg-card px-4 py-2 text-sm font-medium text-[#a05237] shadow-sm dark:text-[#f2a17e]"
                        >
                            <span class="h-2 w-2 rounded-full bg-brand"></span>
                            Find something worth remembering
                        </div>

                        <h1
                            class="max-w-2xl text-5xl font-bold leading-[1.05] tracking-tight text-foreground sm:text-6xl lg:text-7xl"
                        >
                            Great moments
                            <span class="text-brand">start here.</span>
                        </h1>

                        <p
                            class="mt-7 max-w-xl text-lg leading-8 text-muted-foreground"
                        >
                            Discover concerts, workshops, conferences, experiences,
                            and local gatherings. Find an event you love and reserve
                            your spot in just a few clicks.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="/events"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-brand px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand/20 transition hover:-translate-y-0.5 hover:bg-brand-hover"
                            >
                                Explore events

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                                    />
                                </svg>
                            </Link>

                            <Link
                                :href="register()"
                                class="inline-flex items-center justify-center rounded-full border border-line bg-card px-7 py-3.5 text-base font-semibold text-foreground transition hover:bg-hover"
                            >
                                Create an account
                            </Link>
                        </div>

                        <p class="mt-5 text-sm text-muted-foreground/80">
                            No account needed to browse events.
                        </p>
                    </div>

                    <!-- Hero visual -->
                    <div class="relative">
                        <div
                            class="relative mx-auto max-w-lg rotate-1 rounded-[2rem] bg-brand/15 p-4 shadow-2xl shadow-black/10"
                        >
                            <div
                                class="overflow-hidden rounded-[1.5rem] bg-card"
                            >
                                <!-- Event visual (stays coral in both themes) -->
                                <div
                                    class="relative h-72 overflow-hidden bg-gradient-to-br from-[#e9784f] via-[#ec9b70] to-[#f4c9a9]"
                                >
                                    <div
                                        class="absolute -right-10 -top-16 h-52 w-52 rounded-full bg-white/20"
                                    ></div>

                                    <div
                                        class="absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-[#b94e35]/20"
                                    ></div>

                                    <div
                                        class="relative flex h-full flex-col justify-between p-7 text-white"
                                    >
                                        <div class="flex justify-between">
                                            <span
                                                class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur"
                                            >
                                                THIS WEEK
                                            </span>

                                            <span
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 backdrop-blur"
                                            >
                                                ♥
                                            </span>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-white/80">
                                                Live &amp; Local
                                            </p>

                                            <h2 class="mt-1 text-3xl font-bold">
                                                Sunset Sessions
                                            </h2>

                                            <p class="mt-2 text-sm text-white/85">
                                                Music · Food · Community
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">
                                                Friday, 18 October
                                            </p>

                                            <p class="mt-1 text-sm text-muted-foreground">
                                                The Garden Venue · 6:00 PM
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-xs text-muted-foreground">
                                                From
                                            </p>

                                            <p class="text-lg font-bold text-brand">
                                                KSh 1,500
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-center justify-between">
                                        <div class="flex -space-x-2">
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-card bg-[#d9b09c]"
                                            ></div>
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-card bg-[#9d7663]"
                                            ></div>
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-card bg-[#e4c7a9]"
                                            ></div>
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-card bg-muted text-xs font-semibold text-muted-foreground"
                                            >
                                                +42
                                            </div>
                                        </div>

                                        <span class="text-sm font-medium text-muted-foreground">
                                            People are going
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating card -->
                        <div
                            class="absolute -bottom-5 -left-3 rounded-2xl border border-line bg-card p-4 shadow-xl sm:-left-8"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                                >
                                    ✓
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-foreground">
                                        Booking confirmed
                                    </p>

                                    <p class="text-xs text-muted-foreground">
                                        Your next memory awaits
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why Gather -->
            <section class="border-y border-line bg-card">
                <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-brand">
                            Simple by design
                        </p>

                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            From discovery to memories.
                        </h2>

                        <p class="mt-4 text-muted-foreground">
                            Everything you need to find an event, secure your ticket,
                            or bring your own event to life.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 md:grid-cols-3">
                        <!-- Discover -->
                        <div class="rounded-3xl bg-brand/10 p-7">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand/20 text-brand"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                                    />
                                </svg>
                            </div>

                            <h3 class="mt-6 text-xl font-bold text-foreground">
                                Discover
                            </h3>

                            <p class="mt-3 leading-7 text-muted-foreground">
                                Browse upcoming events without creating an account.
                                Find something interesting and explore the details first.
                            </p>
                        </div>

                        <!-- Book -->
                        <div class="rounded-3xl bg-amber-500/10 p-7">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M15 5v2m0 10v2M5 9h2m10 0h2M7.8 7.8l1.4 1.4m5.6 5.6l1.4 1.4m0-8.4l-1.4 1.4m-5.6 5.6l-1.4 1.4M12 8a4 4 0 100 8 4 4 0 000-8z"
                                    />
                                </svg>
                            </div>

                            <h3 class="mt-6 text-xl font-bold text-foreground">
                                Book
                            </h3>

                            <p class="mt-3 leading-7 text-muted-foreground">
                                Create an account when you're ready to book and keep
                                all your upcoming and past bookings in one place.
                            </p>
                        </div>

                        <!-- Organize -->
                        <div class="rounded-3xl bg-emerald-500/10 p-7">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </div>

                            <h3 class="mt-6 text-xl font-bold text-foreground">
                                Organize
                            </h3>

                            <p class="mt-3 leading-7 text-muted-foreground">
                                Have an event of your own? Register as an organizer,
                                create your event, manage tickets, and track your sales.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Organizer CTA -->
            <section class="bg-[#2d2926] dark:bg-black/40">
                <div
                    class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-6 py-16 lg:flex-row lg:items-center lg:px-8"
                >
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#f2a17e]">
                            Have something to share?
                        </p>

                        <h2 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Turn your idea into an event people remember.
                        </h2>

                        <p class="mt-4 max-w-xl leading-7 text-[#c6bbb3]">
                            Register as an organizer and get the tools you need to
                            publish events, manage tickets, and see how your event is
                            performing.
                        </p>
                    </div>

                    <Link
                        :href="register()"
                        class="shrink-0 rounded-full bg-brand px-7 py-3.5 font-semibold text-white transition hover:bg-brand-hover"
                    >
                        Become an organizer
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-line bg-surface">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between lg:px-8"
            >
                <p>
                    © {{ new Date().getFullYear() }} Gather. Made for good times.
                </p>

                <div class="flex gap-5">
                    <Link
                        href="/events"
                        class="transition hover:text-brand"
                    >
                        Events
                    </Link>

                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="transition hover:text-brand"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="!$page.props.auth.user"
                        :href="register()"
                        class="transition hover:text-brand"
                    >
                        Register
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
