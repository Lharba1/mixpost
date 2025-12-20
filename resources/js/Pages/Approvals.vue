<script setup>
import { ref, computed } from "vue";
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
import Checkbox from "@/Components/Form/Checkbox.vue";
import NoResult from "@/Components/Util/NoResult.vue";

const props = defineProps({
    pending_approvals: Array,
    workflows: Array,
});

const showCreateWorkflow = ref(false);
const workflowForm = ref({
    name: '',
    description: '',
    required_approvals: 1,
    require_all: false,
    approver_ids: [],
});

const approvePost = (approval) => {
    if (confirm('Approve this post?')) {
        router.post(route('mixpost.approvals.approve', approval.id), {
            comment: '',
        });
    }
};

const rejectPost = (approval) => {
    const reason = prompt('Reason for rejection:');
    if (reason) {
        router.post(route('mixpost.approvals.reject', approval.id), {
            comment: reason,
        });
    }
};

const getStatusVariant = (status) => {
    const variants = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
    };
    return variants[status] || 'default';
};
</script>

<template>
    <div>
        <PageHeader title="Approvals">
            <template #description>
                Review and approve posts before publishing
            </template>
        </PageHeader>

        <div class="row-py">
            <!-- Pending Approvals -->
            <Panel title="Pending Approvals" class="mb-6">
                <template v-if="pending_approvals && pending_approvals.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="approval in pending_approvals" 
                            :key="approval.id"
                            class="py-4 flex items-start justify-between gap-4"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <Badge :variant="getStatusVariant(approval.status)">
                                        {{ approval.status_label }}
                                    </Badge>
                                    <span class="text-sm text-gray-500">
                                        {{ approval.approvals_received }}/{{ approval.approvals_required }} approvals
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                    {{ approval.post?.preview || 'No preview available' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Requested by {{ approval.requester?.name || 'Unknown' }} • {{ approval.created_at_formatted }}
                                </p>
                                <p v-if="approval.notes" class="text-xs text-gray-500 mt-1 italic">
                                    Note: {{ approval.notes }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <PrimaryButton size="sm" @click="approvePost(approval)">
                                    Approve
                                </PrimaryButton>
                                <DangerButton size="sm" @click="rejectPost(approval)">
                                    Reject
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No pending approvals
                </NoResult>
            </Panel>

            <!-- Workflows Section -->
            <Panel title="Approval Workflows">
                <template #action>
                    <SecondaryButton size="sm" @click="showCreateWorkflow = true">
                        Create Workflow
                    </SecondaryButton>
                </template>

                <template v-if="workflows && workflows.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="workflow in workflows" 
                            :key="workflow.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ workflow.name }}
                                    <Badge v-if="workflow.is_default" variant="info" size="sm" class="ml-2">
                                        Default
                                    </Badge>
                                </p>
                                <p v-if="workflow.description" class="text-sm text-gray-500">
                                    {{ workflow.description }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ workflow.required_approvals }} approval(s) required
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="workflow.is_active ? 'success' : 'default'">
                                    {{ workflow.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No workflows configured
                </NoResult>
            </Panel>
        </div>
    </div>
</template>
