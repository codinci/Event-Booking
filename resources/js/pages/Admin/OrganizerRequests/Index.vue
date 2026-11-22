<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { approve, reject } from '@/routes/admin/organizer-requests';
import { ref } from 'vue';

defineProps<{
    requests: Array<{
        id: number;
        name: string;
        email: string;
        organizer_requested_at: string;
    }>;
}>();

const processing = ref<number | null>(null);

const approveRequest = (userId: number) => {
    const form = useForm({});
    processing.value = userId;
    form.post(approve(userId).url, {
        onFinish: () => {
            processing.value = null;
        },
    });
};

const rejectRequest = (userId: number) => {
    const form = useForm({});
    processing.value = userId;
    form.post(reject(userId).url, {
        onFinish: () => {
            processing.value = null;
        },
    });
};
</script>

<template>
    <Head title="Organizer Requests" />
    <main class="mx-auto w-full max-w-4xl space-y-5 px-4 py-8">
        <h1 class="text-2xl font-bold text-foreground">Organizer Requests</h1>

        <ul v-if="requests.length" class="divide-y rounded-lg border">
            <li v-for="request in requests" :key="request.id" class="flex items-center justify-between gap-4 p-4">
                <div>
                    <p class="font-medium">{{ request.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ request.email }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="approveRequest(request.id)"
                        :disabled="processing !== null"
                        class="rounded-md bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ processing === request.id ? 'Processing...' : 'Approve' }}
                    </button>
                    <button
                        type="button"
                        @click="rejectRequest(request.id)"
                        :disabled="processing !== null"
                        class="rounded-md bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ processing === request.id ? 'Processing...' : 'Reject' }}
                    </button>
                </div>
            </li>
        </ul>
        <p v-else class="text-muted-foreground">No pending organizer requests.</p>
    </main>
</template>
