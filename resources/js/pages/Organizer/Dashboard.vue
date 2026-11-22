<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { create as createEvent, index as eventsIndex } from '@/routes/organizer/events';

defineProps<{
    upcomingEvents: Array<{
        id: number;
        title: string;
        starts_at: string;
        paid_bookings_count: number;
        ticket_types: Array<{
            name: string;
            sold: number;
            available: number;
        }>;
    }>;
    totalRevenue: number;
    pendingRefunds: number;
    recentBookings: Array<{
        id: number;
        reference: string;
        user_name: string;
        event_title: string;
        status: string;
        created_at: string;
    }>;
}>();
</script>

<template>
    <Head title="Dashboard" />
    <main class="mx-auto w-full max-w-4xl space-y-8 px-4 py-8">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-foreground">Organizer dashboard</h1>
            <div class="flex items-center gap-3">
                <Link :href="eventsIndex().url" class="rounded-lg border border-line bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-hover">
                    My events
                </Link>
                <Link :href="createEvent().url" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-hover">
                    Create event
                </Link>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-line bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">Total Revenue</p>
                <p class="mt-1 text-2xl font-bold text-brand">KES {{ totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
            </div>
            <div class="rounded-xl border border-line bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">Pending Refunds</p>
                <p class="mt-1 text-2xl font-bold text-foreground">{{ pendingRefunds }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-foreground">Upcoming Events</h2>
            <ul v-if="upcomingEvents.length" class="divide-y divide-line overflow-hidden rounded-xl border border-line bg-card shadow-sm">
                <li v-for="event in upcomingEvents" :key="event.id" class="p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-semibold text-foreground">{{ event.title }}</p>
                        <p class="text-sm text-muted-foreground">{{ event.paid_bookings_count }} paid bookings</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div v-for="tt in event.ticket_types" :key="tt.name" class="text-muted-foreground">
                            {{ tt.name }}: {{ tt.sold }}/{{ tt.sold + tt.available }} sold
                        </div>
                    </div>
                </li>
            </ul>
            <div v-else class="rounded-xl border border-dashed border-line bg-card p-6 text-center">
                <p class="text-sm text-muted-foreground">No upcoming published events.</p>
                <Link :href="createEvent().url" class="mt-2 inline-block text-sm font-semibold text-brand hover:underline">
                    Create your first event
                </Link>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-foreground">Recent Bookings</h2>
            <ul v-if="recentBookings.length" class="divide-y divide-line overflow-hidden rounded-xl border border-line bg-card shadow-sm">
                <li v-for="booking in recentBookings" :key="booking.id" class="p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-foreground">{{ booking.user_name }}</p>
                            <p class="text-sm text-muted-foreground">{{ booking.event_title }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-mono">{{ booking.reference }}</p>
                            <p class="text-sm capitalize text-muted-foreground">{{ booking.status }}</p>
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">No recent bookings.</p>
        </div>
    </main>
</template>
