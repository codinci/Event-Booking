<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();

const user = computed(() => page.props.auth.user);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors outline-none hover:bg-neutral-100 focus-visible:ring-2 focus-visible:ring-neutral-400 dark:hover:bg-neutral-800"
                data-test="topbar-user-button"
            >
                <UserInfo :user="user" />

                <ChevronsUpDown class="size-4 shrink-0 text-neutral-500" />
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
