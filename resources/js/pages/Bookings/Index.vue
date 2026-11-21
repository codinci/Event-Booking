<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { show as bookingShow, index as bookingsIndex } from '@/routes/bookings';

interface BookingEvent {
    id: number;
    title: string;
    venue: string | null;
    starts_at: string;
    ends_at: string;
}

interface BookingRow {
    id: number;
    reference: string;
    status: 'pending' | 'confirmed' | 'cancelled';
    total_amount: string | number;
    created_at: string;
    is_past: boolean;
    event: BookingEvent;
    items: {
        id: number;
        quantity: number;
        ticket_type: { name: string };
    }[];
}

defineProps<{
    bookings: BookingRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'My bookings',
                href: bookingsIndex(),
            },
        ],
    },
});

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));

const formatPrice = (price: string | number) =>
    new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES' }).format(Number(price));

const statusClasses: Record<BookingRow['status'], string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    confirmed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    cancelled: 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
};

const statusLabel = (status: string) => status.charAt(0).toUpperCase() + status.slice(1);

const ticketSummary = (booking: BookingRow) =>
    booking.items.map((item) => `${item.quantity} × ${item.ticket_type.name}`).join(', ');
</script>

<template>
    <Head title="My bookings" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white">My bookings</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                Events you have booked, most recent first.
            </p>
        </div>

        <div
            v-if="bookings.length === 0"
            class="rounded-xl border border-dashed border-stone-300 p-8 text-center dark:border-stone-700"
        >
            <p class="text-stone-700 dark:text-stone-300">You have not booked any events yet.</p>
            <Link href="/events" class="mt-3 inline-block font-semibold text-orange-600 hover:underline dark:text-orange-400">
                Browse events
            </Link>
        </div>

        <ul v-else class="grid gap-4">
            <li
                v-for="booking in bookings"
                :key="booking.id"
                class="rounded-xl border border-orange-100 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-[#292524]"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-lg font-semibold text-stone-900 dark:text-white">
                                {{ booking.event.title }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusClasses[booking.status]"
                            >
                                {{ statusLabel(booking.status) }}
                            </span>
                            <span
                                v-if="booking.is_past"
                                class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600 dark:bg-stone-800 dark:text-stone-300"
                            >
                                Past event
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                            {{ formatDate(booking.event.starts_at) }}
                            <span v-if="booking.event.venue">· {{ booking.event.venue }}</span>
                        </p>

                        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                            {{ ticketSummary(booking) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-left sm:text-right">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Reference</p>
                        <p class="font-mono font-semibold text-stone-900 dark:text-white">{{ booking.reference }}</p>
                        <p class="mt-1 font-semibold text-orange-600 dark:text-orange-400">
                            {{ formatPrice(booking.total_amount) }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex gap-3 border-t border-stone-100 pt-4 dark:border-stone-700">
                    <Link
                        :href="bookingShow(booking.id).url"
                        class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                    >
                        View booking
                    </Link>
                    <Link
                        :href="`/events/${booking.event.id}`"
                        class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800"
                    >
                        View event
                    </Link>
                </div>
            </li>
        </ul>
    </div>
</template>
