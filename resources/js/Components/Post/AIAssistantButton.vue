<script setup>
import {ref, computed} from "vue";
import Dropdown from "../Dropdown/Dropdown.vue";
import DropdownItem from "../Dropdown/DropdownItem.vue";
import Sparkles from "../../Icons/Sparkles.vue";

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
    platform: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['generated', 'loading']);

const loading = ref(false);
const error = ref(null);
const showPromptInput = ref(false);
const customPrompt = ref('');

const tones = [
    { value: 'professional', label: '💼 Professional' },
    { value: 'casual', label: '😊 Casual' },
    { value: 'friendly', label: '🙌 Friendly' },
    { value: 'humorous', label: '😄 Humorous' },
    { value: 'inspirational', label: '✨ Inspirational' },
];

const callAI = async (endpoint, data) => {
    loading.value = true;
    error.value = null;
    emit('loading', true);

    try {
        const response = await fetch(route(`mixpost.ai.${endpoint}`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (result.success) {
            emit('generated', result.content);
        } else {
            error.value = result.error || 'Failed to generate content';
        }
    } catch (e) {
        error.value = 'Network error. Please try again.';
    } finally {
        loading.value = false;
        emit('loading', false);
    }
};

const generateFromPrompt = () => {
    if (customPrompt.value.trim()) {
        callAI('generate', { prompt: customPrompt.value });
        showPromptInput.value = false;
        customPrompt.value = '';
    }
};

const rewrite = (tone) => {
    if (props.content) {
        callAI('rewrite', { content: props.content, tone });
    }
};

const summarize = () => {
    if (props.content && props.content.length > 50) {
        callAI('summarize', { content: props.content });
    }
};

const generateHashtags = async () => {
    if (props.content) {
        loading.value = true;
        emit('loading', true);

        try {
            const response = await fetch(route('mixpost.ai.hashtags'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ content: props.content }),
            });

            const result = await response.json();

            if (result.success && result.hashtags) {
                emit('generated', props.content + '\n\n' + result.hashtags.join(' '));
            }
        } finally {
            loading.value = false;
            emit('loading', false);
        }
    }
};

const optimizeForPlatform = () => {
    if (props.content && props.platform) {
        callAI('optimize', { content: props.content, platform: props.platform });
    }
};

const hasContent = computed(() => props.content && props.content.length > 10);
</script>

<template>
    <Dropdown width-class="w-64" placement="bottom-start">
        <template #trigger>
            <button 
                type="button" 
                class="p-1.5 rounded transition-colors"
                :class="[
                    loading ? 'animate-pulse' : '',
                    disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
                :disabled="disabled || loading"
                title="AI Assistant"
            >
                <Sparkles class="w-5 h-5" :class="loading ? 'text-primary-500' : 'text-gray-500'"/>
            </button>
        </template>

        <template #content>
            <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <Sparkles class="w-4 h-4"/>
                AI Assistant
            </div>

            <!-- Generate New Content -->
            <div v-if="showPromptInput" class="p-3 border-b border-gray-200 dark:border-gray-700">
                <input 
                    v-model="customPrompt"
                    type="text"
                    placeholder="Describe what you want..."
                    class="w-full px-2 py-1 text-sm border rounded dark:bg-gray-700 dark:border-gray-600"
                    @keyup.enter="generateFromPrompt"
                />
                <div class="flex gap-2 mt-2">
                    <button 
                        @click="generateFromPrompt"
                        class="flex-1 px-2 py-1 text-xs bg-primary-500 text-white rounded hover:bg-primary-600"
                    >
                        Generate
                    </button>
                    <button 
                        @click="showPromptInput = false"
                        class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <DropdownItem v-else @click="showPromptInput = true">
                ✨ Generate from prompt...
            </DropdownItem>

            <div v-if="hasContent" class="border-t border-gray-200 dark:border-gray-700">
                <!-- Rewrite Options -->
                <div class="px-3 py-1 text-xs text-gray-400">Rewrite as...</div>
                <DropdownItem 
                    v-for="tone in tones" 
                    :key="tone.value"
                    @click="rewrite(tone.value)"
                    class="pl-6"
                >
                    {{ tone.label }}
                </DropdownItem>

                <div class="border-t border-gray-200 dark:border-gray-700">
                    <DropdownItem @click="summarize" v-if="content.length > 50">
                        📝 Summarize
                    </DropdownItem>
                    <DropdownItem @click="generateHashtags">
                        #️⃣ Generate Hashtags
                    </DropdownItem>
                    <DropdownItem v-if="platform" @click="optimizeForPlatform">
                        📱 Optimize for {{ platform }}
                    </DropdownItem>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="px-3 py-2 text-xs text-red-500 border-t border-gray-200">
                {{ error }}
            </div>
        </template>
    </Dropdown>
</template>
