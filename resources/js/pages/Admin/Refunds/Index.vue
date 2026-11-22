<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface RefundRow {
    id: number;
    reason: 'late_payment' | 'customer_cancelled' | 'event_cancelled';
    status: string;
    amount: number;
    phone: string | null;
    booking_reference: string;
    event_title: string;
    customer: string;
    customer_email: string;
    approver: string | null;
    rejection_reason: string | null;
    result_desc: string | null;
    transaction_id: string | null;
    created_at: string;
    urls: { approve: string; reject: string; resolve: string };
}

const props = defineProps<{
    refunds: {
        data: RefundRow[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filter: 'awaiting' | 'review' | 'all';
    counts: { awaiting: number; review: number };
}>();

const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string>).refund ?? null);

const tabs = computed(() => [
    { key: 'awaiting', label: `Awaiting approval (${props.counts.awaiting})` },
    { key: 'review', label: `Needs review (${props.counts.review})` },
    { key: 'all', label: 'All' },
]);

const reasons: Record<RefundRow['reason'], string> = {
    late_payment: 'Late payment (full)',
    customer_cancelled: 'Customer cancelled',
    event_cancelled: 'Event cancelled',
};

const statusLabel = (status: string) => status.replace(/_/g, ' ');

const formatPrice = (amount: number) =>
    new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES' }).format(amount);

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));

const busy = ref<number | null>(null);
const rejecting = ref<number | null>(null);
const rejectReason = ref('');

const submit = (refund: RefundRow, url: string, data: Record<string, string> = {}) => {
    busy.value = refund.id;
    router.post(url, data, {
        preserveScroll: true,
        onFinish: () => (busy.value = null),
        onSuccess: () => {
            rejecting.value = null;
            rejectReason.value = '';
        },
    });
};

const approve = (refund: RefundRow) => {
    if (confirm(`Send ${formatPrice(refund.amount)} to ${refund.phone ?? 'the customer'} for booking ${refund.booking_reference}?`)) {
        submit(refund, refund.urls.approve);
    }
};

const resolve = (refund: RefundRow, outcome: 'completed' | 'failed') => {
    const question = outcome === 'completed'
        ? 'Mark as completed? Only do this after confirming the payout on the Daraja portal.'
        : 'Mark as failed? It can then be approved again to resend.';

    if (confirm(question)) {
        submit(refund, refund.urls.resolve, { outcome });
    }
};
</script>

<template>
    <Head title="Refunds" />
    <main class="mx-auto w-full max-w-6xl space-y-5 px-4 py-8">
        <header class="space-y-1">
            <h1 class="text-2xl font-bold text-foreground">Refunds</h1>
            <p class="text-sm text-muted-foreground">
                Approved refunds are paid to the customer's M-PESA number by the scheduler within a minute.
            </p>
        </header>

        <nav aria-label="Refund filters" class="flex flex-wrap gap-2">
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="`?status=${tab.key}`"
                preserve-scroll
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="filter === tab.key ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                :aria-current="filter === tab.key ? 'page' : undefined"
            >
                {{ tab.label }}
            </Link>
        </nav>

        <p v-if="error" role="alert" class="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm">
            {{ error }}
        </p>

        <div v-if="refunds.data.length" class="overflow-x-auto rounded-lg border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="p-3">Booking</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3 text-right">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="refund in refunds.data" :key="refund.id" class="align-top">
                        <td class="p-3">
                            <p class="font-medium">{{ refund.booking_reference }}</p>
                            <p class="text-muted-foreground">{{ refund.event_title }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(refund.created_at) }}</p>
                        </td>
                        <td class="p-3">
                            <p>{{ refund.customer }}</p>
                            <p class="text-muted-foreground">{{ refund.customer_email }}</p>
                            <p class="text-muted-foreground">{{ refund.phone ?? 'No phone' }}</p>
                        </td>
                        <td class="p-3">{{ reasons[refund.reason] }}</td>
                        <td class="p-3 text-right font-medium">{{ formatPrice(refund.amount) }}</td>
                        <td class="p-3">
                            <p class="capitalize">{{ statusLabel(refund.status) }}</p>
                            <p v-if="refund.approver" class="text-xs text-muted-foreground">by {{ refund.approver }}</p>
                            <p v-if="refund.rejection_reason" class="text-xs text-muted-foreground">{{ refund.rejection_reason }}</p>
                            <p v-if="refund.result_desc" class="text-xs text-muted-foreground">{{ refund.result_desc }}</p>
                            <p v-if="refund.transaction_id" class="text-xs text-muted-foreground">M-PESA {{ refund.transaction_id }}</p>
                        </td>
                        <td class="p-3">
                            <div v-if="['awaiting_approval', 'failed'].includes(refund.status)" class="flex flex-col gap-2">
                                <button
                                    type="button"
                                    class="rounded-md bg-primary px-3 py-1.5 text-primary-foreground disabled:opacity-50"
                                    :disabled="busy === refund.id"
                                    @click="approve(refund)"
                                >
                                    {{ refund.status === 'failed' ? 'Approve resend' : 'Approve' }}
                                </button>
                                <button
                                    v-if="rejecting !== refund.id"
                                    type="button"
                                    class="rounded-md border px-3 py-1.5"
                                    @click="rejecting = refund.id; rejectReason = ''"
                                >
                                    Reject
                                </button>
                                <form
                                    v-else
                                    class="flex flex-col gap-2"
                                    @submit.prevent="submit(refund, refund.urls.reject, { reason: rejectReason })"
                                >
                                    <label class="grid gap-1 text-xs">
                                        Reason (shown to the customer)
                                        <input v-model="rejectReason" required maxlength="255" class="rounded border p-1.5 text-sm" />
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="submit" class="rounded-md border border-destructive px-3 py-1.5 text-destructive" :disabled="busy === refund.id">
                                            Confirm reject
                                        </button>
                                        <button type="button" class="underline" @click="rejecting = null">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <div v-else-if="refund.status === 'review'" class="flex flex-col gap-2">
                                <button type="button" class="rounded-md border px-3 py-1.5" :disabled="busy === refund.id" @click="resolve(refund, 'completed')">
                                    Mark completed
                                </button>
                                <button type="button" class="rounded-md border px-3 py-1.5" :disabled="busy === refund.id" @click="resolve(refund, 'failed')">
                                    Mark failed
                                </button>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-muted-foreground">No refunds here.</p>

        <nav v-if="refunds.prev_page_url || refunds.next_page_url" aria-label="Refund pages" class="flex gap-4">
            <Link v-if="refunds.prev_page_url" :href="refunds.prev_page_url" class="underline">Previous</Link>
            <Link v-if="refunds.next_page_url" :href="refunds.next_page_url" class="underline">Next</Link>
        </nav>
    </main>
</template>
