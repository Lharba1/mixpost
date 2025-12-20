<script setup>
import {ref, onMounted} from "vue";
import Dropdown from "../Dropdown/Dropdown.vue";
import DropdownItem from "../Dropdown/DropdownItem.vue";
import Document from "../../Icons/Document.vue";
import Photo from "../../Icons/Photo.vue";

const props = defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['apply']);

const localTemplates = ref([]);
const loading = ref(false);

const fetchTemplates = async () => {
    if (props.templates.length > 0) {
        localTemplates.value = props.templates;
        return;
    }

    loading.value = true;
    try {
        const response = await fetch(route('mixpost.templates.all'));
        const data = await response.json();
        localTemplates.value = data.templates || [];
    } catch (error) {
        console.error('Failed to fetch templates:', error);
    } finally {
        loading.value = false;
    }
};

const applyTemplate = (template) => {
    emit('apply', template.content);
};

onMounted(() => {
    fetchTemplates();
});
</script>

<template>
    <Dropdown width-class="w-80" placement="bottom-start">
        <template #trigger>
            <button 
                type="button" 
                class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Apply Template"
            >
                <Document class="w-5 h-5 text-gray-500"/>
            </button>
        </template>

        <template #content>
            <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                Apply Template
            </div>

            <div v-if="loading" class="px-3 py-4 text-center text-gray-500">
                Loading...
            </div>

            <div v-else class="max-h-80 overflow-y-auto">
                <template v-if="localTemplates.length > 0">
                    <DropdownItem 
                        v-for="template in localTemplates" 
                        :key="template.id"
                        @click="applyTemplate(template)"
                        class="block"
                    >
                        <div class="flex items-start justify-between mb-1">
                            <span class="font-medium text-sm">{{ template.name }}</span>
                            <span v-if="template.category" class="text-xs text-gray-400">
                                {{ template.category }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-2 mb-1">
                            {{ template.preview_text || 'No content' }}
                        </p>
                        <div v-if="template.has_media" class="flex items-center gap-1 text-xs text-gray-400">
                            <Photo class="w-3 h-3"/>
                            {{ template.media_count }} media
                        </div>
                    </DropdownItem>
                </template>

                <!-- Empty State -->
                <div v-else class="px-3 py-4 text-center text-gray-500 text-sm">
                    No templates available.
                    <a :href="route('mixpost.templates.index')" class="text-primary-500 hover:underline block mt-1">
                        Create one
                    </a>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
