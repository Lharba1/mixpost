<script setup>
import {ref} from "vue";
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
import PencilSquare from "@/Icons/PencilSquare.vue";
import Trash from "@/Icons/Trash.vue";
import Plus from "@/Icons/Plus.vue";
import Hashtag from "@/Icons/Hashtag.vue";

const props = defineProps({
    hashtag_groups: {
        type: Array,
        required: true,
    },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingGroup = ref(null);

const colors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#ef4444', 
    '#f97316', '#eab308', '#22c55e', '#14b8a6',
    '#06b6d4', '#3b82f6',
];

const createForm = useForm({
    name: '',
    hashtags: '',
    color: '#6366f1',
});

const editForm = useForm({
    name: '',
    hashtags: '',
    color: '#6366f1',
});

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const openEditModal = (group) => {
    editingGroup.value = group;
    editForm.name = group.name;
    editForm.hashtags = group.hashtags.map(t => '#' + t).join(' ');
    editForm.color = group.color;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingGroup.value = null;
    editForm.reset();
};

const createGroup = () => {
    createForm.post(route('mixpost.hashtagGroups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const updateGroup = () => {
    editForm.put(route('mixpost.hashtagGroups.update', {hashtagGroup: editingGroup.value.id}), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const deleteGroup = (group) => {
    if (confirm('Are you sure you want to delete this hashtag group?')) {
        router.delete(route('mixpost.hashtagGroups.delete', {hashtagGroup: group.id}), {
            preserveScroll: true,
        });
    }
};

const copyToClipboard = (formattedHashtags) => {
    navigator.clipboard.writeText(formattedHashtags);
};
</script>

<template>
    <Head title="Hashtag Groups"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Hashtag Groups"/>

        <div class="row-px">
            <Panel>
                <template #title>Manage Hashtag Groups</template>
                <template #description>
                    Save and reuse hashtag collections for different topics or campaigns.
                </template>

                <template #action>
                    <PrimaryButton @click="openCreateModal" size="sm">
                        <Plus class="w-4 h-4 mr-1"/>
                        New Group
                    </PrimaryButton>
                </template>

                <div v-if="hashtag_groups.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div 
                        v-for="group in hashtag_groups" 
                        :key="group.id"
                        class="relative p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div 
                                    class="w-3 h-3 rounded-full" 
                                    :style="{ backgroundColor: group.color }"
                                ></div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ group.name }}
                                </h3>
                            </div>
                            <Flex class="gap-1">
                                <button 
                                    @click="openEditModal(group)" 
                                    class="p-1 text-gray-400 hover:text-primary-500 transition-colors"
                                    title="Edit"
                                >
                                    <PencilSquare class="w-4 h-4"/>
                                </button>
                                <button 
                                    @click="deleteGroup(group)" 
                                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                    title="Delete"
                                >
                                    <Trash class="w-4 h-4"/>
                                </button>
                            </Flex>
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ group.hashtag_count }} hashtag{{ group.hashtag_count !== 1 ? 's' : '' }}
                        </div>
                        
                        <div class="flex flex-wrap gap-1 mb-3">
                            <Badge 
                                v-for="(tag, index) in group.hashtags.slice(0, 8)" 
                                :key="index" 
                                variant="info"
                                size="sm"
                            >
                                #{{ tag }}
                            </Badge>
                            <Badge v-if="group.hashtags.length > 8" variant="default" size="sm">
                                +{{ group.hashtags.length - 8 }} more
                            </Badge>
                        </div>
                        
                        <button 
                            @click="copyToClipboard(group.formatted_hashtags)"
                            class="text-xs text-primary-500 hover:text-primary-600 transition-colors"
                        >
                            Copy all hashtags
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-12 text-gray-500">
                    <Hashtag class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                    <p class="font-medium">No hashtag groups yet</p>
                    <p class="text-sm mt-1">Create your first group to organize and reuse hashtags.</p>
                </div>
            </Panel>
        </div>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
        <template #header>
            Create Hashtag Group
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="name">Group Name</Label>
                    <Input 
                        id="name" 
                        v-model="createForm.name" 
                        placeholder="e.g., Tech, Marketing, Lifestyle"
                    />
                    <Error :message="createForm.errors.name"/>
                </div>
                <div>
                    <Label for="hashtags">Hashtags</Label>
                    <Textarea 
                        id="hashtags" 
                        v-model="createForm.hashtags" 
                        rows="4"
                        placeholder="#marketing #socialmedia #growth #business"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        Enter hashtags separated by spaces or new lines
                    </p>
                    <Error :message="createForm.errors.hashtags"/>
                </div>
                <div>
                    <Label>Color</Label>
                    <div class="flex gap-2 mt-2">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            @click="createForm.color = color"
                            class="w-8 h-8 rounded-full border-2 transition-all"
                            :class="createForm.color === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                        ></button>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeCreateModal">Cancel</SecondaryButton>
            <PrimaryButton @click="createGroup" :disabled="createForm.processing" class="ml-2">
                Create Group
            </PrimaryButton>
        </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
        <template #header>
            Edit Hashtag Group
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="edit-name">Group Name</Label>
                    <Input id="edit-name" v-model="editForm.name"/>
                    <Error :message="editForm.errors.name"/>
                </div>
                <div>
                    <Label for="edit-hashtags">Hashtags</Label>
                    <Textarea id="edit-hashtags" v-model="editForm.hashtags" rows="4"/>
                    <Error :message="editForm.errors.hashtags"/>
                </div>
                <div>
                    <Label>Color</Label>
                    <div class="flex gap-2 mt-2">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            @click="editForm.color = color"
                            class="w-8 h-8 rounded-full border-2 transition-all"
                            :class="editForm.color === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                        ></button>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeEditModal">Cancel</SecondaryButton>
            <PrimaryButton @click="updateGroup" :disabled="editForm.processing" class="ml-2">
                Save Changes
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
