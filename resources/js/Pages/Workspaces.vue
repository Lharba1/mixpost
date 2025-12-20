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
import Textarea from "@/Components/Form/Textarea.vue";
import Select from "@/Components/Form/Select.vue";
import NoResult from "@/Components/Util/NoResult.vue";

const props = defineProps({
    workspaces: Array,
    current_workspace_id: Number,
});

const showCreateModal = ref(false);
const showInviteModal = ref(false);
const selectedWorkspace = ref(null);

const form = reactive({
    name: '',
    description: '',
    color: '#6366f1',
});

const inviteForm = reactive({
    email: '',
    role: 'member',
});

const resetForm = () => {
    form.name = '';
    form.description = '';
    form.color = '#6366f1';
};

const colors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f97316',
    '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6',
];

const createWorkspace = () => {
    router.post(route('mixpost.workspaces.store'), form, {
        onSuccess: () => {
            showCreateModal.value = false;
            resetForm();
        },
    });
};

const switchWorkspace = (workspace) => {
    router.post(route('mixpost.workspaces.switch', workspace.id));
};

const openInvite = (workspace) => {
    selectedWorkspace.value = workspace;
    inviteForm.email = '';
    inviteForm.role = 'member';
    showInviteModal.value = true;
};

const sendInvite = () => {
    router.post(route('mixpost.workspaces.invite', selectedWorkspace.value.id), inviteForm, {
        onSuccess: () => {
            showInviteModal.value = false;
        },
    });
};

const leaveWorkspace = (workspace) => {
    if (confirm('Are you sure you want to leave this workspace?')) {
        router.post(route('mixpost.workspaces.leave', workspace.id));
    }
};

const deleteWorkspace = (workspace) => {
    if (confirm('Delete this workspace? All data will be lost.')) {
        router.delete(route('mixpost.workspaces.destroy', workspace.id));
    }
};

const getRoleBadgeVariant = (role) => {
    const variants = {
        owner: 'primary',
        admin: 'info',
        member: 'default',
        viewer: 'default',
    };
    return variants[role] || 'default';
};
</script>

<template>
    <div>
        <PageHeader title="Workspaces">
            <template #description>
                Organize your work across multiple workspaces
            </template>
        </PageHeader>

        <div class="row-py">
            <Panel>
                <template #action>
                    <PrimaryButton size="sm" @click="showCreateModal = true">
                        Create Workspace
                    </PrimaryButton>
                </template>

                <template v-if="workspaces && workspaces.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="workspace in workspaces" 
                            :key="workspace.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative"
                            :class="{ 'ring-2 ring-indigo-500': workspace.id === current_workspace_id }"
                        >
                            <div 
                                class="absolute top-0 left-0 right-0 h-1 rounded-t-lg"
                                :style="{ backgroundColor: workspace.color }"
                            ></div>

                            <div class="flex items-start justify-between mt-2">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ workspace.name }}
                                    </h3>
                                    <Badge 
                                        v-if="workspace.id === current_workspace_id" 
                                        variant="success" 
                                        size="sm"
                                        class="mt-1"
                                    >
                                        Current
                                    </Badge>
                                </div>
                                <Badge :variant="getRoleBadgeVariant(workspace.pivot?.role)" size="sm">
                                    {{ workspace.pivot?.role || 'member' }}
                                </Badge>
                            </div>

                            <p v-if="workspace.description" class="text-sm text-gray-500 mt-2">
                                {{ workspace.description }}
                            </p>

                            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                <span>{{ workspace.members_count || 0 }} members</span>
                                <span>{{ workspace.accounts_count || 0 }} accounts</span>
                            </div>

                            <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <SecondaryButton 
                                    v-if="workspace.id !== current_workspace_id"
                                    size="xs" 
                                    @click="switchWorkspace(workspace)"
                                >
                                    Switch
                                </SecondaryButton>
                                <SecondaryButton 
                                    v-if="workspace.pivot?.role === 'owner' || workspace.pivot?.role === 'admin'"
                                    size="xs" 
                                    @click="openInvite(workspace)"
                                >
                                    Invite
                                </SecondaryButton>
                                <DangerButton 
                                    v-if="workspace.pivot?.role !== 'owner'"
                                    size="xs" 
                                    @click="leaveWorkspace(workspace)"
                                >
                                    Leave
                                </DangerButton>
                                <DangerButton 
                                    v-if="workspace.pivot?.role === 'owner'"
                                    size="xs" 
                                    @click="deleteWorkspace(workspace)"
                                >
                                    Delete
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No workspaces. Create one to get started.
                </NoResult>
            </Panel>
        </div>

        <!-- Create Workspace Modal -->
        <DialogModal :show="showCreateModal" @close="showCreateModal = false">
            <template #title>Create Workspace</template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <Input v-model="form.name" placeholder="Marketing Team" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <Textarea v-model="form.description" rows="2" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                        <div class="flex gap-2">
                            <button
                                v-for="color in colors"
                                :key="color"
                                class="w-8 h-8 rounded-full transition-transform"
                                :style="{ backgroundColor: color }"
                                :class="{ 'ring-2 ring-offset-2 ring-gray-400 scale-110': form.color === color }"
                                @click="form.color = color"
                            ></button>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                <PrimaryButton @click="createWorkspace" class="ml-2">Create</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Invite Modal -->
        <DialogModal :show="showInviteModal" @close="showInviteModal = false">
            <template #title>Invite to {{ selectedWorkspace?.name }}</template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <Input v-model="inviteForm.email" type="email" placeholder="user@example.com" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                        <Select v-model="inviteForm.role" class="w-full">
                            <option value="admin">Admin</option>
                            <option value="member">Member</option>
                            <option value="viewer">Viewer</option>
                        </Select>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showInviteModal = false">Cancel</SecondaryButton>
                <PrimaryButton @click="sendInvite" class="ml-2">Send Invite</PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
