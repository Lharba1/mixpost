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
import Document from "@/Icons/Document.vue";
import Photo from "@/Icons/Photo.vue";

const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    current_category: {
        type: String,
        default: null,
    },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTemplate = ref(null);

const createForm = useForm({
    name: '',
    description: '',
    content: [{ body: '', media: [] }],
    category: '',
});

const editForm = useForm({
    name: '',
    description: '',
    content: [{ body: '', media: [] }],
    category: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.content = [{ body: '', media: [] }];
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const openEditModal = (template) => {
    editingTemplate.value = template;
    editForm.name = template.name;
    editForm.description = template.description || '';
    editForm.content = template.content;
    editForm.category = template.category || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingTemplate.value = null;
    editForm.reset();
};

const createTemplate = () => {
    createForm.post(route('mixpost.templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const updateTemplate = () => {
    editForm.put(route('mixpost.templates.update', {postTemplate: editingTemplate.value.id}), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const deleteTemplate = (template) => {
    if (confirm('Are you sure you want to delete this template?')) {
        router.delete(route('mixpost.templates.delete', {postTemplate: template.id}), {
            preserveScroll: true,
        });
    }
};

const filterByCategory = (category) => {
    router.get(route('mixpost.templates.index'), category ? { category } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Post Templates"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Post Templates"/>

        <div class="row-px">
            <Panel>
                <template #title>Manage Templates</template>
                <template #description>
                    Save frequently used post formats for quick reuse.
                </template>

                <template #action>
                    <PrimaryButton @click="openCreateModal" size="sm">
                        <Plus class="w-4 h-4 mr-1"/>
                        New Template
                    </PrimaryButton>
                </template>

                <!-- Category Filter -->
                <div v-if="categories.length > 0" class="mb-4 flex flex-wrap gap-2">
                    <button
                        @click="filterByCategory(null)"
                        class="px-3 py-1 text-sm rounded-full transition-colors"
                        :class="!current_category ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    >
                        All
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category"
                        @click="filterByCategory(category)"
                        class="px-3 py-1 text-sm rounded-full transition-colors"
                        :class="current_category === category ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    >
                        {{ category }}
                    </button>
                </div>

                <div v-if="templates.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div 
                        v-for="template in templates" 
                        :key="template.id"
                        class="relative p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ template.name }}
                                </h3>
                                <p v-if="template.category" class="text-xs text-gray-400 mt-0.5">
                                    {{ template.category }}
                                </p>
                            </div>
                            <Flex class="gap-1">
                                <button 
                                    @click="openEditModal(template)" 
                                    class="p-1 text-gray-400 hover:text-primary-500 transition-colors"
                                    title="Edit"
                                >
                                    <PencilSquare class="w-4 h-4"/>
                                </button>
                                <button 
                                    @click="deleteTemplate(template)" 
                                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                    title="Delete"
                                >
                                    <Trash class="w-4 h-4"/>
                                </button>
                            </Flex>
                        </div>
                        
                        <p v-if="template.description" class="text-sm text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">
                            {{ template.description }}
                        </p>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-3">
                            {{ template.preview_text || 'No content' }}
                        </p>
                        
                        <Flex v-if="template.has_media" class="text-xs text-gray-400 gap-1">
                            <Photo class="w-4 h-4"/>
                            {{ template.media_count }} media file{{ template.media_count !== 1 ? 's' : '' }}
                        </Flex>
                    </div>
                </div>

                <div v-else class="text-center py-12 text-gray-500">
                    <Document class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                    <p class="font-medium">No templates yet</p>
                    <p class="text-sm mt-1">Create your first template to speed up your workflow.</p>
                </div>
            </Panel>
        </div>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal" max-width="lg">
        <template #header>
            Create Template
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="name">Template Name</Label>
                    <Input 
                        id="name" 
                        v-model="createForm.name" 
                        placeholder="e.g., Weekly Tip, Product Launch"
                    />
                    <Error :message="createForm.errors.name"/>
                </div>
                <div>
                    <Label for="description">Description (optional)</Label>
                    <Input 
                        id="description" 
                        v-model="createForm.description" 
                        placeholder="Brief description of when to use this template"
                    />
                    <Error :message="createForm.errors.description"/>
                </div>
                <div>
                    <Label for="category">Category (optional)</Label>
                    <Input 
                        id="category" 
                        v-model="createForm.category" 
                        placeholder="e.g., Marketing, Updates, Tips"
                        list="category-suggestions"
                    />
                    <datalist id="category-suggestions">
                        <option v-for="cat in categories" :key="cat" :value="cat"/>
                    </datalist>
                </div>
                <div>
                    <Label for="content">Post Content</Label>
                    <Textarea 
                        id="content" 
                        v-model="createForm.content[0].body" 
                        rows="6"
                        placeholder="Write your template content here. You can use variables like {date} or {time}."
                    />
                    <Error :message="createForm.errors.content"/>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeCreateModal">Cancel</SecondaryButton>
            <PrimaryButton @click="createTemplate" :disabled="createForm.processing" class="ml-2">
                Create Template
            </PrimaryButton>
        </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal" max-width="lg">
        <template #header>
            Edit Template
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="edit-name">Template Name</Label>
                    <Input id="edit-name" v-model="editForm.name"/>
                    <Error :message="editForm.errors.name"/>
                </div>
                <div>
                    <Label for="edit-description">Description</Label>
                    <Input id="edit-description" v-model="editForm.description"/>
                    <Error :message="editForm.errors.description"/>
                </div>
                <div>
                    <Label for="edit-category">Category</Label>
                    <Input id="edit-category" v-model="editForm.category" list="category-suggestions-edit"/>
                    <datalist id="category-suggestions-edit">
                        <option v-for="cat in categories" :key="cat" :value="cat"/>
                    </datalist>
                </div>
                <div>
                    <Label for="edit-content">Post Content</Label>
                    <Textarea id="edit-content" v-model="editForm.content[0].body" rows="6"/>
                    <Error :message="editForm.errors.content"/>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeEditModal">Cancel</SecondaryButton>
            <PrimaryButton @click="updateTemplate" :disabled="editForm.processing" class="ml-2">
                Save Changes
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
