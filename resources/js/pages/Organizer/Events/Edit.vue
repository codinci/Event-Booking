<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CancelEventButton from '@/components/CancelEventButton.vue';
import OrganizerEventForm from '@/components/OrganizerEventForm.vue';
import { update } from '@/routes/organizer/events';

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
    <main class="mx-auto w-full max-w-xl space-y-5 px-6 py-10">
        <h1 class="text-2xl font-semibold">Edit event</h1>
        <OrganizerEventForm :event="event" :action="update(event.id).url" method="put" />
        <section v-if="event.status !== 'cancelled'" class="space-y-2 border-t pt-5" aria-label="Cancel event">
            <h2 class="font-medium">Cancel this event</h2>
            <p class="text-sm text-muted-foreground">
                Unpaid reservations are released. Paid bookings are cancelled and refunded, minus the platform fee, after admin approval.
            </p>
            <CancelEventButton :event="event" />
        </section>
        <p v-else class="border-t pt-5 text-sm text-muted-foreground">This event has been cancelled.</p>
    </main>
</template>
