<script setup>
import {ref, watch, computed} from "vue";
import Textarea from "../Form/Textarea.vue";
import PrimaryButton from "../Button/PrimaryButton.vue";
import SecondaryButton from "../Button/SecondaryButton.vue";
import PureButton from "../Button/PureButton.vue";
import ChevronDown from "../../Icons/ChevronDown.vue";
import ChevronUp from "../../Icons/ChevronUp.vue";
import Bars3 from "../../Icons/Bars3.vue";
import Plus from "../../Icons/Plus.vue";
import Trash from "../../Icons/Trash.vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    maxThreads: {
        type: Number,
        default: 25, // X allows up to 25 tweets in a thread
    },
    maxLength: {
        type: Number,
        default: 280,
    },
    platform: {
        type: String,
        default: 'twitter',
    },
});

const emit = defineEmits(['update:modelValue']);

const isExpanded = ref(props.modelValue.length > 0);
const threads = ref(props.modelValue.length > 0 ? [...props.modelValue] : []);

watch(() => props.modelValue, (newVal) => {
    if (JSON.stringify(newVal) !== JSON.stringify(threads.value)) {
        threads.value = [...newVal];
    }
}, { deep: true });

const updateValue = () => {
    emit('update:modelValue', threads.value);
};

const addThread = () => {
    if (threads.value.length < props.maxThreads) {
        threads.value.push({ body: '', media: [] });
        updateValue();
    }
};

const removeThread = (index) => {
    threads.value.splice(index, 1);
    updateValue();
};

const updateThreadContent = (index, value) => {
    threads.value[index].body = value;
    updateValue();
};

const moveThread = (fromIndex, toIndex) => {
    if (toIndex >= 0 && toIndex < threads.value.length) {
        const item = threads.value.splice(fromIndex, 1)[0];
        threads.value.splice(toIndex, 0, item);
        updateValue();
    }
};

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const totalCharacters = computed(() => {
    return threads.value.reduce((sum, t) => sum + (t.body?.length || 0), 0);
});

const platformLabel = computed(() => {
    const labels = {
        twitter: 'X (Twitter)',
        mastodon: 'Mastodon',
        bluesky: 'Bluesky',
    };
    return labels[props.platform] || 'Thread';
});

const clearAll = () => {
    threads.value = [];
    updateValue();
};
</script>

<template>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <button 
            type="button"
            @click="toggleExpanded"
            class="w-full flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
            <div class="flex items-center gap-2 text-sm">
                <Bars3 class="w-4 h-4 text-gray-500"/>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ platformLabel }} Thread</span>
                <span v-if="threads.length > 0" class="text-xs text-green-500">
                    ({{ threads.length }} post{{ threads.length !== 1 ? 's' : '' }})
                </span>
            </div>
            <component 
                :is="isExpanded ? ChevronUp : ChevronDown" 
                class="w-4 h-4 text-gray-400"
            />
        </button>
        
        <div v-show="isExpanded" class="p-3 bg-white dark:bg-gray-900">
            <p class="text-xs text-gray-500 mb-3">
                Create a thread of connected posts. Each post will be published as a reply to the previous one.
            </p>
            
            <!-- Thread Posts -->
            <div class="space-y-3">
                <div 
                    v-for="(thread, index) in threads" 
                    :key="index"
                    class="relative p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500">
                            Post {{ index + 2 }} of {{ threads.length + 1 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button 
                                v-if="index > 0"
                                @click="moveThread(index, index - 1)"
                                type="button"
                                class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                                title="Move up"
                            >
                                <ChevronUp class="w-4 h-4"/>
                            </button>
                            <button 
                                v-if="index < threads.length - 1"
                                @click="moveThread(index, index + 1)"
                                type="button"
                                class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                                title="Move down"
                            >
                                <ChevronDown class="w-4 h-4"/>
                            </button>
                            <button 
                                @click="removeThread(index)"
                                type="button"
                                class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                title="Remove"
                            >
                                <Trash class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>
                    
                    <Textarea 
                        :modelValue="thread.body"
                        @update:modelValue="(val) => updateThreadContent(index, val)"
                        :rows="3"
                        placeholder="Continue your thread..."
                        class="w-full"
                    />
                    
                    <div class="flex justify-end mt-1">
                        <span 
                            class="text-xs"
                            :class="(thread.body?.length || 0) > maxLength ? 'text-red-500' : 'text-gray-400'"
                        >
                            {{ thread.body?.length || 0 }} / {{ maxLength }}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Add Thread Button -->
            <div class="mt-3 flex items-center justify-between">
                <SecondaryButton 
                    v-if="threads.length < maxThreads"
                    @click="addThread"
                    size="sm"
                >
                    <Plus class="w-4 h-4 mr-1"/>
                    Add Post to Thread
                </SecondaryButton>
                <span v-else class="text-xs text-gray-500">
                    Maximum {{ maxThreads }} posts reached
                </span>
                
                <PureButton 
                    v-if="threads.length > 0" 
                    @click="clearAll"
                    class="text-xs text-red-500"
                >
                    Clear Thread
                </PureButton>
            </div>
        </div>
    </div>
</template>
