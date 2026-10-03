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
    <div
        class="min-h-screen bg-orange-50 text-stone-900 dark:bg-[#1c1917] dark:text-white"
    >
        <header
            class="bg-orange-500 px-4 py-8 text-white dark:bg-[#2d2926]"
        >
            <div class="mx-auto max-w-5xl">
                <Link
                    :href="`/events/${event.id}`"
                    class="text-sm text-orange-100 transition hover:text-white dark:text-orange-300"
                >
                    ← Back to event
                </Link>

                <h1 class="mt-5 text-3xl font-bold">
                    Book {{ event.title }}
                </h1>

                <p class="mt-2 text-orange-100 dark:text-orange-200">
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
                    <div
                        class="rounded-xl border border-orange-100 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-[#292524]"
                    >
                        <h2
                            class="text-xl font-bold text-stone-900 dark:text-white"
                        >
                            Select tickets
                        </h2>

                        <div class="mt-6 space-y-4">
                            <div
                                v-for="ticket in event.ticket_types"
                                :key="ticket.id"
                                class="flex flex-col gap-4 rounded-lg border border-orange-100 bg-orange-50/50 p-4 transition hover:border-orange-300 sm:flex-row sm:items-center sm:justify-between dark:border-stone-700 dark:bg-[#1c1917] dark:hover:border-orange-500/50"
                            >
                                <div>
                                    <h3
                                        class="font-semibold text-stone-900 dark:text-white"
                                    >
                                        {{ ticket.name }}
                                    </h3>

                                    <p
                                        class="mt-1 font-medium text-orange-600 dark:text-orange-400"
                                    >
                                        {{ formatPrice(ticket.price) }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm text-stone-500 dark:text-stone-400"
                                    >
                                        {{ ticket.available_quantity }}
                                        available
                                    </p>
                                </div>

                                <!-- Quantity controls -->
                                <div
                                    v-if="ticket.available_quantity > 0"
                                    class="flex items-center gap-3"
                                >
                                    <!-- Decrement -->
                                    <button
                                        type="button"
                                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-stone-300 bg-white text-lg font-semibold text-stone-700 transition hover:border-orange-400 hover:bg-orange-50 hover:text-orange-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-orange-500 dark:hover:bg-stone-700 dark:hover:text-orange-400"
                                        :disabled="
                                            getQuantity(ticket.id) <= 0
                                        "
                                        @click="decrement(ticket)"
                                    >
                                        −
                                    </button>

                                    <!-- Quantity -->
                                    <span
                                        class="flex h-10 w-8 items-center justify-center font-semibold text-stone-900 dark:text-white"
                                    >
                                        {{ getQuantity(ticket.id) }}
                                    </span>

                                    <!-- Increment -->
                                    <button
                                        type="button"
                                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-stone-300 bg-white text-lg font-semibold text-stone-700 transition hover:border-orange-400 hover:bg-orange-50 hover:text-orange-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-orange-500 dark:hover:bg-stone-700 dark:hover:text-orange-400"
                                        :disabled="
                                            getQuantity(ticket.id) >=
                                            ticket.available_quantity
                                        "
                                        @click="increment(ticket)"
                                    >
                                        +
                                    </button>
                                </div>

                                <!-- Sold out -->
                                <span
                                    v-else
                                    class="rounded-full bg-stone-100 px-3 py-1 text-sm text-stone-500 dark:bg-stone-700 dark:text-stone-400"
                                >
                                    Sold out
                                </span>
                            </div>
                        </div>

                        <!-- Validation error -->
                        <div
                            v-if="form.errors.items"
                            class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
                        >
                            {{ form.errors.items }}
                        </div>
                    </div>
                </section>

                <!-- Booking summary -->
                <aside>
                    <div
                        class="sticky top-6 rounded-xl border border-orange-100 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-[#292524]"
                    >
                        <h2
                            class="text-xl font-bold text-stone-900 dark:text-white"
                        >
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
                                <span
                                    class="text-stone-600 dark:text-stone-400"
                                >
                                    {{
                                        event.ticket_types.find(
                                            (ticket) =>
                                                ticket.id ===
                                                item.ticket_type_id,
                                        )?.name
                                    }}
                                    × {{ item.quantity }}
                                </span>

                                <span
                                    class="font-medium text-stone-900 dark:text-stone-200"
                                >
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

                            <div
                                class="border-t border-stone-200 pt-4 dark:border-stone-700"
                            >
                                <div
                                    class="flex justify-between text-lg font-bold"
                                >
                                    <span
                                        class="text-stone-900 dark:text-white"
                                    >
                                        Total
                                    </span>

                                    <span
                                        class="text-orange-600 dark:text-orange-400"
                                    >
                                        {{ formatPrice(total) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            v-else
                            class="mt-5 text-sm text-stone-500 dark:text-stone-400"
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