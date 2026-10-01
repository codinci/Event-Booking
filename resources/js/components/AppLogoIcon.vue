<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15 5v2m0 10v2M5 9h2m10 0h2M7.8 7.8l1.4 1.4m5.6 5.6l1.4 1.4m0-8.4l-1.4 1.4m-5.6 5.6l-1.4 1.4M12 8a4 4 0 100 8 4 4 0 000-8z"
        />
    </svg>
</template>
