<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import OrganizerEventForm from '@/components/OrganizerEventForm.vue';
import { store } from '@/routes/organizer/events';
</script>

<template>
    <Head title="Create event" />
    <main class="mx-auto w-full max-w-xl space-y-5 px-6 py-10">
        <h1 class="text-2xl font-semibold">Create event</h1>
        <OrganizerEventForm :action="store().url" method="post" />
    </main>
</template>
