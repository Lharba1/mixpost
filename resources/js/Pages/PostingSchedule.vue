<script setup>
import {ref, computed} from "vue";
import {useForm, router} from "@inertiajs/vue3";
import {Head} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Input from "@/Components/Form/Input.vue";
import Select from "@/Components/Form/Select.vue";
import Label from "@/Components/Form/Label.vue";
import Error from "@/Components/Form/Error.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Trash from "@/Icons/Trash.vue";
import Plus from "@/Icons/Plus.vue";
import Clock from "@/Icons/Clock.vue";
import QueueList from "@/Icons/QueueList.vue";
import ArrowPath from "@/Icons/ArrowPath.vue";

const props = defineProps({
    schedule: {
        type: Object,
        required: true,
    },
    queue_items: {
        type: Array,
        default: () => [],
    },
    days: {
        type: Object,
        required: true,
    },
});

const showAddTimeModal = ref(false);

const timeForm = useForm({
    day_of_week: 1,
    time: '09:00',
});

const openAddTimeModal = () => {
    timeForm.reset();
    showAddTimeModal.value = true;
};

const closeAddTimeModal = () => {
    showAddTimeModal.value = false;
    timeForm.reset();
};

const addTimeSlot = () => {
    timeForm.post(route('mixpost.schedule.addTimeSlot'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddTimeModal();
        },
    });
};

const removeTimeSlot = (timeId) => {
    if (confirm('Remove this time slot?')) {
        router.delete(route('mixpost.schedule.removeTimeSlot', {time: timeId}), {
            preserveScroll: true,
        });
    }
};

const toggleTimeSlot = (timeId) => {
    router.put(route('mixpost.schedule.toggleTimeSlot', {time: timeId}), {}, {
        preserveScroll: true,
    });
};

const removeFromQueue = (queueItemId) => {
    if (confirm('Remove this post from the queue?')) {
        router.delete(route('mixpost.schedule.removeFromQueue', {queueItem: queueItemId}), {
            preserveScroll: true,
        });
    }
};

const retryQueueItem = (queueItemId) => {
    router.put(route('mixpost.schedule.retryQueueItem', {queueItem: queueItemId}), {}, {
        preserveScroll: true,
    });
};

const timesByDay = computed(() => {
    if (!props.schedule.times) return {};
    
    const grouped = {};
    for (const [dayNum, dayName] of Object.entries(props.days)) {
        grouped[dayNum] = props.schedule.times.filter(t => t.day_of_week === parseInt(dayNum));
    }
    return grouped;
});

const statusColor = (status) => {
    const colors = {
        pending: 'info',
        processing: 'warning',
        published: 'success',
        failed: 'danger',
    };
    return colors[status] || 'default';
};
</script>

<template>
    <Head title="Posting Schedule"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Posting Schedule"/>

        <div class="row-px">
            <!-- Weekly Schedule -->
            <Panel>
                <template #title>Weekly Schedule</template>
                <template #description>
                    Set up recurring time slots for automatic post scheduling.
                </template>

                <template #action>
                    <PrimaryButton @click="openAddTimeModal" size="sm">
                        <Plus class="w-4 h-4 mr-1"/>
                        Add Time Slot
                    </PrimaryButton>
                </template>

                <div class="grid grid-cols-7 gap-2">
                    <div 
                        v-for="(dayName, dayNum) in days" 
                        :key="dayNum"
                        class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3"
                    >
                        <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2 text-center">
                            {{ dayName.substring(0, 3) }}
                        </h4>
                        
                        <div class="space-y-1 min-h-[60px]">
                            <div 
                                v-for="time in timesByDay[dayNum]" 
                                :key="time.id"
                                class="flex items-center justify-between p-1.5 bg-white dark:bg-gray-700 rounded text-xs"
                                :class="{ 'opacity-50': !time.is_active }"
                            >
                                <button 
                                    @click="toggleTimeSlot(time.id)"
                                    class="font-medium text-gray-700 dark:text-gray-300 hover:text-primary-500"
                                >
                                    {{ time.formatted_time }}
                                </button>
                                <button 
                                    @click="removeTimeSlot(time.id)"
                                    class="text-gray-400 hover:text-red-500"
                                >
                                    <Trash class="w-3 h-3"/>
                                </button>
                            </div>
                            
                            <div v-if="!timesByDay[dayNum] || timesByDay[dayNum].length === 0" 
                                class="text-xs text-gray-400 text-center py-2">
                                No slots
                            </div>
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Queue -->
            <Panel class="mt-lg">
                <template #title>
                    <Flex class="items-center gap-2">
                        <QueueList class="w-5 h-5"/>
                        Queue
                        <Badge v-if="queue_items.length > 0" variant="info">
                            {{ queue_items.length }}
                        </Badge>
                    </Flex>
                </template>
                <template #description>
                    Posts waiting to be published based on your schedule.
                </template>

                <div v-if="queue_items.length > 0" class="space-y-3">
                    <div 
                        v-for="(item, index) in queue_items" 
                        :key="item.id"
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <div class="text-2xl font-bold text-gray-300 dark:text-gray-600 w-8">
                            {{ index + 1 }}
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ item.post?.versions?.[0]?.content?.[0]?.body?.substring(0, 80) || 'No content' }}...
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Scheduled: {{ item.scheduled_at_formatted || 'Pending assignment' }}
                                <span v-if="item.scheduled_at_relative" class="ml-2 text-gray-400">
                                    ({{ item.scheduled_at_relative }})
                                </span>
                            </p>
                        </div>
                        
                        <Badge :variant="statusColor(item.status)">
                            {{ item.status }}
                        </Badge>
                        
                        <div class="flex gap-2">
                            <button 
                                v-if="item.status === 'failed'"
                                @click="retryQueueItem(item.id)"
                                class="p-1 text-gray-400 hover:text-green-500"
                                title="Retry"
                            >
                                <ArrowPath class="w-5 h-5"/>
                            </button>
                            <button 
                                @click="removeFromQueue(item.id)"
                                class="p-1 text-gray-400 hover:text-red-500"
                                title="Remove"
                            >
                                <Trash class="w-5 h-5"/>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-gray-500">
                    <QueueList class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                    <p>No posts in queue.</p>
                    <p class="text-sm">Add posts to your queue from the post editor.</p>
                </div>
            </Panel>
        </div>
    </div>

    <!-- Add Time Slot Modal -->
    <DialogModal :show="showAddTimeModal" @close="closeAddTimeModal">
        <template #header>
            Add Time Slot
        </template>
        <template #body>
            <div class="space-y-4">
                <div>
                    <Label for="day">Day of Week</Label>
                    <Select id="day" v-model="timeForm.day_of_week">
                        <option v-for="(dayName, dayNum) in days" :key="dayNum" :value="parseInt(dayNum)">
                            {{ dayName }}
                        </option>
                    </Select>
                    <Error :message="timeForm.errors.day_of_week"/>
                </div>
                <div>
                    <Label for="time">Time</Label>
                    <Input id="time" type="time" v-model="timeForm.time"/>
                    <Error :message="timeForm.errors.time"/>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="closeAddTimeModal">Cancel</SecondaryButton>
            <PrimaryButton @click="addTimeSlot" :disabled="timeForm.processing" class="ml-2">
                Add Slot
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
