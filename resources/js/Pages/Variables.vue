<script setup>
import {ref, computed} from "vue";
import {useForm, router} from "@inertiajs/vue3";
import {Head} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Input from "@/Components/Form/Input.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Label from "@/Components/Form/Label.vue";
import Error from "@/Components/Form/Error.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Trash from "@/Icons/Trash.vue";
import Plus from "@/Icons/Plus.vue";
import CommandLine from "@/Icons/CommandLine.vue";

const props = defineProps({
    custom_variables: {
        type: Array,
        required: true,
    },
    system_variables: {
        type: Array,
        required: true,
    },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingVariable = ref(null);

const createForm = useForm({
    name: '',
    key: '',
    value: '',
});

const editForm = useForm({
    name: '',
    key: '',
    value: '',
});

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const openEditModal = (variable) => {
    editingVariable.value = variable;
    editForm.name = variable.name;
    editForm.key = variable.key;
    editForm.value = variable.value;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingVariable.value = null;
    editForm.reset();
};

const createVariable = () => {
    createForm.post(route('mixpost.variables.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const updateVariable = () => {
    editForm.put(route('mixpost.variables.update', {variable: editingVariable.value.id}), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const deleteVariable = (variable) => {
    if (confirm('Are you sure you want to delete this variable?')) {
        router.delete(route('mixpost.variables.delete', {variable: variable.id}), {
            preserveScroll: true,
        });
    }
};

const generateKey = () => {
    if (createForm.name && !createForm.key) {
        createForm.key = createForm.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }
};
</script>

<template>
    <Head title="Variables"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Variables"/>

        <div class="row-px">
            <!-- System Variables -->
            <Panel>
                <template #title>System Variables</template>
                <template #description>
                    Built-in variables that are automatically replaced with current values at publish time.
                </template>

                <Table>
                    <template #head>
                        <TableRow>
                            <TableCell heading>Name</TableCell>
                            <TableCell heading>Placeholder</TableCell>
                            <TableCell heading>Current Value</TableCell>
                        </TableRow>
                    </template>
                    <template #body>
                        <TableRow v-for="variable in system_variables" :key="variable.key">
                            <TableCell>{{ variable.name }}</TableCell>
                            <TableCell>
                                <Badge variant="info">{{ '{' + variable.key + '}' }}</Badge>
                            </TableCell>
                            <TableCell>{{ variable.value }}</TableCell>
                        </TableRow>
                    </template>
                </Table>
            </Panel>

            <!-- Custom Variables -->
            <Panel class="mt-lg">
                <template #title>Custom Variables</template>
                <template #description>
                    Create your own variables for repeated content like signatures, links, or promotions.
                </template>

                <template #action>
                    <PrimaryButton @click="openCreateModal" size="sm">
                        <Plus class="w-4 h-4 mr-1"/>
                        Add Variable
                    </PrimaryButton>
                </template>

                <Table v-if="custom_variables.length > 0">
                    <template #head>
                        <TableRow>
                            <TableCell heading>Name</TableCell>
                            <TableCell heading>Placeholder</TableCell>
                            <TableCell heading>Value</TableCell>
                            <TableCell heading class="w-24">Actions</TableCell>
                        </TableRow>
                    </template>
                    <template #body>
                        <TableRow v-for="variable in custom_variables" :key="variable.id">
                            <TableCell>{{ variable.name }}</TableCell>
                            <TableCell>
                                <Badge variant="info">{{ variable.placeholder }}</Badge>
                            </TableCell>
                            <TableCell class="max-w-xs truncate">{{ variable.value }}</TableCell>
                            <TableCell>
                                <Flex class="gap-2">
                                    <button @click="openEditModal(variable)" class="text-gray-500 hover:text-primary-500">
                                        <PencilSquare class="w-5 h-5"/>
                                    </button>
                                    <button @click="deleteVariable(variable)" class="text-gray-500 hover:text-red-500">
                                        <Trash class="w-5 h-5"/>
                                    </button>
                                </Flex>
                            </TableCell>
                        </TableRow>
                    </template>
                </Table>

                <div v-else class="text-center py-8 text-gray-500">
                    <CommandLine class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                    <p>No custom variables yet.</p>
                    <p class="text-sm">Create your first variable to use in posts.</p>
                </div>
            </Panel>
        </div>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
        <template #header>
            Create Variable
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input 
                        id="name" 
                        v-model="createForm.name" 
                        @blur="generateKey"
                        placeholder="e.g., My Signature"
                    />
                    <Error :message="createForm.errors.name"/>
                </div>
                <div>
                    <Label for="key">
                        Key
                        <span class="text-gray-400 text-sm ml-1">(used as {'{key}'})</span>
                    </Label>
                    <Input 
                        id="key" 
                        v-model="createForm.key" 
                        placeholder="e.g., my_signature"
                    />
                    <Error :message="createForm.errors.key"/>
                </div>
                <div>
                    <Label for="value">Value</Label>
                    <Textarea 
                        id="value" 
                        v-model="createForm.value" 
                        rows="3"
                        placeholder="The text that will replace the variable"
                    />
                    <Error :message="createForm.errors.value"/>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeCreateModal">Cancel</SecondaryButton>
            <PrimaryButton @click="createVariable" :disabled="createForm.processing" class="ml-2">
                Create Variable
            </PrimaryButton>
        </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
        <template #header>
            Edit Variable
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="edit-name">Name</Label>
                    <Input id="edit-name" v-model="editForm.name"/>
                    <Error :message="editForm.errors.name"/>
                </div>
                <div>
                    <Label for="edit-key">Key</Label>
                    <Input id="edit-key" v-model="editForm.key"/>
                    <Error :message="editForm.errors.key"/>
                </div>
                <div>
                    <Label for="edit-value">Value</Label>
                    <Textarea id="edit-value" v-model="editForm.value" rows="3"/>
                    <Error :message="editForm.errors.value"/>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeEditModal">Cancel</SecondaryButton>
            <PrimaryButton @click="updateVariable" :disabled="editForm.processing" class="ml-2">
                Save Changes
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
