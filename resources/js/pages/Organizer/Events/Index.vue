<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import CancelEventButton from '@/components/CancelEventButton.vue';
import { create, edit, publish } from '@/routes/organizer/events';
import { ref } from 'vue';

defineProps<{
    events: {
        data: { id: number; title: string; venue: string; status: string; paid_bookings_count: number }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const publishingId = ref<number | null>(null);

const publishEvent = (eventId: number) => {
    const form = useForm({});
    publishingId.value = eventId;
    form.post(publish(eventId).url, {
        onFinish: () => {
            publishingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Your events" />
    <main class="mx-auto w-full max-w-4xl space-y-6 px-4 py-8">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Your events</h1>
                <p class="mt-1 text-sm text-muted-foreground">Create, publish and manage your events.</p>
            </div>
            <Link
                :href="create().url"
                class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-hover"
            >
                Create event
            </Link>
        </header>

        <ul v-if="events.data.length" class="grid gap-4">
            <li
                v-for="event in events.data"
                :key="event.id"
                class="flex flex-col gap-4 rounded-xl border border-line bg-card p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-lg font-semibold text-foreground">{{ event.title }}</h2>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                            :class="
                                event.status === 'published'
                                    ? 'bg-brand/10 text-brand'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ event.status }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ event.venue }} · {{ event.paid_bookings_count }} paid bookings
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="edit(event.id).url"
                        class="rounded-lg border border-line px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-hover"
                        :aria-label="`Edit ${event.title}`"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="event.status === 'draft'"
                        type="button"
                        @click="publishEvent(event.id)"
                        :disabled="publishingId === event.id"
                        class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-hover disabled:opacity-50"
                    >
                        {{ publishingId === event.id ? 'Publishing...' : 'Publish' }}
                    </button>
                    <CancelEventButton v-if="event.status !== 'cancelled'" :event="event" />
                </div>
            </li>
        </ul>
        <div v-else class="rounded-xl border border-dashed border-line bg-card px-6 py-12 text-center">
            <p class="text-foreground">You haven't created any events yet.</p>
            <Link :href="create().url" class="mt-3 inline-block font-semibold text-brand hover:underline">
                Create your first event
            </Link>
        </div>

        <nav v-if="events.prev_page_url || events.next_page_url" aria-label="Event pages" class="flex gap-3">
            <Link
                v-if="events.prev_page_url"
                :href="events.prev_page_url"
                class="rounded-lg border border-line px-4 py-2 text-sm font-semibold hover:bg-hover"
            >
                Previous
            </Link>
            <Link
                v-if="events.next_page_url"
                :href="events.next_page_url"
                class="rounded-lg border border-line px-4 py-2 text-sm font-semibold hover:bg-hover"
            >
                Next
            </Link>
        </nav>
    </main>
</template>
