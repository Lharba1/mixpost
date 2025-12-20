<script setup>
import { ref, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Input from "@/Components/Form/Input.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import NoResult from "@/Components/Util/NoResult.vue";

const props = defineProps({
    webhooks: Array,
    available_events: Object,
});

const showCreateModal = ref(false);
const editingWebhook = ref(null);

const form = reactive({
    name: '',
    url: '',
    events: [],
    secret: '',
    is_active: true,
});

const resetForm = () => {
    form.name = '';
    form.url = '';
    form.events = [];
    form.secret = '';
    form.is_active = true;
    editingWebhook.value = null;
};

const openCreate = () => {
    resetForm();
    showCreateModal.value = true;
};

const openEdit = (webhook) => {
    editingWebhook.value = webhook;
    form.name = webhook.name;
    form.url = webhook.url;
    form.events = webhook.events || [];
    form.is_active = webhook.is_active;
    showCreateModal.value = true;
};

const save = () => {
    if (editingWebhook.value) {
        router.put(route('mixpost.webhooks.update', editingWebhook.value.id), form, {
            onSuccess: () => {
                showCreateModal.value = false;
                resetForm();
            },
        });
    } else {
        router.post(route('mixpost.webhooks.store'), form, {
            onSuccess: () => {
                showCreateModal.value = false;
                resetForm();
            },
        });
    }
};

const deleteWebhook = (webhook) => {
    if (confirm('Delete this webhook?')) {
        router.delete(route('mixpost.webhooks.destroy', webhook.id));
    }
};

const toggleWebhook = (webhook) => {
    router.post(route('mixpost.webhooks.toggle', webhook.id));
};

const testWebhook = (webhook) => {
    router.post(route('mixpost.webhooks.test', webhook.id));
};

const toggleEvent = (eventKey) => {
    const index = form.events.indexOf(eventKey);
    if (index > -1) {
        form.events.splice(index, 1);
    } else {
        form.events.push(eventKey);
    }
};
</script>

<template>
    <div>
        <PageHeader title="Webhooks">
            <template #description>
                Send event notifications to external services
            </template>
        </PageHeader>

        <div class="row-py">
            <Panel>
                <template #action>
                    <PrimaryButton size="sm" @click="openCreate">
                        Add Webhook
                    </PrimaryButton>
                </template>

                <template v-if="webhooks && webhooks.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="webhook in webhooks" 
                            :key="webhook.id"
                            class="py-4"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ webhook.name }}
                                        </h3>
                                        <Badge :variant="webhook.is_active ? 'success' : 'default'" size="sm">
                                            {{ webhook.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-500 font-mono truncate mb-2">
                                        {{ webhook.url }}
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge 
                                            v-for="event in webhook.events" 
                                            :key="event"
                                            variant="info" 
                                            size="sm"
                                        >
                                            {{ event }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">
                                        {{ webhook.success_count || 0 }} successful • {{ webhook.failed_count || 0 }} failed
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <SecondaryButton size="xs" @click="testWebhook(webhook)">
                                        Test
                                    </SecondaryButton>
                                    <SecondaryButton size="xs" @click="toggleWebhook(webhook)">
                                        {{ webhook.is_active ? 'Disable' : 'Enable' }}
                                    </SecondaryButton>
                                    <SecondaryButton size="xs" @click="openEdit(webhook)">
                                        Edit
                                    </SecondaryButton>
                                    <DangerButton size="xs" @click="deleteWebhook(webhook)">
                                        Delete
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No webhooks configured. Create one to get started.
                </NoResult>
            </Panel>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="showCreateModal" @close="showCreateModal = false">
            <template #title>
                {{ editingWebhook ? 'Edit Webhook' : 'Create Webhook' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <Input v-model="form.name" placeholder="My Webhook" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                        <Input v-model="form.url" placeholder="https://example.com/webhook" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Events</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div 
                                v-for="(label, key) in available_events" 
                                :key="key"
                                class="flex items-center gap-2"
                            >
                                <Checkbox 
                                    :checked="form.events.includes(key)"
                                    @change="toggleEvent(key)"
                                />
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                <PrimaryButton @click="save" class="ml-2">Save</PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
