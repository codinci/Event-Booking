<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

interface TicketType {
    id: number;
    name: string;
    price: string | number;
    available_quantity: number;
}

interface Event {
    id: number;
    title: string;
    venue: string | null;
    starts_at: string;
    ends_at: string;
    ticket_types: TicketType[];
}

interface BookingItem {
    ticket_type_id: number;
    quantity: number;
}

const props = defineProps<{
    event: Event;
}>();

const form = useForm<{
    items: BookingItem[];
}>({
    items: props.event.ticket_types.map((ticket) => ({
        ticket_type_id: ticket.id,
        quantity: 0,
    })),
});

const selectedItems = computed(() =>
    form.items.filter((item) => item.quantity > 0),
);

const total = computed(() =>
    selectedItems.value.reduce((sum, item) => {
        const ticket = props.event.ticket_types.find(
            (ticket) => ticket.id === item.ticket_type_id,
        );

        if (!ticket) {
            return sum;
        }

        return sum + Number(ticket.price) * item.quantity;
    }, 0),
);

const hasSelection = computed(() => selectedItems.value.length > 0);

const getQuantity = (ticketId: number) => {
    return (
        form.items.find((item) => item.ticket_type_id === ticketId)
            ?.quantity ?? 0
    );
};

const setQuantity = (ticket: TicketType, quantity: number) => {
    const item = form.items.find(
        (item) => item.ticket_type_id === ticket.id,
    );

    if (!item) {
        return;
    }

    item.quantity = Math.max(
        0,
        Math.min(quantity, ticket.available_quantity),
    );
};

const increment = (ticket: TicketType) => {
    setQuantity(ticket, getQuantity(ticket.id) + 1);
};

const decrement = (ticket: TicketType) => {
    setQuantity(ticket, getQuantity(ticket.id) - 1);
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
    }).format(Number(price));
};

const submit = () => {
    form.post(`/events/${props.event.id}/bookings`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="min-h-screen bg-orange-50">
        <header class="bg-orange-500 px-4 py-8 text-white">
            <div class="mx-auto max-w-5xl">
                <Link
                    :href="`/events/${event.id}`"
                    class="text-sm text-orange-100 hover:text-white"
                >
                    ← Back to event
                </Link>

                <h1 class="mt-5 text-3xl font-bold">
                    Book {{ event.title }}
                </h1>

                <p class="mt-2 text-orange-100">
                    {{ formatDate(event.starts_at) }}
                    <span v-if="event.venue">
                        · {{ event.venue }}
                    </span>
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="lg:col-span-2">
                    <div class="rounded-xl bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900">
                            Select tickets
                        </h2>

                        <div class="mt-6 space-y-4">
                            <div
                                v-for="ticket in event.ticket_types"
                                :key="ticket.id"
                                class="flex flex-col gap-4 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        {{ ticket.name }}
                                    </h3>

                                    <p class="mt-1 font-medium text-orange-600">
                                        {{ formatPrice(ticket.price) }}
                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">
                                        {{
                                            ticket.available_quantity
                                        }}
                                        available
                                    </p>
                                </div>

                                <div
                                    v-if="ticket.available_quantity > 0"
                                    class="flex items-center gap-3"
                                >
                                    <button
                                        type="button"
                                        class="h-10 w-10 rounded-lg border border-gray-300 text-lg hover:bg-gray-50 disabled:opacity-40"
                                        :disabled="
                                            getQuantity(ticket.id) <= 0
                                        "
                                        @click="decrement(ticket)"
                                    >
                                        −
                                    </button>

                                    <span
                                        class="w-8 text-center font-semibold"
                                    >
                                        {{ getQuantity(ticket.id) }}
                                    </span>

                                    <button
                                        type="button"
                                        class="h-10 w-10 rounded-lg border border-gray-300 text-lg hover:bg-gray-50 disabled:opacity-40"
                                        :disabled="
                                            getQuantity(ticket.id) >=
                                            ticket.available_quantity
                                        "
                                        @click="increment(ticket)"
                                    >
                                        +
                                    </button>
                                </div>

                                <span
                                    v-else
                                    class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-500"
                                >
                                    Sold out
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="form.errors.items"
                            class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
                        >
                            {{ form.errors.items }}
                        </div>
                    </div>
                </section>

                <aside>
                    <div class="sticky top-6 rounded-xl bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900">
                            Booking summary
                        </h2>

                        <div
                            v-if="selectedItems.length"
                            class="mt-5 space-y-3"
                        >
                            <div
                                v-for="item in selectedItems"
                                :key="item.ticket_type_id"
                                class="flex justify-between gap-4 text-sm"
                            >
                                <span class="text-gray-600">
                                    {{
                                        event.ticket_types.find(
                                            (ticket) =>
                                                ticket.id ===
                                                item.ticket_type_id,
                                        )?.name
                                    }}
                                    × {{ item.quantity }}
                                </span>

                                <span class="font-medium text-gray-900">
                                    {{
                                        formatPrice(
                                            Number(
                                                event.ticket_types.find(
                                                    (ticket) =>
                                                        ticket.id ===
                                                        item.ticket_type_id,
                                                )?.price ?? 0,
                                            ) * item.quantity,
                                        )
                                    }}
                                </span>
                            </div>

                            <div class="border-t pt-4">
                                <div
                                    class="flex justify-between text-lg font-bold"
                                >
                                    <span>Total</span>
                                    <span class="text-orange-600">
                                        {{ formatPrice(total) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            v-else
                            class="mt-5 text-sm text-gray-500"
                        >
                            Select at least one ticket to continue.
                        </p>

                        <button
                            type="button"
                            class="mt-6 w-full rounded-lg bg-orange-500 px-4 py-3 font-semibold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!hasSelection || form.processing"
                            @click="submit"
                        >
                            {{
                                form.processing
                                    ? 'Creating booking...'
                                    : 'Confirm booking'
                            }}
                        </button>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>