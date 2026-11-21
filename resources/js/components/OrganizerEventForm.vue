<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { index } from '@/routes/organizer/events';
import { ref } from 'vue';

interface TicketType {
    id?: number;
    name: string;
    price: number | string;
    quantity: number | string;
    available_quantity?: number;
}

const props = defineProps<{
    action: string;
    method: 'post' | 'put';
    event?: {
        title: string;
        description: string | null;
        venue: string;
        location: string | null;
        image_url: string | null;
        starts_at: string;
        ends_at: string;
        status?: string;
        paid_bookings_count?: number;
        ticket_types?: TicketType[];
    };
}>();

const ticketTypes = ref<TicketType[]>(props.event?.ticket_types ?? []);

const form = useForm({
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    venue: props.event?.venue ?? '',
    location: props.event?.location ?? '',
    image_url: props.event?.image_url ?? '',
    starts_at: props.event?.starts_at ?? '',
    ends_at: props.event?.ends_at ?? '',
    ticket_types: ticketTypes.value,
});

const addTicketType = () => {
    ticketTypes.value.push({ name: '', price: '', quantity: '' });
    form.ticket_types = ticketTypes.value;
};

const removeTicketType = (index: number) => {
    ticketTypes.value.splice(index, 1);
    form.ticket_types = ticketTypes.value;
};

const fields = [
    { name: 'title', label: 'Title', type: 'text' },
    { name: 'venue', label: 'Venue', type: 'text' },
    { name: 'location', label: 'Location', type: 'text' },
    { name: 'image_url', label: 'Image URL (https)', type: 'url' },
    { name: 'starts_at', label: 'Starts at', type: 'datetime-local' },
    { name: 'ends_at', label: 'Ends at', type: 'datetime-local' },
] as const;
</script>

<template>
    <form class="grid gap-6" @submit.prevent="form.submit(method, action)">
        <div v-for="field in fields" :key="field.name" class="grid gap-1">
            <label :for="field.name">{{ field.label }}</label>
            <input
                :id="field.name"
                v-model="form[field.name]"
                :type="field.type"
                :step="field.type === 'datetime-local' ? 1 : undefined"
                :aria-invalid="Boolean(form.errors[field.name])"
                :required="field.name !== 'location' && field.name !== 'image_url'"
                class="rounded-md border bg-background p-2"
            />
            <p v-if="form.errors[field.name]" role="alert" class="text-sm text-destructive">
                {{ form.errors[field.name] }}
            </p>
        </div>

        <div class="grid gap-1">
            <label for="description">Description</label>
            <textarea id="description" v-model="form.description" class="rounded-md border bg-background p-2" rows="4" />
            <p v-if="form.errors.description" role="alert" class="text-sm text-destructive">{{ form.errors.description }}</p>
        </div>

        <div class="grid gap-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Ticket Types</h3>
                <button
                    type="button"
                    @click="addTicketType"
                    class="rounded-md border border-primary px-3 py-1 text-sm text-primary hover:bg-primary/10"
                >
                    Add Type
                </button>
            </div>

            <div v-if="ticketTypes.length === 0" class="text-sm text-muted-foreground">
                Add at least one ticket type to sell tickets for this event.
            </div>

            <div v-for="(type, idx) in ticketTypes" :key="idx" class="grid gap-3 rounded-md border p-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label :for="`ticket-name-${idx}`">Name</label>
                        <input
                            :id="`ticket-name-${idx}`"
                            v-model="type.name"
                            type="text"
                            :aria-invalid="Boolean(form.errors[`ticket_types.${idx}.name`])"
                            class="rounded-md border bg-background p-2"
                            required
                        />
                        <p v-if="form.errors[`ticket_types.${idx}.name`]" role="alert" class="text-sm text-destructive">
                            {{ form.errors[`ticket_types.${idx}.name`] }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <label :for="`ticket-price-${idx}`">Price (KES)</label>
                        <input
                            :id="`ticket-price-${idx}`"
                            v-model="type.price"
                            type="number"
                            step="0.01"
                            min="0"
                            :aria-invalid="Boolean(form.errors[`ticket_types.${idx}.price`])"
                            class="rounded-md border bg-background p-2"
                            required
                        />
                        <p v-if="form.errors[`ticket_types.${idx}.price`]" role="alert" class="text-sm text-destructive">
                            {{ form.errors[`ticket_types.${idx}.price`] }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label :for="`ticket-qty-${idx}`">Quantity</label>
                        <input
                            :id="`ticket-qty-${idx}`"
                            v-model="type.quantity"
                            type="number"
                            min="1"
                            :aria-invalid="Boolean(form.errors[`ticket_types.${idx}.quantity`])"
                            class="rounded-md border bg-background p-2"
                            required
                        />
                        <p v-if="form.errors[`ticket_types.${idx}.quantity`]" role="alert" class="text-sm text-destructive">
                            {{ form.errors[`ticket_types.${idx}.quantity`] }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <label>Available</label>
                        <div class="flex items-center rounded-md border bg-background p-2 text-sm">
                            {{ type.available_quantity ?? type.quantity }}
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="removeTicketType(idx)"
                    class="text-sm text-destructive hover:underline"
                >
                    Remove
                </button>
            </div>

            <p v-if="form.errors['ticket_types']" role="alert" class="text-sm text-destructive">
                {{ form.errors['ticket_types'] }}
            </p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-primary-foreground">
                {{ method === 'post' ? 'Create event' : 'Save changes' }}
            </button>
            <Link :href="index().url" class="underline">Cancel</Link>
        </div>
    </form>
</template>
