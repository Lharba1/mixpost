<script setup>
import {ref, watch} from "vue";
import {startsWith} from "lodash";
import Draggable from 'vuedraggable'
import usePost from "@/Composables/usePost";
import DialogModal from "@/Components/Modal/DialogModal.vue"
import MediaFile from "@/Components/Media/MediaFile.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";

const props = defineProps({
    media: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['updateMedia']);

const {editAllowed} = usePost();

const items = ref([]);
const showView = ref(false);
const openedItem = ref({});
const altText = ref('');

const isVideo = (mime_type) => {
    return startsWith(mime_type, 'video')
}

const isImage = (mime_type) => {
    return startsWith(mime_type, 'image')
}

const open = (item) => {
    openedItem.value = item;
    altText.value = item.alt_text || '';
    showView.value = true;
}

const close = () => {
    showView.value = false;
    openedItem.value = {};
    altText.value = '';
}

const remove = (id) => {
    const index = props.media.findIndex(item => item.id === id);
    props.media.splice(index, 1);
    close();
}

const saveAltText = () => {
    const index = props.media.findIndex(item => item.id === openedItem.value.id);
    if (index !== -1) {
        props.media[index].alt_text = altText.value;
    }
    close();
}
</script>
<template>
    <div class="mt-lg">
        <Draggable
            :list="media"
            :disabled="!editAllowed"
            v-bind="{
                animation: 200,
                group: 'media',
            }"
            item-key="id"
            class="flex flex-wrap gap-xs"
        >
            <template #item="{element}">
                <div role="button" class="cursor-pointer" @click="open(element)">
                    <MediaFile :media="element" img-height="sm" :imgWidthFull="false" :showCaption="false"/>
                </div>
            </template>
        </Draggable>
    </div>

    <DialogModal :show="showView" @close="close" maxWidth="lg">
        <template #header>
            {{ isImage(openedItem.mime_type) ? 'Edit Image' : 'View Media' }}
        </template>

        <template #body>
            <figcaption class="mb-xs text-sm font-medium text-gray-700 dark:text-gray-300">{{ openedItem.name }}</figcaption>

            <video v-if="isVideo(openedItem.mime_type)" class="w-auto h-full max-h-64 rounded-lg" controls>
                <source :src="openedItem.url" :type="openedItem.mime_type">
                Your browser does not support the video tag.
            </video>

            <img v-else :src="openedItem.url" :alt="altText || 'Image'" class="max-h-64 rounded-lg"/>

            <!-- Alt Text Input for Images -->
            <div v-if="isImage(openedItem.mime_type) && editAllowed" class="mt-4">
                <Label for="altText" class="block mb-1">Alt Text (Accessibility)</Label>
                <Input
                    id="altText"
                    v-model="altText"
                    type="text"
                    class="w-full"
                    placeholder="Describe this image for screen readers..."
                    maxlength="500"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Alt text helps visually impaired users understand your images.
                </p>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close" class="mr-xs">Cancel</SecondaryButton>
            <DangerButton v-if="editAllowed" @click="remove(openedItem.id)" class="mr-xs">Remove</DangerButton>
            <PrimaryButton v-if="editAllowed && isImage(openedItem.mime_type)" @click="saveAltText">Save</PrimaryButton>
        </template>
    </DialogModal>
</template>

