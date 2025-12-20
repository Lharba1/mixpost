<script setup>
import { ref, reactive, computed } from "vue";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Input from "@/Components/Form/Input.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import NoResult from "@/Components/Util/NoResult.vue";

const props = defineProps({
    languages: Array,
});

const showAddModal = ref(false);
const editingLanguage = ref(null);

const form = reactive({
    code: '',
    name: '',
    native_name: '',
    is_active: true,
    is_rtl: false,
});

const commonLanguages = [
    { code: 'en', name: 'English', native_name: 'English' },
    { code: 'es', name: 'Spanish', native_name: 'Español' },
    { code: 'fr', name: 'French', native_name: 'Français' },
    { code: 'de', name: 'German', native_name: 'Deutsch' },
    { code: 'it', name: 'Italian', native_name: 'Italiano' },
    { code: 'pt', name: 'Portuguese', native_name: 'Português' },
    { code: 'nl', name: 'Dutch', native_name: 'Nederlands' },
    { code: 'ru', name: 'Russian', native_name: 'Русский' },
    { code: 'zh', name: 'Chinese', native_name: '中文' },
    { code: 'ja', name: 'Japanese', native_name: '日本語' },
    { code: 'ko', name: 'Korean', native_name: '한국어' },
    { code: 'ar', name: 'Arabic', native_name: 'العربية', rtl: true },
    { code: 'hi', name: 'Hindi', native_name: 'हिन्दी' },
    { code: 'tr', name: 'Turkish', native_name: 'Türkçe' },
    { code: 'pl', name: 'Polish', native_name: 'Polski' },
];

const resetForm = () => {
    form.code = '';
    form.name = '';
    form.native_name = '';
    form.is_active = true;
    form.is_rtl = false;
    editingLanguage.value = null;
};

const openAdd = () => {
    resetForm();
    showAddModal.value = true;
};

const selectCommonLanguage = (lang) => {
    form.code = lang.code;
    form.name = lang.name;
    form.native_name = lang.native_name;
    form.is_rtl = lang.rtl || false;
};

const openEdit = (language) => {
    editingLanguage.value = language;
    form.code = language.code;
    form.name = language.name;
    form.native_name = language.native_name;
    form.is_active = language.is_active;
    form.is_rtl = language.is_rtl;
    showAddModal.value = true;
};

const save = () => {
    if (editingLanguage.value) {
        router.put(route('mixpost.translations.languages.update', editingLanguage.value.id), form, {
            onSuccess: () => {
                showAddModal.value = false;
                resetForm();
            },
        });
    } else {
        router.post(route('mixpost.translations.languages.store'), form, {
            onSuccess: () => {
                showAddModal.value = false;
                resetForm();
            },
        });
    }
};

const toggleActive = (language) => {
    router.put(route('mixpost.translations.languages.update', language.id), {
        ...language,
        is_active: !language.is_active,
    });
};

const deleteLanguage = (language) => {
    if (confirm(`Delete ${language.name}?`)) {
        router.delete(route('mixpost.translations.languages.destroy', language.id));
    }
};

const setDefault = (language) => {
    router.post(route('mixpost.translations.languages.setDefault', language.id));
};

const activeLanguages = computed(() => {
    return (props.languages || []).filter(l => l.is_active);
});

const inactiveLanguages = computed(() => {
    return (props.languages || []).filter(l => !l.is_active);
});
</script>

<template>
    <div>
        <PageHeader title="Languages & Translations">
            <template #description>
                Manage available languages for multilingual post support
            </template>
        </PageHeader>

        <div class="row-py space-y-6">
            <!-- Active Languages -->
            <Panel title="Active Languages">
                <template #action>
                    <PrimaryButton size="sm" @click="openAdd">
                        Add Language
                    </PrimaryButton>
                </template>

                <template v-if="activeLanguages.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="language in activeLanguages" 
                            :key="language.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                    {{ language.code.toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ language.name }}
                                        </span>
                                        <Badge v-if="language.is_default" variant="success" size="sm">
                                            Default
                                        </Badge>
                                        <Badge v-if="language.is_rtl" variant="info" size="sm">
                                            RTL
                                        </Badge>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ language.native_name }} · {{ language.code }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <SecondaryButton 
                                    v-if="!language.is_default"
                                    size="xs" 
                                    @click="setDefault(language)"
                                >
                                    Set Default
                                </SecondaryButton>
                                <SecondaryButton size="xs" @click="openEdit(language)">
                                    Edit
                                </SecondaryButton>
                                <SecondaryButton size="xs" @click="toggleActive(language)">
                                    Disable
                                </SecondaryButton>
                                <DangerButton 
                                    v-if="!language.is_default"
                                    size="xs" 
                                    @click="deleteLanguage(language)"
                                >
                                    Delete
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </template>
                <NoResult v-else>
                    No active languages. Add one to get started.
                </NoResult>
            </Panel>

            <!-- Inactive Languages -->
            <Panel v-if="inactiveLanguages.length > 0" title="Inactive Languages">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div 
                        v-for="language in inactiveLanguages" 
                        :key="language.id"
                        class="py-3 flex items-center justify-between opacity-60"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-400 font-bold text-sm">
                                {{ language.code.toUpperCase() }}
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ language.name }}
                                </span>
                                <span class="text-sm text-gray-500 ml-2">
                                    {{ language.native_name }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <SecondaryButton size="xs" @click="toggleActive(language)">
                                Enable
                            </SecondaryButton>
                            <DangerButton size="xs" @click="deleteLanguage(language)">
                                Delete
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </Panel>
        </div>

        <!-- Add/Edit Modal -->
        <DialogModal :show="showAddModal" @close="showAddModal = false">
            <template #title>
                {{ editingLanguage ? 'Edit Language' : 'Add Language' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Quick Select -->
                    <div v-if="!editingLanguage">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Quick Select
                        </label>
                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="lang in commonLanguages"
                                :key="lang.code"
                                type="button"
                                class="px-2 py-1 text-xs rounded border transition-colors"
                                :class="form.code === lang.code 
                                    ? 'bg-indigo-100 border-indigo-500 text-indigo-700' 
                                    : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-indigo-300'"
                                @click="selectCommonLanguage(lang)"
                            >
                                {{ lang.code.toUpperCase() }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Language Code
                            </label>
                            <Input 
                                v-model="form.code" 
                                placeholder="en" 
                                class="w-full" 
                                :disabled="!!editingLanguage"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name
                            </label>
                            <Input v-model="form.name" placeholder="English" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Native Name
                        </label>
                        <Input v-model="form.native_name" placeholder="English" class="w-full" />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_active" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_rtl" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Right-to-Left (RTL)</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                <PrimaryButton @click="save" class="ml-2">
                    {{ editingLanguage ? 'Update' : 'Add Language' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
