<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { cancel } from '@/routes/organizer/events';

const props = defineProps<{
    event: { id: number; title: string; paid_bookings_count: number };
}>();

const processing = ref(false);

const cancelEvent = () => {
    const count = props.event.paid_bookings_count;
    const refunds = count
        ? ` ${count} paid ${count === 1 ? 'booking' : 'bookings'} will be cancelled and refunded (minus the platform fee) after admin approval.`
        : '';

    if (confirm(`Cancel "${props.event.title}"? This can't be undone.${refunds}`)) {
        processing.value = true;
        router.post(cancel(props.event.id).url, {}, { onFinish: () => (processing.value = false) });
    }
};
</script>

<template>
    <button
        type="button"
        class="rounded-lg border border-destructive/40 px-4 py-2 text-sm font-semibold text-destructive transition hover:bg-destructive/10 disabled:opacity-50"
        :disabled="processing"
        :aria-label="`Cancel ${event.title}`"
        @click="cancelEvent"
    >
        Cancel event
    </button>
</template>
