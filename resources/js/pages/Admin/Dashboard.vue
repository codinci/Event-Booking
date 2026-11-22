<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    pendingRefunds: number;
    reviewRefunds: number;
    totalPlatformFees: number;
    totalEvents: number;
    totalUsers: number;
    stuckPayments: number;
}>();
</script>

<template>
    <Head title="Admin Dashboard" />
    <main class="mx-auto w-full max-w-4xl space-y-8 px-4 py-8">
        <h1 class="text-2xl font-bold text-foreground">Admin Dashboard</h1>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Pending Refunds</p>
                <p class="text-2xl font-semibold text-brand">{{ pendingRefunds }}</p>
            </div>
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">In Review</p>
                <p class="text-2xl font-semibold text-yellow-600">{{ reviewRefunds }}</p>
            </div>
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Platform Fees</p>
                <p class="text-2xl font-semibold">KES {{ totalPlatformFees.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Total Events</p>
                <p class="text-2xl font-semibold">{{ totalEvents }}</p>
            </div>
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Total Users</p>
                <p class="text-2xl font-semibold">{{ totalUsers }}</p>
            </div>
            <div class="rounded-xl border border-line bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Stuck Payments</p>
                <p class="text-2xl font-semibold text-red-600">{{ stuckPayments }}</p>
            </div>
        </div>

        <div class="rounded-lg border bg-blue-50 p-4 text-sm text-blue-700">
            Use the navigation to manage refunds, organizer requests, and events.
        </div>
    </main>
</template>
