<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CancelEventButton from '@/components/CancelEventButton.vue';
import { create, edit } from '@/routes/organizer/events';

defineProps<{
    events: {
        data: { id: number; title: string; venue: string; status: string; paid_bookings_count: number }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>

<template>
    <Head title="Your events" />
    <main class="mx-auto w-full max-w-4xl space-y-5 px-6 py-10">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">Your events</h1>
            <Link :href="create().url" class="rounded-md bg-primary px-4 py-2 text-primary-foreground">
                Create event
            </Link>
        </header>

        <ul v-if="events.data.length" class="divide-y rounded-lg border">
            <li v-for="event in events.data" :key="event.id" class="flex items-center justify-between gap-4 p-4">
                <div>
                    <p class="font-medium">{{ event.title }}</p>
                    <p class="text-sm text-muted-foreground">{{ event.venue }} · <span class="capitalize">{{ event.status }}</span></p>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="edit(event.id).url" class="underline" :aria-label="`Edit ${event.title}`">Edit</Link>
                    <CancelEventButton v-if="event.status !== 'cancelled'" :event="event" />
                </div>
            </li>
        </ul>
        <p v-else class="text-muted-foreground">You haven’t created any events yet.</p>

        <nav v-if="events.prev_page_url || events.next_page_url" aria-label="Event pages" class="flex gap-4">
            <Link v-if="events.prev_page_url" :href="events.prev_page_url" class="underline">Previous</Link>
            <Link v-if="events.next_page_url" :href="events.next_page_url" class="underline">Next</Link>
        </nav>
    </main>
</template>
