<script setup>
import {ref, watch} from "vue";
import Textarea from "../Form/Textarea.vue";
import Label from "../Form/Label.vue";
import PureButton from "../Button/PureButton.vue";
import ChevronDown from "../../Icons/ChevronDown.vue";
import ChatBubble from "../../Icons/ChatBubble.vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    accountName: {
        type: String,
        default: '',
    },
    maxLength: {
        type: Number,
        default: 500,
    },
    placeholder: {
        type: String,
        default: 'Add a first comment to this post...',
    },
});

const emit = defineEmits(['update:modelValue']);

const isExpanded = ref(!!props.modelValue);
const localValue = ref(props.modelValue);

watch(() => props.modelValue, (newVal) => {
    localValue.value = newVal;
});

const updateValue = () => {
    emit('update:modelValue', localValue.value);
};

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const characterCount = () => {
    return localValue.value?.length || 0;
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
                <ChatBubble class="w-4 h-4 text-gray-500"/>
                <span class="font-medium text-gray-700 dark:text-gray-300">First Comment</span>
                <span v-if="modelValue" class="text-xs text-green-500">(Added)</span>
            </div>
            <ChevronDown 
                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isExpanded }"
            />
        </button>
        
        <div v-show="isExpanded" class="p-3 bg-white dark:bg-gray-900">
            <p class="text-xs text-gray-500 mb-2">
                This comment will be automatically posted as a reply to your main post.
                <span v-if="accountName">Perfect for adding links or extra hashtags on {{ accountName }}.</span>
            </p>
            
            <Textarea 
                v-model="localValue"
                @input="updateValue"
                :rows="3"
                :placeholder="placeholder"
                class="w-full"
            />
            
            <div class="flex justify-between items-center mt-2">
                <span 
                    class="text-xs"
                    :class="characterCount() > maxLength ? 'text-red-500' : 'text-gray-400'"
                >
                    {{ characterCount() }} / {{ maxLength }}
                </span>
                <PureButton 
                    v-if="localValue" 
                    @click="localValue = ''; updateValue()"
                    class="text-xs text-red-500"
                >
                    Clear
                </PureButton>
            </div>
        </div>
    </div>
</template>
