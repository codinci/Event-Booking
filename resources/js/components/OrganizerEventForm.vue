<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { index } from '@/routes/organizer/events';

const props = defineProps<{
    action: string;
    method: 'post' | 'put';
    event?: {
        title: string;
        description: string | null;
        venue: string;
        starts_at: string;
        ends_at: string;
    };
}>();

const form = useForm({
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    venue: props.event?.venue ?? '',
    starts_at: props.event?.starts_at ?? '',
    ends_at: props.event?.ends_at ?? '',
});

const fields = [
    { name: 'title', label: 'Title', type: 'text' },
    { name: 'venue', label: 'Venue', type: 'text' },
    { name: 'starts_at', label: 'Starts at', type: 'datetime-local' },
    { name: 'ends_at', label: 'Ends at', type: 'datetime-local' },
] as const;
</script>

<template>
    <form class="grid gap-4" @submit.prevent="form.submit(method, action)">
        <div v-for="field in fields" :key="field.name" class="grid gap-1">
            <label :for="field.name">{{ field.label }}</label>
            <input
                :id="field.name"
                v-model="form[field.name]"
                :type="field.type"
                :step="field.type === 'datetime-local' ? 1 : undefined"
                :aria-invalid="Boolean(form.errors[field.name])"
                class="rounded-md border bg-background p-2"
                required
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
        <div class="flex items-center gap-4">
            <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-primary-foreground">
                {{ method === 'post' ? 'Create event' : 'Save changes' }}
            </button>
            <Link :href="index().url" class="underline">Cancel</Link>
        </div>
    </form>
</template>
