<script setup>
import {ref, onMounted} from "vue";
import Dropdown from "../Dropdown/Dropdown.vue";
import DropdownItem from "../Dropdown/DropdownItem.vue";
import Hashtag from "../../Icons/Hashtag.vue";
import Badge from "../DataDisplay/Badge.vue";

const props = defineProps({
    hashtagGroups: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['insert']);

const localGroups = ref([]);
const loading = ref(false);

const fetchGroups = async () => {
    if (props.hashtagGroups.length > 0) {
        localGroups.value = props.hashtagGroups;
        return;
    }

    loading.value = true;
    try {
        const response = await fetch(route('mixpost.hashtagGroups.all'));
        const data = await response.json();
        localGroups.value = data.hashtag_groups || [];
    } catch (error) {
        console.error('Failed to fetch hashtag groups:', error);
    } finally {
        loading.value = false;
    }
};

const insertGroup = (group) => {
    emit('insert', group.formatted_hashtags);
};

onMounted(() => {
    fetchGroups();
});
</script>

<template>
    <Dropdown width-class="w-72" placement="bottom-start">
        <template #trigger>
            <button 
                type="button" 
                class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Insert Hashtag Group"
            >
                <Hashtag class="w-5 h-5 text-gray-500"/>
            </button>
        </template>

        <template #content>
            <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                Insert Hashtag Group
            </div>

            <div v-if="loading" class="px-3 py-4 text-center text-gray-500">
                Loading...
            </div>

            <div v-else class="max-h-72 overflow-y-auto">
                <template v-if="localGroups.length > 0">
                    <DropdownItem 
                        v-for="group in localGroups" 
                        :key="group.id"
                        @click="insertGroup(group)"
                        class="block"
                    >
                        <div class="flex items-center gap-2 mb-1">
                            <div 
                                class="w-2.5 h-2.5 rounded-full flex-shrink-0" 
                                :style="{ backgroundColor: group.color }"
                            ></div>
                            <span class="font-medium">{{ group.name }}</span>
                            <span class="text-xs text-gray-400 ml-auto">
                                {{ group.hashtag_count }} tags
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <Badge 
                                v-for="(tag, index) in group.hashtags.slice(0, 4)" 
                                :key="index" 
                                variant="info"
                                size="xs"
                            >
                                #{{ tag }}
                            </Badge>
                            <span v-if="group.hashtags.length > 4" class="text-xs text-gray-400">
                                +{{ group.hashtags.length - 4 }}
                            </span>
                        </div>
                    </DropdownItem>
                </template>

                <!-- Empty State -->
                <div v-else class="px-3 py-4 text-center text-gray-500 text-sm">
                    No hashtag groups available.
                    <a :href="route('mixpost.hashtagGroups.index')" class="text-primary-500 hover:underline block mt-1">
                        Create one
                    </a>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
