<script setup>
import {ref, onMounted} from "vue";
import Dropdown from "../Dropdown/Dropdown.vue";
import DropdownItem from "../Dropdown/DropdownItem.vue";
import CommandLine from "../../Icons/CommandLine.vue";
import Badge from "../DataDisplay/Badge.vue";

const props = defineProps({
    variables: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['insert']);

const localVariables = ref([]);
const loading = ref(false);

const fetchVariables = async () => {
    if (props.variables.length > 0) {
        localVariables.value = props.variables;
        return;
    }

    loading.value = true;
    try {
        const response = await fetch(route('mixpost.variables.all'));
        const data = await response.json();
        localVariables.value = data.variables || [];
    } catch (error) {
        console.error('Failed to fetch variables:', error);
    } finally {
        loading.value = false;
    }
};

const insertVariable = (variable) => {
    emit('insert', `{${variable.key}}`);
};

const systemVariables = () => {
    return localVariables.value.filter(v => v.system === true);
};

const customVariables = () => {
    return localVariables.value.filter(v => v.system !== true);
};

onMounted(() => {
    fetchVariables();
});
</script>

<template>
    <Dropdown width-class="w-64" placement="bottom-start">
        <template #trigger>
            <button 
                type="button" 
                class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Insert Variable"
            >
                <CommandLine class="w-5 h-5 text-gray-500"/>
            </button>
        </template>

        <template #content>
            <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                Insert Variable
            </div>

            <div v-if="loading" class="px-3 py-4 text-center text-gray-500">
                Loading...
            </div>

            <div v-else class="max-h-64 overflow-y-auto">
                <!-- System Variables -->
                <div v-if="systemVariables().length > 0">
                    <div class="px-3 py-1.5 text-xs text-gray-400 bg-gray-50 dark:bg-gray-800">
                        System
                    </div>
                    <DropdownItem 
                        v-for="variable in systemVariables()" 
                        :key="variable.key"
                        @click="insertVariable(variable)"
                        class="flex items-center justify-between"
                    >
                        <span>{{ variable.name }}</span>
                        <Badge variant="info" size="sm">{{ '{' + variable.key + '}' }}</Badge>
                    </DropdownItem>
                </div>

                <!-- Custom Variables -->
                <div v-if="customVariables().length > 0">
                    <div class="px-3 py-1.5 text-xs text-gray-400 bg-gray-50 dark:bg-gray-800">
                        Custom
                    </div>
                    <DropdownItem 
                        v-for="variable in customVariables()" 
                        :key="variable.key"
                        @click="insertVariable(variable)"
                        class="flex items-center justify-between"
                    >
                        <span>{{ variable.name }}</span>
                        <Badge variant="info" size="sm">{{ '{' + variable.key + '}' }}</Badge>
                    </DropdownItem>
                </div>

                <!-- Empty State -->
                <div v-if="localVariables.length === 0" class="px-3 py-4 text-center text-gray-500 text-sm">
                    No variables available.
                    <a :href="route('mixpost.variables.index')" class="text-primary-500 hover:underline block mt-1">
                        Create one
                    </a>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
