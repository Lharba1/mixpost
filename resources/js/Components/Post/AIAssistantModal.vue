<script setup>
import { ref, computed } from "vue";
import DialogModal from "../Modal/DialogModal.vue";
import PrimaryButton from "../Button/PrimaryButton.vue";
import SecondaryButton from "../Button/SecondaryButton.vue";
import Textarea from "../Form/Textarea.vue";
import Sparkles from "../../Icons/Sparkles.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    currentContent: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'apply']);

const isLoading = ref(false);
const error = ref(null);
const result = ref('');
const activeTab = ref('improve');
const customPrompt = ref('');

// Quick action buttons for improve tab
const improveActions = [
    { id: 'rewrite', label: '✨ Improve', tone: 'professional' },
    { id: 'summarize', label: '📝 Shorter', maxLength: 200 },
    { id: 'optimize', label: '🎯 Engaging', platform: 'instagram' },
    { id: 'hashtags', label: '#️⃣ Hashtags', count: 5 },
];

const generateActions = [
    { id: 'ideas', label: '💡 Get Ideas', topic: '' },
];

const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
};

const callAI = async (endpoint, body) => {
    isLoading.value = true;
    error.value = null;
    result.value = '';

    try {
        const response = await fetch(`/mixpost/ai/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });

        const data = await response.json();

        if (data.success) {
            if (endpoint === 'hashtags' && data.hashtags) {
                result.value = data.hashtags.join(' ');
            } else {
                result.value = data.content || '';
            }
        } else {
            error.value = data.error || 'AI request failed';
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service';
        console.error('AI error:', e);
    } finally {
        isLoading.value = false;
    }
};

const improve = (action) => {
    if (!props.currentContent) {
        error.value = 'Please write some content first';
        return;
    }

    if (action.id === 'rewrite') {
        callAI('rewrite', { content: props.currentContent, tone: action.tone });
    } else if (action.id === 'summarize') {
        callAI('summarize', { content: props.currentContent, max_length: action.maxLength });
    } else if (action.id === 'optimize') {
        callAI('optimize', { content: props.currentContent, platform: action.platform });
    } else if (action.id === 'hashtags') {
        callAI('hashtags', { content: props.currentContent, count: action.count });
    }
};

const generate = () => {
    if (!customPrompt.value) {
        error.value = 'Please enter a prompt';
        return;
    }
    callAI('generate', { prompt: customPrompt.value });
};

const getIdeas = () => {
    const topic = customPrompt.value || 'social media marketing';
    callAI('ideas', { topic, count: 3 });
};

const applyResult = () => {
    if (result.value) {
        emit('apply', result.value);
        result.value = '';
    }
};

const appendHashtags = () => {
    if (result.value) {
        emit('apply', props.currentContent + '\n\n' + result.value);
        result.value = '';
    }
};

const close = () => {
    result.value = '';
    error.value = null;
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" @close="close" max-width="2xl">
        <template #header>
            <div class="flex items-center gap-2">
                <Sparkles class="w-5 h-5 text-purple-500" />
                <span class="font-semibold">AI Assistant</span>
            </div>
        </template>

        <template #body>
            <div class="space-y-4">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="activeTab = 'improve'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'improve' 
                            ? 'border-purple-500 text-purple-600 dark:text-purple-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        Improve Content
                    </button>
                    <button
                        @click="activeTab = 'generate'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'generate' 
                            ? 'border-purple-500 text-purple-600 dark:text-purple-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        Generate
                    </button>
                </div>

                <!-- Improve Tab -->
                <div v-if="activeTab === 'improve'" class="space-y-4">
                    <p class="text-sm text-gray-500">
                        Select an action to enhance your current content:
                    </p>
                    
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="action in improveActions"
                            :key="action.id"
                            @click="improve(action)"
                            :disabled="isLoading"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
                        >
                            {{ action.label }}
                        </button>
                    </div>

                    <div v-if="!currentContent" class="text-sm text-amber-600 dark:text-amber-400">
                        ⚠️ Write some content in the editor first to use these features.
                    </div>
                </div>

                <!-- Generate Tab -->
                <div v-if="activeTab === 'generate'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            What would you like to create?
                        </label>
                        <Textarea
                            v-model="customPrompt"
                            placeholder="e.g., Write a post about our new product launch..."
                            :rows="3"
                            class="w-full"
                        />
                    </div>

                    <div class="flex gap-2">
                        <PrimaryButton 
                            @click="generate" 
                            :disabled="isLoading || !customPrompt"
                            class="bg-purple-600 hover:bg-purple-700"
                        >
                            ✨ Generate
                        </PrimaryButton>
                        <SecondaryButton @click="getIdeas" :disabled="isLoading">
                            💡 Get Ideas
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                    <span class="ml-3 text-gray-500">AI is thinking...</span>
                </div>

                <!-- Error -->
                <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm">
                    {{ error }}
                </div>

                <!-- Result -->
                <div v-if="result && !isLoading" class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        AI Result:
                    </label>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{{ result }}</p>
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton @click="applyResult" class="bg-green-600 hover:bg-green-700">
                            ✓ Replace Content
                        </PrimaryButton>
                        <SecondaryButton v-if="result.includes('#')" @click="appendHashtags">
                            + Append to Content
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close">Close</SecondaryButton>
        </template>
    </DialogModal>
</template>
