<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicHeader from '@/components/PublicHeader.vue';


interface TicketType {
    id: number;
    name: string;
    price: string | number;
    available_quantity: number;
}

interface Event {
    id: number;
    title: string;
    description: string | null;
    venue: string | null;
    starts_at: string;
    ends_at: string;
    ticket_types: TicketType[];
}

const props = defineProps<{
    events: Event[];
}>();

const search = ref('');

const filteredEvents = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.events;
    }

    return props.events.filter((event) => {
        return (
            event.title.toLowerCase().includes(query) ||
            event.venue?.toLowerCase().includes(query)
        );
    });
});

const hasTickets = (event: Event) =>
    event.ticket_types.some((ticket) => ticket.available_quantity > 0);

const lowestPrice = (event: Event) => {
    const prices = event.ticket_types.map((ticket) => Number(ticket.price));

    return prices.length ? Math.min(...prices) : null;
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
    }).format(price);
};
</script>

<template>
    <div class="min-h-screen bg-orange-50">
		<PublicHeader />
        <section class="bg-orange-500 px-4 py-16 text-white">
            <div class="mx-auto max-w-6xl">
                <p class="mb-2 text-sm font-semibold uppercase tracking-wider">
                    Discover events
                </p>

                <h1 class="text-4xl font-bold sm:text-5xl">
                    Find your next event
                </h1>

                <p class="mt-4 max-w-2xl text-orange-100">
                    Browse upcoming events and reserve your tickets online.
                </p>
            </div>
        </section>

        <main class="mx-auto max-w-6xl px-4 py-8">
           	<div class="mb-8">
				<input
					v-model="search"
					type="search"
					placeholder="Search events or venues..."
					class="w-full rounded-lg border border-orange-200 bg-white px-4 py-3 text-[#2d2926] placeholder:text-[#9a918a] outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
				/>
			</div>

            <div
                v-if="filteredEvents.length"
                class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="event in filteredEvents"
                    :key="event.id"
                    class="overflow-hidden rounded-xl border border-orange-100 bg-white shadow-sm"
                >
                    <div class="h-3 bg-orange-500" />

                    <div class="p-6">
						<!-- Event Image -->
						<div class="mb-5 overflow-hidden rounded-lg">
							<img
								v-if="event.image_url"
								:src="event.image_url"
								:alt="event.title"
								class="h-48 w-full object-cover transition duration-300 hover:scale-105"
							/>

							<div
								v-else
								class="flex h-48 w-full items-center justify-center rounded-lg bg-orange-100 text-sm text-orange-500"
							>
								No image available
							</div>
						</div>
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ event.title }}
                        </h2>

                        <p
                            v-if="event.venue"
                            class="mt-2 text-sm text-gray-600"
                        >
                            {{ event.venue }}
                        </p>

                        <p class="mt-2 text-sm text-gray-600">
                            {{ formatDate(event.starts_at) }}
                        </p>

                        <p
                            v-if="event.description"
                            class="mt-4 line-clamp-3 text-sm text-gray-600"
                        >
                            {{ event.description }}
                        </p>

                        <div
                            class="mt-6 flex items-center justify-between gap-4"
                        >
                            <div>
                                <p class="text-xs text-gray-500">
                                    Starting from
                                </p>

                                <p
                                    v-if="lowestPrice(event) !== null"
                                    class="font-semibold text-orange-600"
                                >
                                    {{ formatPrice(lowestPrice(event)!) }}
                                </p>

                                <p
                                    v-else
                                    class="text-sm text-gray-500"
                                >
                                    No tickets
                                </p>
                            </div>

                            <span
                                v-if="!hasTickets(event)"
                                class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600"
                            >
                                Sold out
                            </span>

                            <Link
                                :href="`/events/${event.id}`"
                                class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600"
                            >
                                View event
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed border-orange-200 bg-white px-6 py-16 text-center"
            >
                <h2 class="text-xl font-semibold text-gray-900">
                    No events found
                </h2>

                <p class="mt-2 text-gray-500">
                    Try a different search term.
                </p>
            </div>
        </main>
    </div>
</template>