<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';

import { dashboard, login, register } from '@/routes';

const page = usePage();

const user = computed(() => page.props.auth?.user);
</script>

<template>
    <header class="border-b border-line bg-surface">
        <div
            class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8"
        >
            <!-- Brand -->
            <Link href="/" class="flex items-center gap-3 text-foreground">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand text-white shadow-sm"
                >
                    <AppLogoIcon class="h-5 w-5" />
                </div>

                <span class="text-xl font-bold tracking-tight text-foreground">
                    Gather
                </span>
            </Link>

            <!-- Navigation -->
            <nav class="flex items-center gap-2">
                <Link
                    href="/events"
                    class="hidden rounded-full px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-hover hover:text-brand sm:inline-block"
                >
                    Explore Events
                </Link>

                <template v-if="user">
                    <Link
                        v-if="user.role === 'organizer'"
                        :href="dashboard()"
                        class="rounded-full px-4 py-2.5 text-sm font-semibold text-foreground/80 transition hover:bg-hover sm:inline-block"
                    >
                        Dashboard
                    </Link>

                    <Link
                        v-else
                        href="/events"
                        class="hidden rounded-full px-4 py-2.5 text-sm font-semibold text-foreground/80 transition hover:bg-hover sm:inline-block"
                    >
                        Past Events
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-left outline-none transition hover:bg-hover focus-visible:ring-2 focus-visible:ring-brand/40"
                                data-test="topbar-user-button"
                            >
                                <UserInfo :user="user" />

                                <ChevronsUpDown
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                            </button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            class="w-64 min-w-56 rounded-lg"
                            align="end"
                            :side-offset="8"
                        >
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-full px-4 py-2.5 text-sm font-semibold text-foreground/80 transition hover:bg-hover"
                    >
                        Log in
                    </Link>

                    <Link
                        :href="register()"
                        class="rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-hover"
                    >
                        Sign up
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>
