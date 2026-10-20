<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';

const props = defineProps({
    secrets: {
        type: Array,
        default: () => [],
    },
    tokens: {
        type: Array,
        default: () => [],
    },
});

const userSecrets = computed(() => {
    return props.secrets && props.secrets.length > 0 ? props.secrets : props.tokens;
});

const form = useForm({
    name: '',
    abilities: '',
});

const createToken = () => {
    form.post(route('secrets.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString();
};

const otpUrl = (token) => {
    if (!token) return '';
    try {
        return route('otp.login', { token });
    } catch {
        return `/otp?token=${encodeURIComponent(token)}`;
    }
};
</script>

<template>
    <AppLayout title="Secrets">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Secrets
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <!-- Create Secret / OTP Token -->
                <FormSection @submitted="createToken">
                    <template #title>
                        Create Secret Token
                    </template>

                    <template #description>
                        Generate a new one-time password token with a custom name and optional abilities.
                    </template>

                    <template #form>
                        <!-- Token Name -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Token Abilities -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="abilities" value="Abilities" />
                            <TextInput
                                id="abilities"
                                v-model="form.abilities"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g. read, write"
                            />
                            <InputError :message="form.errors.abilities" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="form.recentlySuccessful" class="me-3">
                            Created.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Create
                        </PrimaryButton>
                    </template>
                </FormSection>

                <SectionBorder />

                <!-- Existing Secrets Table -->
                <div class="mt-10 sm:mt-0">
                    <ActionSection>
                        <template #title>
                            Existing Secrets
                        </template>

                        <template #description>
                            All secret tokens currently associated with your account.
                        </template>

                        <template #content>
                            <div class="overflow-x-auto">
                                <table v-if="userSecrets.length > 0" class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            <th scope="col" class="px-4 py-3">Name</th>
                                            <th scope="col" class="px-4 py-3">URL</th>
                                            <th scope="col" class="px-4 py-3">Created At</th>
                                            <th scope="col" class="px-4 py-3">Last Used At</th>
                                            <th scope="col" class="px-4 py-3">Expires At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white text-sm text-gray-700">
                                        <tr v-for="secret in userSecrets" :key="secret.id" class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">
                                                {{ secret.name }}
                                            </td>
                                            <td class="px-4 py-3 font-mono text-xs text-indigo-600 break-all">
                                                <a :href="otpUrl(secret.token)" class="underline hover:text-indigo-900">
                                                    {{ otpUrl(secret.token) }}
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                                {{ formatDate(secret.created_at) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                                {{ formatDate(secret.last_used_at) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                                {{ formatDate(secret.expires_at) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div v-else class="text-sm text-gray-500 py-4">
                                    No secrets found. Create a new token using the form above.
                                </div>
                            </div>
                        </template>
                    </ActionSection>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
