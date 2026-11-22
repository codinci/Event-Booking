<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { index } from '@/routes/organizer/events';

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

const steps = [
    { key: 'basics', label: 'Basics', hint: 'What is your event?' },
    { key: 'when', label: 'When & where', hint: 'Date, time and place' },
    { key: 'tickets', label: 'Tickets', hint: 'Prices and availability' },
] as const;

const stepFields: Record<number, string[]> = {
    0: ['title', 'description', 'image_url'],
    1: ['venue', 'location', 'starts_at', 'ends_at'],
    2: ['ticket_types'],
};

const isEditing = props.method === 'put';
const currentStep = ref(0);
const stepError = ref<string | null>(null);

const form = useForm({
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    venue: props.event?.venue ?? '',
    location: props.event?.location ?? '',
    image_url: props.event?.image_url ?? '',
    starts_at: props.event?.starts_at ?? '',
    ends_at: props.event?.ends_at ?? '',
    ticket_types: (props.event?.ticket_types ?? [
        { name: 'General admission', price: '', quantity: '' },
    ]) as TicketType[],
});

const toLocalInput = (date: Date) => {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

// Suggest a 2-hour event once a start time is chosen, so organizers only adjust if needed.
watch(
    () => form.starts_at,
    (value) => {
        if (!value || (form.ends_at && form.ends_at > value)) {
            return;
        }

        const start = new Date(value);

        if (!Number.isNaN(start.getTime())) {
            form.ends_at = toLocalInput(new Date(start.getTime() + 2 * 60 * 60 * 1000));
        }
    },
);

const totalTickets = computed(() =>
    form.ticket_types.reduce((sum, type) => sum + (Number(type.quantity) || 0), 0),
);

const priceRange = computed(() => {
    const prices = form.ticket_types.filter((type) => type.price !== '').map((type) => Number(type.price));

    if (prices.length === 0) {
        return null;
    }

    const format = (value: number) => (value === 0 ? 'Free' : `KES ${value.toLocaleString('en-KE')}`);
    const min = Math.min(...prices);
    const max = Math.max(...prices);

    return min === max ? format(min) : `${format(min)} – ${format(max)}`;
});

const addTicketType = () => {
    form.ticket_types.push({ name: '', price: '', quantity: '' });
};

const removeTicketType = (position: number) => {
    form.ticket_types.splice(position, 1);
};

const errorFor = (name: string) => (form.errors as Record<string, string | undefined>)[name];

const stepHasErrors = (step: number) =>
    Object.keys(form.errors).some((key) => stepFields[step].some((field) => key === field || key.startsWith(`${field}.`)));

const validateStep = (step: number): string | null => {
    if (step === 0 && !form.title.trim()) {
        return 'Give your event a title to continue.';
    }

    if (step === 1) {
        if (!form.venue.trim()) {
            return 'Add the venue to continue.';
        }

        if (!form.starts_at || !form.ends_at) {
            return 'Choose when the event starts and ends.';
        }

        if (form.ends_at <= form.starts_at) {
            return 'The event must end after it starts.';
        }
    }

    if (step === 2) {
        if (form.ticket_types.length === 0) {
            return 'Add at least one ticket type.';
        }

        const incomplete = form.ticket_types.some(
            (type) => !type.name.trim() || type.price === '' || Number(type.quantity) < 1,
        );

        if (incomplete) {
            return 'Each ticket needs a name, a price (0 for free) and a quantity of at least 1.';
        }
    }

    return null;
};

const goTo = (step: number) => {
    // Moving forward must pass each earlier step; moving back is always allowed.
    for (let i = currentStep.value; i < step; i++) {
        const problem = validateStep(i);

        if (problem) {
            currentStep.value = i;
            stepError.value = problem;

            return;
        }
    }

    stepError.value = null;
    currentStep.value = step;
};

const next = () => goTo(currentStep.value + 1);
const back = () => goTo(currentStep.value - 1);

const submit = () => {
    if (currentStep.value < steps.length - 1) {
        next();

        return;
    }

    const problem = validateStep(currentStep.value);

    if (problem) {
        stepError.value = problem;

        return;
    }

    stepError.value = null;
    form.submit(props.method, props.action, {
        preserveScroll: true,
        onError: () => {
            const failing = steps.findIndex((_, i) => stepHasErrors(i));

            if (failing !== -1) {
                currentStep.value = failing;
            }
        },
    });
};

const inputClass =
    'w-full rounded-lg border border-line bg-card px-3 py-2 text-foreground outline-none placeholder:text-muted-foreground focus:border-brand focus:ring-2 focus:ring-brand/30';
</script>

<template>
    <form class="grid gap-6" novalidate @submit.prevent="submit">
        <!-- Progress -->
        <ol class="grid grid-cols-3 gap-2" aria-label="Event setup steps">
            <li v-for="(step, i) in steps" :key="step.key">
                <button
                    type="button"
                    class="group grid w-full gap-1.5 text-left"
                    :aria-current="i === currentStep ? 'step' : undefined"
                    @click="goTo(i)"
                >
                    <span
                        class="h-1.5 rounded-full transition"
                        :class="i <= currentStep ? 'bg-brand' : 'bg-line group-hover:bg-brand/40'"
                    />
                    <span class="flex items-center gap-2 text-sm font-semibold" :class="i === currentStep ? 'text-foreground' : 'text-muted-foreground'">
                        <span
                            class="flex size-5 items-center justify-center rounded-full text-xs"
                            :class="i === currentStep ? 'bg-brand text-white' : stepHasErrors(i) ? 'bg-destructive text-white' : 'bg-muted text-muted-foreground'"
                        >
                            {{ i + 1 }}
                        </span>
                        <span class="truncate">{{ step.label }}</span>
                    </span>
                </button>
            </li>
        </ol>

        <p class="-mt-2 text-sm text-muted-foreground">{{ steps[currentStep].hint }}</p>

        <!-- Step 1: Basics -->
        <section v-show="currentStep === 0" class="grid gap-5">
            <div class="grid gap-1.5">
                <label for="title" class="text-sm font-medium text-foreground">Event title</label>
                <input id="title" v-model="form.title" type="text" placeholder="e.g. Nairobi Jazz Night" :aria-invalid="Boolean(errorFor('title'))" :class="inputClass" />
                <p v-if="errorFor('title')" role="alert" class="text-sm text-destructive">{{ errorFor('title') }}</p>
            </div>

            <div class="grid gap-1.5">
                <label for="description" class="text-sm font-medium text-foreground">
                    Description <span class="font-normal text-muted-foreground">(optional)</span>
                </label>
                <textarea id="description" v-model="form.description" rows="4" placeholder="Tell people what to expect" :class="inputClass" />
                <p v-if="errorFor('description')" role="alert" class="text-sm text-destructive">{{ errorFor('description') }}</p>
            </div>

            <div class="grid gap-1.5">
                <label for="image_url" class="text-sm font-medium text-foreground">
                    Cover image URL <span class="font-normal text-muted-foreground">(optional, https)</span>
                </label>
                <input id="image_url" v-model="form.image_url" type="url" placeholder="https://" :aria-invalid="Boolean(errorFor('image_url'))" :class="inputClass" />
                <p v-if="errorFor('image_url')" role="alert" class="text-sm text-destructive">{{ errorFor('image_url') }}</p>
            </div>
        </section>

        <!-- Step 2: When & where -->
        <section v-show="currentStep === 1" class="grid gap-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label for="starts_at" class="text-sm font-medium text-foreground">Starts</label>
                    <input id="starts_at" v-model="form.starts_at" type="datetime-local" :aria-invalid="Boolean(errorFor('starts_at'))" :class="inputClass" />
                    <p v-if="errorFor('starts_at')" role="alert" class="text-sm text-destructive">{{ errorFor('starts_at') }}</p>
                </div>
                <div class="grid gap-1.5">
                    <label for="ends_at" class="text-sm font-medium text-foreground">Ends</label>
                    <input id="ends_at" v-model="form.ends_at" type="datetime-local" :min="form.starts_at || undefined" :aria-invalid="Boolean(errorFor('ends_at'))" :class="inputClass" />
                    <p v-if="errorFor('ends_at')" role="alert" class="text-sm text-destructive">{{ errorFor('ends_at') }}</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label for="venue" class="text-sm font-medium text-foreground">Venue</label>
                    <input id="venue" v-model="form.venue" type="text" placeholder="e.g. Alliance Française" :aria-invalid="Boolean(errorFor('venue'))" :class="inputClass" />
                    <p v-if="errorFor('venue')" role="alert" class="text-sm text-destructive">{{ errorFor('venue') }}</p>
                </div>
                <div class="grid gap-1.5">
                    <label for="location" class="text-sm font-medium text-foreground">
                        City / area <span class="font-normal text-muted-foreground">(optional)</span>
                    </label>
                    <input id="location" v-model="form.location" type="text" placeholder="e.g. Nairobi" :aria-invalid="Boolean(errorFor('location'))" :class="inputClass" />
                    <p v-if="errorFor('location')" role="alert" class="text-sm text-destructive">{{ errorFor('location') }}</p>
                </div>
            </div>
        </section>

        <!-- Step 3: Tickets -->
        <section v-show="currentStep === 2" class="grid gap-4">
            <div class="hidden grid-cols-[1fr_8rem_7rem_4.5rem] gap-3 px-1 text-xs font-medium uppercase tracking-wide text-muted-foreground sm:grid">
                <span>Ticket name</span>
                <span>Price (KES)</span>
                <span>Quantity</span>
                <span class="sr-only">Actions</span>
            </div>

            <div v-for="(type, idx) in form.ticket_types" :key="type.id ?? idx" class="grid gap-3 rounded-xl border border-line bg-card p-3 sm:grid-cols-[1fr_8rem_7rem_4.5rem] sm:items-start sm:border-0 sm:bg-transparent sm:p-0">
                <div class="grid gap-1">
                    <label :for="`ticket-name-${idx}`" class="text-xs font-medium text-muted-foreground sm:sr-only">Ticket name</label>
                    <input :id="`ticket-name-${idx}`" v-model="type.name" type="text" placeholder="e.g. Regular, VIP" :aria-invalid="Boolean(errorFor(`ticket_types.${idx}.name`))" :class="inputClass" />
                    <p v-if="errorFor(`ticket_types.${idx}.name`)" role="alert" class="text-sm text-destructive">{{ errorFor(`ticket_types.${idx}.name`) }}</p>
                </div>
                <div class="grid gap-1">
                    <label :for="`ticket-price-${idx}`" class="text-xs font-medium text-muted-foreground sm:sr-only">Price (KES)</label>
                    <input :id="`ticket-price-${idx}`" v-model="type.price" type="number" step="0.01" min="0" placeholder="0 = free" :aria-invalid="Boolean(errorFor(`ticket_types.${idx}.price`))" :class="inputClass" />
                    <p v-if="errorFor(`ticket_types.${idx}.price`)" role="alert" class="text-sm text-destructive">{{ errorFor(`ticket_types.${idx}.price`) }}</p>
                </div>
                <div class="grid gap-1">
                    <label :for="`ticket-qty-${idx}`" class="text-xs font-medium text-muted-foreground sm:sr-only">Quantity</label>
                    <input :id="`ticket-qty-${idx}`" v-model="type.quantity" type="number" min="1" placeholder="100" :aria-invalid="Boolean(errorFor(`ticket_types.${idx}.quantity`))" :class="inputClass" />
                    <p v-if="errorFor(`ticket_types.${idx}.quantity`)" role="alert" class="text-sm text-destructive">{{ errorFor(`ticket_types.${idx}.quantity`) }}</p>
                    <p v-if="isEditing && type.available_quantity !== undefined" class="text-xs text-muted-foreground">{{ type.available_quantity }} left</p>
                </div>
                <button
                    type="button"
                    :disabled="form.ticket_types.length === 1"
                    :aria-label="`Remove ticket type ${idx + 1}`"
                    class="justify-self-start rounded-lg px-2 py-2 text-sm font-semibold text-destructive transition hover:bg-destructive/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                    @click="removeTicketType(idx)"
                >
                    Remove
                </button>
            </div>

            <p v-if="errorFor('ticket_types')" role="alert" class="text-sm text-destructive">{{ errorFor('ticket_types') }}</p>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="rounded-lg border border-brand px-4 py-2 text-sm font-semibold text-brand transition hover:bg-brand/10" @click="addTicketType">
                    + Add another ticket type
                </button>
                <p class="text-sm text-muted-foreground">
                    <strong class="text-foreground">{{ totalTickets }}</strong> tickets
                    <template v-if="priceRange"> · <strong class="text-foreground">{{ priceRange }}</strong></template>
                </p>
            </div>
        </section>

        <p v-if="stepError" role="alert" class="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm text-destructive">
            {{ stepError }}
        </p>

        <!-- Actions -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
            <div class="flex items-center gap-3">
                <button v-if="currentStep > 0" type="button" class="rounded-lg border border-line px-5 py-2.5 text-sm font-semibold text-foreground transition hover:bg-hover" @click="back">
                    Back
                </button>
                <Link v-else :href="index().url" class="rounded-lg border border-line px-5 py-2.5 text-sm font-semibold text-foreground transition hover:bg-hover">Cancel</Link>
            </div>

            <div class="flex items-center gap-3">
                <button
                    v-if="isEditing && currentStep < steps.length - 1"
                    type="button"
                    :disabled="form.processing"
                    class="rounded-lg border border-line px-5 py-2.5 text-sm font-semibold text-foreground transition hover:bg-hover disabled:opacity-50"
                    @click="goTo(steps.length - 1)"
                >
                    Skip to tickets
                </button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-hover disabled:opacity-50">
                    <template v-if="currentStep < steps.length - 1">Next</template>
                    <template v-else>{{ form.processing ? 'Saving…' : method === 'post' ? 'Create event' : 'Save changes' }}</template>
                </button>
            </div>
        </div>
    </form>
</template>
