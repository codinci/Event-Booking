<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import TopBar from '@/components/TopBar.vue';

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
    <TopBar />

    <div class="min-h-screen bg-surface text-foreground">
        <!-- Header (solid coral in both themes so white text stays readable) -->
        <header class="bg-[#e9784f] px-4 py-10 text-white">
            <div class="mx-auto max-w-5xl">
                <Link
                    href="/events"
                    class="text-sm text-white/80 hover:text-white"
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
                    <div class="rounded-xl border border-line bg-card p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-foreground">
                            Event details
                        </h2>

                        <div class="mt-5 space-y-4 text-muted-foreground">
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    Date
                                </p>

                                <p>
                                    {{ formatDate(event.starts_at) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    Time
                                </p>

                                <p>
                                    {{ formatTime(event.starts_at) }}
                                    -
                                    {{ formatTime(event.ends_at) }}
                                </p>
                            </div>

                            <div v-if="event.venue || event.location">
                                <p class="text-sm font-medium text-foreground">
                                    Venue
                                </p>

                                <p v-if="event.venue">
                                    {{ event.venue }}
                                </p>

                                <p v-if="event.location" class="text-sm">
                                    {{ event.location }}
                                </p>
                            </div>

                            <div v-if="event.description">
                                <p class="text-sm font-medium text-foreground">
                                    About this event
                                </p>
                                <p class="whitespace-pre-line">
                                    {{ event.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-line bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-foreground">
                                Tickets
                            </h2>

                            <span class="text-sm text-muted-foreground">
                                {{ availableTickets }} remaining
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div
                                v-for="ticket in event.ticket_types"
                                :key="ticket.id"
                                class="flex items-center justify-between rounded-lg border border-line p-4"
                            >
                                <div>
                                    <h3 class="font-semibold text-foreground">
                                        {{ ticket.name }}
                                    </h3>

                                    <p class="mt-1 font-medium text-brand">
                                        {{ formatPrice(ticket.price) }}
                                    </p>
                                </div>

                                <span
                                    v-if="ticket.available_quantity > 0"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ ticket.available_quantity }} available
                                </span>

                                <span
                                    v-else
                                    class="rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground"
                                >
                                    Sold out
                                </span>
                            </div>

                            <p
                                v-if="!event.ticket_types.length"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No tickets are available for this event.
                            </p>
                        </div>
                    </div>
                </section>

                <aside>
                    <div
                        class="sticky top-6 rounded-xl border border-line bg-card p-6 shadow-sm"
                    >
                        <h2 class="text-xl font-bold text-foreground">
                            Ready to attend?
                        </h2>

                        <p class="mt-2 text-sm text-muted-foreground">
                            Select your tickets and continue to booking.
                        </p>

                        <Link
                            v-if="hasTickets"
                            :href="`/events/${event.id}/book`"
                            class="mt-6 block w-full rounded-lg bg-brand px-4 py-3 text-center font-semibold text-white transition hover:bg-brand-hover"
                        >
                            Book tickets
                        </Link>

                        <div
                            v-else
                            class="mt-6 rounded-lg bg-muted px-4 py-3 text-center font-semibold text-muted-foreground"
                        >
                            Sold out
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>