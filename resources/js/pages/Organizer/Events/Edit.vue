<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CancelEventButton from '@/components/CancelEventButton.vue';
import OrganizerEventForm from '@/components/OrganizerEventForm.vue';
import { index, update } from '@/routes/organizer/events';

interface TicketType {
    id?: number;
    name: string;
    price: number | string;
    quantity: number | string;
    available_quantity?: number;
}

defineProps<{
    event: {
        id: number;
        title: string;
        description: string | null;
        venue: string;
        location: string | null;
        image_url: string | null;
        starts_at: string;
        ends_at: string;
        status: string;
        paid_bookings_count: number;
        ticket_types: TicketType[];
    };
}>();
</script>

<template>
    <Head title="Edit event" />
    <main class="mx-auto w-full max-w-2xl space-y-6 px-4 py-8">
        <Link
            :href="index().url"
            class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-hover"
        >
            &larr; Back to your events
        </Link>

        <h1 class="text-2xl font-bold text-foreground">Edit event</h1>

        <div class="overflow-hidden rounded-xl border border-line bg-card shadow-sm">
            <div class="h-2 bg-brand" />
            <div class="p-6">
                <OrganizerEventForm :event="event" :action="update(event.id).url" method="put" />
            </div>
        </div>

        <section
            v-if="event.status !== 'cancelled'"
            class="space-y-3 rounded-xl border border-destructive/30 bg-card p-6"
            aria-label="Cancel event"
        >
            <h2 class="text-lg font-semibold text-foreground">Cancel this event</h2>
            <p class="text-sm text-muted-foreground">
                Unpaid reservations are released. Paid bookings are cancelled and refunded, minus the platform fee, after admin approval.
            </p>
            <CancelEventButton :event="event" />
        </section>
        <p v-else class="rounded-xl border border-dashed border-line bg-card p-6 text-sm text-muted-foreground">
            This event has been cancelled.
        </p>
    </main>
</template>
