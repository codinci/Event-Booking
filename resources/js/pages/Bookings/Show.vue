<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface BookingItem {
    id: number;
    quantity: number;
    unit_price: string | number;
    total: string | number;
    ticket_type: {
        id: number;
        name: string;
    };
}

interface BookingEvent {
    id: number;
    title: string;
    venue: string | null;
    starts_at: string;
    ends_at: string;
}

interface Booking {
    id: number;
    reference: string;
    status: string;
    total_amount: string | number;
    created_at: string;
    event: BookingEvent;
    items: BookingItem[];
}

const props = defineProps<{
    booking: Booking;
}>();

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
    }).format(Number(price));
};

const statusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<template>
    <div class="min-h-screen bg-orange-50 px-4 py-10">
        <main class="mx-auto max-w-3xl">
            <div class="rounded-xl bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-8 text-center">
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-2xl text-orange-600"
                    >
                        ✓
                    </div>

                    <h1 class="mt-5 text-3xl font-bold text-gray-900">
                        Booking received
                    </h1>

                    <p class="mt-2 text-gray-600">
                        Your booking has been created successfully.
                    </p>

                    <div
                        class="mx-auto mt-5 inline-flex rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700"
                    >
                        {{ statusLabel(booking.status) }}
                    </div>
                </div>

                <div class="px-6 py-6">
                    <div
                        class="rounded-lg bg-gray-50 p-4 text-center"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Booking reference
                        </p>

                        <p
                            class="mt-1 text-2xl font-bold tracking-wider text-gray-900"
                        >
                            {{ booking.reference }}
                        </p>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ booking.event.title }}
                        </h2>

                        <div class="mt-3 space-y-1 text-sm text-gray-600">
                            <p>
                                {{ formatDate(booking.event.starts_at) }}
                            </p>

                            <p v-if="booking.event.venue">
                                {{ booking.event.venue }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h2 class="font-semibold text-gray-900">
                            Tickets
                        </h2>

                        <div class="mt-3 divide-y rounded-lg border">
                            <div
                                v-for="item in booking.items"
                                :key="item.id"
                                class="flex items-center justify-between gap-4 p-4"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ item.ticket_type.name }}
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        {{ item.quantity }} ×
                                        {{ formatPrice(item.unit_price) }}
                                    </p>
                                </div>

                                <p class="font-semibold text-gray-900">
                                    {{ formatPrice(item.total) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-5">
                        <div
                            class="flex items-center justify-between text-lg font-bold"
                        >
                            <span>Total</span>

                            <span class="text-orange-600">
                                {{ formatPrice(booking.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-8 flex flex-col gap-3 sm:flex-row"
                    >
                        <Link
                            :href="`/events/${booking.event.id}`"
                            class="rounded-lg bg-orange-500 px-4 py-3 text-center font-semibold text-white hover:bg-orange-600"
                        >
                            View event
                        </Link>

                        <Link
                            href="/events"
                            class="rounded-lg border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Browse other events
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>