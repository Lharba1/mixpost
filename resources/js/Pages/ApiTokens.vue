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
import Alert from "@/Components/Util/Alert.vue";

const props = defineProps({
    tokens: Array,
    available_abilities: Object,
});

const showCreateModal = ref(false);
const newToken = ref(null);

const form = reactive({
    name: '',
    abilities: [],
    expires_at: '',
});

const resetForm = () => {
    form.name = '';
    form.abilities = Object.keys(props.available_abilities || {});
    form.expires_at = '';
};

const openCreate = () => {
    resetForm();
    newToken.value = null;
    showCreateModal.value = true;
};

const createToken = async () => {
    try {
        const response = await fetch(route('mixpost.apiTokens.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(form),
        });
        const data = await response.json();
        if (data.success) {
            newToken.value = data.token;
            router.reload({ only: ['tokens'] });
        }
    } catch (error) {
        console.error('Failed to create token:', error);
    }
};

const copyToken = () => {
    navigator.clipboard.writeText(newToken.value);
    alert('Token copied to clipboard!');
};

const deleteToken = (token) => {
    if (confirm('Delete this API token? This cannot be undone.')) {
        router.delete(route('mixpost.apiTokens.destroy', token.id));
    }
};

const toggleAbility = (ability) => {
    const index = form.abilities.indexOf(ability);
    if (index > -1) {
        form.abilities.splice(index, 1);
    } else {
        form.abilities.push(ability);
    }
};
</script>

<template>
    <div>
        <PageHeader title="API Tokens">
            <template #description>
                Manage API tokens for external integrations
            </template>
        </PageHeader>

        <div class="row-py">
            <Panel>
                <template #action>
                    <PrimaryButton size="sm" @click="openCreate">
                        Create Token
                    </PrimaryButton>
                </template>

                <template v-if="tokens && tokens.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="token in tokens" 
                            :key="token.id"
                            class="py-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ token.name }}
                                        </h3>
                                        <Badge :variant="token.is_active ? 'success' : 'default'" size="sm">
                                            {{ token.is_active ? 'Active' : 'Revoked' }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-500 font-mono">
                                        {{ token.masked_token }}
                                    </p>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <Badge 
                                            v-for="ability in token.abilities" 
                                            :key="ability"
                                            variant="info" 
                                            size="sm"
                                        >
                                            {{ ability }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Created {{ token.created_at }} • 
                                        <span v-if="token.last_used_at">Last used {{ token.last_used_at }}</span>
                                        <span v-else>Never used</span>
                                        • {{ token.logs_count }} requests
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <DangerButton size="xs" @click="deleteToken(token)">
                                        Revoke
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No API tokens. Create one to integrate with external services.
                </NoResult>
            </Panel>
        </div>

        <!-- Create Modal -->
        <DialogModal :show="showCreateModal" @close="!newToken && (showCreateModal = false)">
            <template #title>
                {{ newToken ? 'Token Created!' : 'Create API Token' }}
            </template>

            <template #content>
                <template v-if="newToken">
                    <Alert variant="warning" class="mb-4">
                        Copy this token now. You won't be able to see it again!
                    </Alert>
                    <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded font-mono text-sm break-all">
                        {{ newToken }}
                    </div>
                </template>
                <template v-else>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Token Name</label>
                            <Input v-model="form.name" placeholder="My Integration" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expires At (optional)</label>
                            <Input v-model="form.expires_at" type="date" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permissions</label>
                            <div class="space-y-2">
                                <div 
                                    v-for="(label, ability) in available_abilities" 
                                    :key="ability"
                                    class="flex items-center gap-2"
                                >
                                    <Checkbox 
                                        :checked="form.abilities.includes(ability)"
                                        @change="toggleAbility(ability)"
                                    />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <template #footer>
                <template v-if="newToken">
                    <SecondaryButton @click="copyToken">Copy Token</SecondaryButton>
                    <PrimaryButton @click="showCreateModal = false" class="ml-2">Done</PrimaryButton>
                </template>
                <template v-else>
                    <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="createToken" class="ml-2">Create Token</PrimaryButton>
                </template>
            </template>
        </DialogModal>
    </div>
</template>
