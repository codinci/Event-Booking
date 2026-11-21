<script setup lang="ts">
import { Link, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watchEffect } from 'vue';

interface BookingItem {
    id: number;
    quantity: number;
    unit_price: string | number;
    total: string | number;
    ticket_type: {
        id: number;
        name: string;
    };
}

interface BookingEvent {
    id: number;
    title: string;
    venue: string | null;
    starts_at: string;
    ends_at: string;
}

interface Booking {
    id: number;
    reference: string;
    status: string;
    payment_hold_expires_at: string | null;
    total_amount: string | number;
    created_at: string;
    event: BookingEvent;
    items: BookingItem[];
}

const props = defineProps<{
    booking: Booking;
    paymentGateways: Record<string, string>;
    paymentUrl: string;
    receiptUrl: string;
    paymentAttempt: {
        status: string;
        refund_status: string | null;
        next_check_at: string | null;
        receipt_number: string | null;
    } | null;
    refund: {
        status: string;
        reason: 'late_payment' | 'customer_cancelled' | 'event_cancelled';
        amount: number;
        rejection_reason: string | null;
    } | null;
    cancellation: {
        url: string;
        refund_amount: number;
        cutoff_hours: number;
    } | null;
}>();

const page = usePage();
const cancelError = computed(() => (page.props.errors as Record<string, string>).booking ?? null);
const cancelling = ref(false);
const cancelBooking = () => {
    if (!props.cancellation) {
        return;
    }

    if (confirm(`Cancel this booking? You'll be refunded ${formatPrice(props.cancellation.refund_amount)} (the ticket price minus the platform fee) to your M-PESA number once an admin approves it.`)) {
        cancelling.value = true;
        router.post(props.cancellation.url, {}, { preserveScroll: true, onFinish: () => (cancelling.value = false) });
    }
};

const refundMessage = computed(() => {
    const refund = props.refund;

    if (!refund) {
        return null;
    }

    const amount = formatPrice(refund.amount);
    const why = {
        late_payment: 'Your payment arrived after the reservation expired.',
        customer_cancelled: 'You cancelled this booking.',
        event_cancelled: 'The organizer cancelled this event.',
    }[refund.reason];

    switch (refund.status) {
        case 'completed':
            return `${why} ${amount} has been refunded to your M-PESA number.`;
        case 'rejected':
            return `${why} Your refund was declined${refund.rejection_reason ? `: ${refund.rejection_reason}` : '.'}`;
        case 'awaiting_approval':
            return `${why} A refund of ${amount} is waiting for approval.`;
        default:
            return `${why} Your refund of ${amount} is being processed.`;
    }
});

const payment = useForm({ gateway: '', phone: import.meta.env.VITE_DARAJA_TEST_PHONE ?? '' });
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval>;
onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);
});
onUnmounted(() => clearInterval(timer));
const secondsUntil = (date: string | null) =>
    date ? Math.max(0, Math.ceil((Date.parse(date) - now.value) / 1000)) : 0;
const holdSeconds = computed(() =>
    secondsUntil(props.booking.payment_hold_expires_at),
);
const waiting = computed(() =>
    ['pending', 'review'].includes(props.paymentAttempt?.status ?? ''),
);
const checkSeconds = computed(() =>
    secondsUntil(props.paymentAttempt?.next_check_at ?? null),
);
const { start, stop } = usePoll(
    10000,
    { only: ['booking', 'paymentAttempt'] },
    { autoStart: false },
);
watchEffect(() => {
    if (waiting.value || (props.booking.payment_hold_expires_at && props.booking.status === 'pending')) {
        start();
    } else {
        stop();
    }
});

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
    }).format(Number(price));
};

const statusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<template>
    <div class="min-h-screen bg-orange-50 px-4 py-10">
        <main class="mx-auto max-w-3xl">
            <div class="rounded-xl bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-8 text-center">
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-2xl text-orange-600"
                    >
                        ✓
                    </div>

                    <h1 class="mt-5 text-3xl font-bold text-gray-900">
                        Booking received
                    </h1>

                    <p class="mt-2 text-gray-600">
                        Your booking has been created successfully.
                    </p>

                    <div
                        class="mx-auto mt-5 inline-flex rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700"
                    >
                        {{ statusLabel(booking.status) }}
                    </div>
                </div>

                <div class="px-6 py-6">
                    <a v-if="paymentAttempt?.receipt_number" :href="receiptUrl" class="mb-4 inline-block underline">
                        View sandbox receipt
                    </a>
                    <p v-if="refundMessage" role="status" class="mb-6 rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-gray-800">
                        {{ refundMessage }}
                    </p>
                    <section
                        v-if="booking.payment_hold_expires_at && booking.status !== 'confirmed'"
                        class="mb-6 space-y-3 rounded-lg border p-4"
                        aria-label="Payment"
                    >
                        <p v-if="holdSeconds && booking.status === 'pending'">
                            Reservation expires in {{ holdSeconds }} seconds.
                        </p>
                        <p v-else>This reservation has expired.</p>
                        <p v-if="waiting" role="status">
                            We’re still checking your payment.
                            <span v-if="checkSeconds">Next check in {{ checkSeconds }} seconds.</span>
                        </p>
                        <p v-if="paymentAttempt?.status === 'review'">
                            The payment is taking longer than expected. Contact support with your booking reference.
                        </p>
                        <p v-if="paymentAttempt?.status === 'failed'" role="status">
                            The gateway reported that payment failed.
                        </p>
                        <form
                            v-if="holdSeconds && booking.status === 'pending' && !waiting && Object.keys(paymentGateways).length"
                            class="grid gap-3"
                            @submit.prevent="payment.post(paymentUrl)"
                        >
                            <label class="grid gap-1">
                                Payment method
                                <select v-model="payment.gateway" class="rounded border p-2" required>
                                    <option value="" disabled>Select a payment method</option>
                                    <option v-for="(label, key) in paymentGateways" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </label>
                            <label class="grid gap-1">
                                Sandbox phone number
                                <input v-model="payment.phone" type="tel" placeholder="+254…" class="rounded border p-2" :required="payment.gateway === 'daraja'" />
                            </label>
                            <p v-if="payment.errors.gateway || payment.errors.phone" role="alert">
                                {{ payment.errors.gateway || payment.errors.phone }}
                            </p>
                            <button type="submit" class="rounded bg-orange-700 px-4 py-2 text-white" :disabled="payment.processing">
                                Start sandbox payment
                            </button>
                        </form>
                    </section>
                    <div
                        class="rounded-lg bg-gray-50 p-4 text-center"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Booking reference
                        </p>

                        <p
                            class="mt-1 text-2xl font-bold tracking-wider text-gray-900"
                        >
                            {{ booking.reference }}
                        </p>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ booking.event.title }}
                        </h2>

                        <div class="mt-3 space-y-1 text-sm text-gray-600">
                            <p>
                                {{ formatDate(booking.event.starts_at) }}
                            </p>

                            <p v-if="booking.event.venue">
                                {{ booking.event.venue }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h2 class="font-semibold text-gray-900">
                            Tickets
                        </h2>

                        <div class="mt-3 divide-y rounded-lg border">
                            <div
                                v-for="item in booking.items"
                                :key="item.id"
                                class="flex items-center justify-between gap-4 p-4"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ item.ticket_type.name }}
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        {{ item.quantity }} ×
                                        {{ formatPrice(item.unit_price) }}
                                    </p>
                                </div>

                                <p class="font-semibold text-gray-900">
                                    {{ formatPrice(item.total) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-5">
                        <div
                            class="flex items-center justify-between text-lg font-bold"
                        >
                            <span>Total</span>

                            <span class="text-orange-600">
                                {{ formatPrice(booking.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <section v-if="cancellation" class="mt-8 rounded-lg border p-4" aria-label="Cancel booking">
                        <p class="text-sm text-gray-600">
                            You can cancel up to {{ cancellation.cutoff_hours }} hours before the event starts and get
                            {{ formatPrice(cancellation.refund_amount) }} back (the ticket price minus the platform fee).
                        </p>
                        <p v-if="cancelError" role="alert" class="mt-2 text-sm text-red-700">{{ cancelError }}</p>
                        <button
                            type="button"
                            class="mt-3 rounded-lg border border-red-300 px-4 py-2 font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                            :disabled="cancelling"
                            @click="cancelBooking"
                        >
                            Cancel booking
                        </button>
                    </section>

                    <div
                        class="mt-8 flex flex-col gap-3 sm:flex-row"
                    >
                        <Link
                            :href="`/events/${booking.event.id}`"
                            class="rounded-lg bg-orange-500 px-4 py-3 text-center font-semibold text-white hover:bg-orange-600"
                        >
                            View event
                        </Link>

                        <Link
                            href="/events"
                            class="rounded-lg border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Browse other events
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
