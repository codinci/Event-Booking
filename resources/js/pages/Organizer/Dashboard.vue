<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
    <main class="mx-auto w-full max-w-4xl space-y-8 px-6 py-10">
        <h1 class="text-3xl font-semibold">Organizer Dashboard</h1>

        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Total Revenue</p>
                <p class="text-2xl font-semibold">KES {{ totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Pending Refunds</p>
                <p class="text-2xl font-semibold">{{ pendingRefunds }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-xl font-semibold">Upcoming Events</h2>
            <ul v-if="upcomingEvents.length" class="divide-y rounded-lg border">
                <li v-for="event in upcomingEvents" :key="event.id" class="p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-medium">{{ event.title }}</p>
                        <p class="text-sm text-muted-foreground">{{ event.paid_bookings_count }} paid bookings</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div v-for="tt in event.ticket_types" :key="tt.name" class="text-muted-foreground">
                            {{ tt.name }}: {{ tt.sold }}/{{ tt.sold + tt.available }} sold
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">No upcoming published events.</p>
        </div>

        <div class="space-y-3">
            <h2 class="text-xl font-semibold">Recent Bookings</h2>
            <ul v-if="recentBookings.length" class="divide-y rounded-lg border">
                <li v-for="booking in recentBookings" :key="booking.id" class="p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-medium">{{ booking.user_name }}</p>
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
