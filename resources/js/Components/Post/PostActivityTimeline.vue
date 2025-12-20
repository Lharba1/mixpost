<script setup>
import {ref, onMounted, computed} from "vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Flex from "@/Components/Layout/Flex.vue";

const props = defineProps({
    postId: {
        type: [Number, String],
        required: true,
    },
    activities: {
        type: Array,
        default: () => [],
    },
    loadRemote: {
        type: Boolean,
        default: false,
    },
});

const loading = ref(false);
const activityList = ref(props.activities);

onMounted(async () => {
    if (props.loadRemote && props.postId) {
        await loadActivities();
    }
});

const loadActivities = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('mixpost.activity.post', {post: props.postId}), {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        activityList.value = data.data || [];
    } catch (error) {
        console.error('Failed to load activities:', error);
    } finally {
        loading.value = false;
    }
};

const getActionColor = (action) => {
    const colors = {
        created: 'blue',
        updated: 'yellow',
        scheduled: 'purple',
        published: 'green',
        failed: 'red',
        restored: 'blue',
        deleted: 'red',
        approved: 'green',
        rejected: 'red',
    };
    return colors[action] || 'gray';
};

const getActionBadgeVariant = (action) => {
    const variants = {
        created: 'info',
        updated: 'warning',
        scheduled: 'info',
        published: 'success',
        failed: 'danger',
        restored: 'info',
        deleted: 'danger',
        approved: 'success',
        rejected: 'danger',
    };
    return variants[action] || 'default';
};
</script>

<template>
    <div class="post-activity-timeline">
        <div v-if="loading" class="text-center py-8 text-gray-500">
            <div class="animate-spin w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-2"></div>
            Loading activity...
        </div>

        <div v-else-if="activityList.length === 0" class="text-center py-8 text-gray-500">
            <p>No activity recorded yet.</p>
        </div>

        <div v-else class="relative">
            <!-- Timeline line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <!-- Activity items -->
            <div 
                v-for="(activity, index) in activityList" 
                :key="activity.id"
                class="relative pl-10 pb-6 last:pb-0"
            >
                <!-- Timeline dot -->
                <div 
                    class="absolute left-2.5 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800"
                    :class="{
                        'bg-blue-500': getActionColor(activity.action) === 'blue',
                        'bg-yellow-500': getActionColor(activity.action) === 'yellow',
                        'bg-green-500': getActionColor(activity.action) === 'green',
                        'bg-red-500': getActionColor(activity.action) === 'red',
                        'bg-purple-500': getActionColor(activity.action) === 'purple',
                        'bg-gray-400': getActionColor(activity.action) === 'gray',
                    }"
                ></div>

                <!-- Activity content -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-100 dark:border-gray-700">
                    <Flex class="items-start justify-between mb-1">
                        <Flex class="items-center gap-2">
                            <Badge :variant="getActionBadgeVariant(activity.action)" size="sm">
                                {{ activity.action_label }}
                            </Badge>
                            <span v-if="activity.user" class="text-sm text-gray-600 dark:text-gray-400">
                                by {{ activity.user.name }}
                            </span>
                        </Flex>
                        <span class="text-xs text-gray-400">
                            {{ activity.time_ago }}
                        </span>
                    </Flex>

                    <p v-if="activity.description" class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                        {{ activity.description }}
                    </p>

                    <!-- Show changes if available -->
                    <div v-if="activity.changes" class="mt-2 text-xs">
                        <details class="group">
                            <summary class="cursor-pointer text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                View changes
                            </summary>
                            <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300 overflow-x-auto">
                                <pre>{{ JSON.stringify(activity.changes, null, 2) }}</pre>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
