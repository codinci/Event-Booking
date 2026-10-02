<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh items-center justify-center bg-[#fffaf5] px-4 py-4"
    >
        <div class="w-full max-w-md">
            <AuthLayout
                :title="title"
                :description="description"
            >
                <slot />
            </AuthLayout>
        </div>
    </div>
</template>
