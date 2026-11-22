<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizerEventForm from '@/components/OrganizerEventForm.vue';
import { index, store } from '@/routes/organizer/events';
</script>

<template>
    <Head title="Create event" />
    <main class="mx-auto w-full max-w-2xl space-y-6 px-4 py-8">
        <Link
            :href="index().url"
            class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-hover"
        >
            &larr; Back to your events
        </Link>

        <div>
            <h1 class="text-2xl font-bold text-foreground">Create event</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Fill in the details and set up your ticket types. You can publish once saved.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-card shadow-sm">
            <div class="h-2 bg-brand" />
            <div class="p-6">
                <OrganizerEventForm :action="store().url" method="post" />
            </div>
        </div>
    </main>
</template>
