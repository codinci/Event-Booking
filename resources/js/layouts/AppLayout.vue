<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppPublicLayout from '@/layouts/app/AppPublicLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

const layout = computed(() =>
    page.props.auth.user?.role === 'organizer'
        ? AppSidebarLayout
        : AppPublicLayout,
);
</script>

<template>
    <component :is="layout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>