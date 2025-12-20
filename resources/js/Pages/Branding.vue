<script setup>
import { ref, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Input from "@/Components/Form/Input.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import Alert from "@/Components/Util/Alert.vue";

const props = defineProps({
    branding: Object,
});

const form = reactive({
    app_name: props.branding?.app_name || 'Mixpost',
    primary_color: props.branding?.primary_color || '#6366f1',
    secondary_color: props.branding?.secondary_color || '#8b5cf6',
    footer_text: props.branding?.footer_text || '',
    hide_powered_by: props.branding?.hide_powered_by || false,
    custom_css: props.branding?.custom_css || '',
});

const saving = ref(false);

const save = () => {
    saving.value = true;
    router.put(route('mixpost.branding.update'), form, {
        onFinish: () => saving.value = false,
    });
};

const uploadLogo = (type, event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('logo', file);

    fetch(route(`mixpost.branding.${type}`), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData,
    }).then(() => router.reload());
};

const uploadFavicon = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('favicon', file);

    fetch(route('mixpost.branding.favicon'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData,
    }).then(() => router.reload());
};

const removeImage = (key) => {
    if (confirm('Remove this image?')) {
        router.delete(route('mixpost.branding.removeImage'), {
            data: { key },
        });
    }
};

const resetBranding = () => {
    if (confirm('Reset all branding to defaults?')) {
        router.post(route('mixpost.branding.reset'));
    }
};
</script>

<template>
    <div>
        <PageHeader title="White Label / Branding">
            <template #description>
                Customize the look and feel of your Mixpost instance
            </template>
        </PageHeader>

        <div class="row-py space-y-6">
            <!-- General Settings -->
            <Panel title="General">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Application Name
                        </label>
                        <Input v-model="form.app_name" class="w-full max-w-md" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.hide_powered_by" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Hide "Powered by Mixpost" text
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Footer Text
                        </label>
                        <Input v-model="form.footer_text" placeholder="© 2024 Your Company" class="w-full max-w-md" />
                    </div>
                </div>
            </Panel>

            <!-- Logo & Favicon -->
            <Panel title="Logo & Favicon">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Logo (Light Mode)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                            <img 
                                v-if="branding?.logo_light" 
                                :src="branding.logo_light" 
                                alt="Logo" 
                                class="h-12 mx-auto mb-2"
                            />
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="uploadLogo('logoLight', $event)"
                                class="text-sm"
                            />
                            <button 
                                v-if="branding?.logo_light"
                                @click="removeImage('logo_light')"
                                class="text-xs text-red-500 mt-2 block mx-auto"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Logo (Dark Mode)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center bg-gray-800">
                            <img 
                                v-if="branding?.logo_dark" 
                                :src="branding.logo_dark" 
                                alt="Logo" 
                                class="h-12 mx-auto mb-2"
                            />
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="uploadLogo('logoDark', $event)"
                                class="text-sm text-white"
                            />
                            <button 
                                v-if="branding?.logo_dark"
                                @click="removeImage('logo_dark')"
                                class="text-xs text-red-400 mt-2 block mx-auto"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Favicon
                        </label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                            <img 
                                v-if="branding?.favicon" 
                                :src="branding.favicon" 
                                alt="Favicon" 
                                class="h-8 w-8 mx-auto mb-2"
                            />
                            <input 
                                type="file" 
                                accept=".png,.ico"
                                @change="uploadFavicon($event)"
                                class="text-sm"
                            />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Colors -->
            <Panel title="Colors">
                <div class="flex gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Primary Color
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="color" 
                                v-model="form.primary_color"
                                class="h-10 w-14 rounded cursor-pointer"
                            />
                            <Input v-model="form.primary_color" class="w-28 font-mono text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Secondary Color
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="color" 
                                v-model="form.secondary_color"
                                class="h-10 w-14 rounded cursor-pointer"
                            />
                            <Input v-model="form.secondary_color" class="w-28 font-mono text-sm" />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Custom CSS -->
            <Panel title="Custom CSS">
                <Textarea 
                    v-model="form.custom_css" 
                    rows="6"
                    placeholder="/* Add your custom CSS here */"
                    class="w-full font-mono text-sm"
                />
                <p class="text-xs text-gray-500 mt-2">
                    Add custom CSS to further customize the appearance.
                </p>
            </Panel>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <DangerButton @click="resetBranding">
                    Reset to Defaults
                </DangerButton>
                <PrimaryButton @click="save" :disabled="saving">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
