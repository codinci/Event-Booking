<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface TicketType {
    id: number;
    name: string;
    price: string | number;
    available_quantity: number;
}

interface Event {
    id: number;
    title: string;
    description: string | null;
    venue: string | null;
    starts_at: string;
    ends_at: string;
    ticket_types: TicketType[];
}

const props = defineProps<{
    event: Event;
}>();

const availableTickets = computed(() =>
    props.event.ticket_types.reduce(
        (total, ticket) => total + ticket.available_quantity,
        0,
    ),
);

const hasTickets = computed(() => availableTickets.value > 0);

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatTime = (date: string) => {
    return new Date(date).toLocaleTimeString('en-KE', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })
};

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
    }).format(Number(price));
};
</script>

<template>
    <div class="min-h-screen bg-orange-50">
        <header class="bg-orange-500 px-4 py-10 text-white">
            <div class="mx-auto max-w-5xl">
                <Link
                    href="/events"
                    class="text-sm text-orange-100 hover:text-white"
                >
                    ← Back to events
                </Link>

                <h1 class="mt-6 text-4xl font-bold sm:text-5xl">
                    {{ event.title }}
                </h1>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="grid gap-8 lg:grid-cols-3">
                <section class="lg:col-span-2">
                    <div class="rounded-xl bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900">
                            Event details
                        </h2>

                        <div class="mt-5 space-y-4 text-gray-600">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    Date
                                </p>

                                <p>
                                    {{ formatDate(event.starts_at) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    Time
                                </p>

                                <p>
                                    {{ formatTime(event.starts_at) }}
                                    -
                                    {{ formatTime(event.ends_at) }}
                                </p>
                            </div>

                            <div v-if="event.venue || event.location">
                                <p class="text-sm font-medium text-gray-900">
                                    Venue
                                </p>

                                <p v-if="event.venue">
                                    {{ event.venue }}
                                </p>

                                <p v-if="event.location" class="text-sm text-gray-600">
                                    {{ event.location }}
                                </p>
                            </div>

                            <div v-if="event.description">
                                <p class="text-sm font-medium text-gray-900">
                                    About this event
                                </p>
                                <p class="whitespace-pre-line">
                                    {{ event.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">
                                Tickets
                            </h2>

                            <span class="text-sm text-gray-500">
                                {{ availableTickets }} remaining
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div
                                v-for="ticket in event.ticket_types"
                                :key="ticket.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-4"
                            >
                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        {{ ticket.name }}
                                    </h3>

                                    <p class="mt-1 font-medium text-orange-600">
                                        {{ formatPrice(ticket.price) }}
                                    </p>
                                </div>

                                <span
                                    v-if="ticket.available_quantity > 0"
                                    class="text-sm text-gray-500"
                                >
                                    {{ ticket.available_quantity }} available
                                </span>

                                <span
                                    v-else
                                    class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500"
                                >
                                    Sold out
                                </span>
                            </div>

                            <p
                                v-if="!event.ticket_types.length"
                                class="py-8 text-center text-gray-500"
                            >
                                No tickets are available for this event.
                            </p>
                        </div>
                    </div>
                </section>

                <aside>
                    <div
                        class="sticky top-6 rounded-xl bg-white p-6 shadow-sm"
                    >
                        <h2 class="text-xl font-bold text-gray-900">
                            Ready to attend?
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Select your tickets and continue to booking.
                        </p>

                        <Link
                            v-if="hasTickets"
                            :href="`/events/${event.id}/book`"
                            class="mt-6 block w-full rounded-lg bg-orange-500 px-4 py-3 text-center font-semibold text-white transition hover:bg-orange-600"
                        >
                            Book tickets
                        </Link>

                        <div
                            v-else
                            class="mt-6 rounded-lg bg-gray-100 px-4 py-3 text-center font-semibold text-gray-500"
                        >
                            Sold out
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>