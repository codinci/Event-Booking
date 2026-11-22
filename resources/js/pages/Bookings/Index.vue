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
    filter: 'all' | 'past';
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
            <h1 class="text-2xl font-bold text-foreground">
                {{ filter === 'past' ? 'Past events' : 'My bookings' }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ filter === 'past' ? 'Events you booked that have already ended.' : 'Events you have booked, most recent first.' }}
                <Link
                    :href="filter === 'past' ? '/bookings' : '/bookings?filter=past'"
                    class="font-semibold text-brand hover:underline"
                >
                    {{ filter === 'past' ? 'Show all bookings' : 'Show past only' }}
                </Link>
            </p>
        </div>

        <div
            v-if="bookings.length === 0"
            class="rounded-xl border border-dashed border-line bg-card p-8 text-center"
        >
            <p class="text-muted-foreground">{{ filter === 'past' ? 'You have no past booked events.' : 'You have not booked any events yet.' }}</p>
            <Link href="/events" class="mt-3 inline-block font-semibold text-brand hover:underline">
                Browse events
            </Link>
        </div>

        <ul v-else class="grid gap-4">
            <li
                v-for="booking in bookings"
                :key="booking.id"
                class="rounded-xl border border-line bg-card p-5 shadow-sm"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-lg font-semibold text-foreground">
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
                                class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"
                            >
                                Past event
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ formatDate(booking.event.starts_at) }}
                            <span v-if="booking.event.venue">· {{ booking.event.venue }}</span>
                        </p>

                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ ticketSummary(booking) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-left sm:text-right">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">Reference</p>
                        <p class="font-mono font-semibold text-foreground">{{ booking.reference }}</p>
                        <p class="mt-1 font-semibold text-brand">
                            {{ formatPrice(booking.total_amount) }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex gap-3 border-t border-line pt-4">
                    <Link
                        :href="bookingShow(booking.id).url"
                        class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-hover"
                    >
                        View booking
                    </Link>
                    <Link
                        :href="`/events/${booking.event.id}`"
                        class="rounded-lg border border-line px-4 py-2 text-sm font-semibold text-foreground hover:bg-hover"
                    >
                        View event
                    </Link>
                </div>
            </li>
        </ul>
    </div>
</template>
