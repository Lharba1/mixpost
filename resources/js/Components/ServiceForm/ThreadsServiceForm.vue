<script setup>
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import Panel from "@/Components/Surface/Panel.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ReadDocHelp from "@/Components/Util/ReadDocHelp.vue";

const props = defineProps({
    form: {
        required: true,
        type: Object
    }
})

const internalForm = useForm({
    client_id: props.form.client_id,
    client_secret: props.form.client_secret,
    active: Boolean(props.form.active),
});

const save = () => {
    internalForm.put(route('mixpost.services.update', {service: 'threads'}));
}
</script>
<template>
    <Panel>
        <template #title>
            <div class="flex items-center gap-xs">
                <span class="text-xl">🧵</span>
                Threads
            </div>
        </template>
        <template #description>
            <a href="https://developers.facebook.com/" target="_blank" class="link">Create an App on Meta.</a>
            <p class="text-sm text-gray-500 mt-xs">Uses Meta Graph API. Enable Threads permissions in your Meta app.</p>
            <ReadDocHelp :href="`https://docs.mixpost.app`" class="mt-xs"/>
        </template>

        <div class="mt-lg flex flex-col gap-lg">
            <div>
                <Label for="client_id" required>App ID</Label>
                <Input v-model="internalForm.client_id" type="text" id="client_id" class="w-full"/>
            </div>

            <div>
                <Label for="client_secret" required>App Secret</Label>
                <Input v-model="internalForm.client_secret" :type="'password'" id="client_secret" class="w-full"/>
            </div>

            <div>
                <Label>Status</Label>
                <Checkbox v-model:checked="internalForm.active" id="status">Active</Checkbox>
            </div>
        </div>

        <PrimaryButton @click="save" :disabled="internalForm.processing" class="mt-lg">Save</PrimaryButton>
    </Panel>
</template>
