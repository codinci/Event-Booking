<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login } from '@/routes'

/* @chisel-registration */
import { register } from '@/routes'
/* @end-chisel-registration */
</script>

<template>
    <Head title="Discover Events" />

    <div class="min-h-screen bg-[#fffaf5] text-[#2d2926]">
        <!-- Navigation -->
        <header class="border-b border-[#eadfd4] bg-[#fffaf5]/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
                <!-- Brand -->
                <Link href="/" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e9784f] text-white shadow-sm"
                    >
						<AppLogoIcon />
                    </div>

                    <span class="text-xl font-bold tracking-tight">
                        Gather
                    </span>
                </Link>

                <!-- Navigation -->
                <nav class="flex items-center gap-3">
                    <Link
                        href="/events"
                        class="hidden px-4 py-2 text-sm font-medium text-[#625b55] transition hover:text-[#e05f35] sm:inline-block"
                    >
                        Explore Events
                    </Link>

                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="dashboard()"
                            class="rounded-full bg-[#2d2926] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#45403c]"
                        >
                            Dashboard
                        </Link>
                    </template>

                    <template v-else>
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
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <main>
            <section class="relative overflow-hidden">
                <!-- Decorative shapes -->
                <div
                    class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-[#f9d9c9] opacity-60 blur-3xl"
                ></div>

                <div
                    class="absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-[#f3e3c5] opacity-50 blur-3xl"
                ></div>

                <div
                    class="relative mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28"
                >
                    <!-- Hero copy -->
                    <div>
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#ead5c8] bg-white px-4 py-2 text-sm font-medium text-[#a05237] shadow-sm"
                        >
                            <span class="h-2 w-2 rounded-full bg-[#e9784f]"></span>
                            Find something worth remembering
                        </div>

                        <h1
                            class="max-w-2xl text-5xl font-bold leading-[1.05] tracking-tight text-[#292522] sm:text-6xl lg:text-7xl"
                        >
                            Great moments
                            <span class="text-[#e9784f]">start here.</span>
                        </h1>

                        <p
                            class="mt-7 max-w-xl text-lg leading-8 text-[#756c65]"
                        >
                            Discover concerts, workshops, conferences, experiences,
                            and local gatherings. Find an event you love and reserve
                            your spot in just a few clicks.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="/events"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#e9784f] px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#e9784f]/20 transition hover:-translate-y-0.5 hover:bg-[#d9653d]"
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
                                class="inline-flex items-center justify-center rounded-full border border-[#d9c9bd] bg-white px-7 py-3.5 text-base font-semibold text-[#4c4540] transition hover:border-[#c9b5a7] hover:bg-[#faf4ef]"
                            >
                                Create an account
                            </Link>
                        </div>

                        <p class="mt-5 text-sm text-[#92877f]">
                            No account needed to browse events.
                        </p>
                    </div>

                    <!-- Hero visual -->
                    <div class="relative">
                        <div
                            class="relative mx-auto max-w-lg rotate-1 rounded-[2rem] bg-[#f2dfd2] p-4 shadow-2xl shadow-[#6d4d3c]/10"
                        >
                            <div
                                class="overflow-hidden rounded-[1.5rem] bg-white"
                            >
                                <!-- Event visual -->
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
                                            <p class="text-sm font-semibold text-[#342f2b]">
                                                Friday, 18 October
                                            </p>

                                            <p class="mt-1 text-sm text-[#8a8078]">
                                                The Garden Venue · 6:00 PM
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-xs text-[#9a8d84]">
                                                From
                                            </p>

                                            <p class="text-lg font-bold text-[#e9784f]">
                                                KSh 1,500
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-center justify-between">
                                        <div class="flex -space-x-2">
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-white bg-[#d9b09c]"
                                            ></div>
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-white bg-[#9d7663]"
                                            ></div>
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-white bg-[#e4c7a9]"
                                            ></div>
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-[#f1e8e2] text-xs font-semibold text-[#71665e]"
                                            >
                                                +42
                                            </div>
                                        </div>

                                        <span class="text-sm font-medium text-[#81766e]">
                                            People are going
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating card -->
                        <div
                            class="absolute -bottom-5 -left-3 rounded-2xl border border-[#eadfd4] bg-white p-4 shadow-xl sm:-left-8"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e7f1e8] text-[#4e8060]"
                                >
                                    ✓
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-[#3a3531]">
                                        Booking confirmed
                                    </p>

                                    <p class="text-xs text-[#92877f]">
                                        Your next memory awaits
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why Gather -->
            <section class="border-y border-[#eadfd4] bg-white">
                <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#e9784f]">
                            Simple by design
                        </p>

                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-[#302b27] sm:text-4xl">
                            From discovery to memories.
                        </h2>

                        <p class="mt-4 text-[#756c65]">
                            Everything you need to find an event, secure your ticket,
                            or bring your own event to life.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 md:grid-cols-3">
                        <!-- Feature -->
                        <div
                            class="rounded-3xl bg-[#fff8f3] p-7"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f9dfd1] text-[#d9653d]"
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

                            <h3 class="mt-6 text-xl font-bold text-[#302b27]">
                                Discover
                            </h3>

                            <p class="mt-3 leading-7 text-[#786e67]">
                                Browse upcoming events without creating an account.
                                Find something interesting and explore the details first.
                            </p>
                        </div>

                        <!-- Feature -->
                        <div
                            class="rounded-3xl bg-[#fffaf0] p-7"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f5e7bd] text-[#b18427]"
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

                            <h3 class="mt-6 text-xl font-bold text-[#302b27]">
                                Book
                            </h3>

                            <p class="mt-3 leading-7 text-[#786e67]">
                                Create an account when you're ready to book and keep
                                all your upcoming and past bookings in one place.
                            </p>
                        </div>

                        <!-- Feature -->
                        <div
                            class="rounded-3xl bg-[#f5f8f4] p-7"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#dcebdc] text-[#4e8060]"
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

                            <h3 class="mt-6 text-xl font-bold text-[#302b27]">
                                Organize
                            </h3>

                            <p class="mt-3 leading-7 text-[#786e67]">
                                Have an event of your own? Register as an organizer,
                                create your event, manage tickets, and track your sales.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Organizer CTA -->
            <section class="bg-[#2d2926]">
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
                        class="shrink-0 rounded-full bg-[#e9784f] px-7 py-3.5 font-semibold text-white transition hover:bg-[#f08a62]"
                    >
                        Become an organizer
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-[#fffaf5]">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-[#887d75] sm:flex-row sm:items-center sm:justify-between lg:px-8"
            >
                <p>
                    © {{ new Date().getFullYear() }} Gather. Made for good times.
                </p>

                <div class="flex gap-5">
                    <Link
                        href="/events"
                        class="transition hover:text-[#e9784f]"
                    >
                        Events
                    </Link>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="login()"
                        class="transition hover:text-[#e9784f]"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="register()"
                        class="transition hover:text-[#e9784f]"
                    >
                        Register
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
