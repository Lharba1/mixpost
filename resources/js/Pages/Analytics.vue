<script setup>
import {ref, computed} from "vue";
import {router} from "@inertiajs/vue3";
import {Head} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Select from "@/Components/Form/Select.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import ChartBar from "@/Icons/ChartBar.vue";
import ArrowPath from "@/Icons/ArrowPath.vue";

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const accountId = ref(props.filters.account_id || '');

const applyFilters = () => {
    router.get(route('mixpost.analytics.index'), {
        from: from.value,
        to: to.value,
        account_id: accountId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    from.value = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    to.value = new Date().toISOString().split('T')[0];
    accountId.value = '';
    applyFilters();
};

const maxPostsPerDay = computed(() => {
    return Math.max(...props.analytics.posts_per_day.map(d => d.count), 1);
});

const maxByHour = computed(() => {
    return Math.max(...props.analytics.posts_by_hour.map(d => d.count), 1);
});
</script>

<template>
    <Head title="Analytics"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Analytics"/>

        <div class="row-px">
            <!-- Filters -->
            <Panel>
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <Label>From</Label>
                        <Input type="date" v-model="from" class="w-40"/>
                    </div>
                    <div>
                        <Label>To</Label>
                        <Input type="date" v-model="to" class="w-40"/>
                    </div>
                    <div>
                        <Label>Account</Label>
                        <Select v-model="accountId" class="w-48">
                            <option value="">All Accounts</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </Select>
                    </div>
                    <PrimaryButton @click="applyFilters">
                        Apply
                    </PrimaryButton>
                    <SecondaryButton @click="resetFilters">
                        <ArrowPath class="w-4 h-4"/>
                    </SecondaryButton>
                </div>
            </Panel>

            <!-- Overview Stats -->
            <div class="grid grid-cols-4 gap-4 mt-lg">
                <Panel class="text-center">
                    <div class="text-4xl font-bold text-primary-500">
                        {{ analytics.overview.total_posts }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Published Posts</div>
                </Panel>
                <Panel class="text-center">
                    <div class="text-4xl font-bold text-blue-500">
                        {{ analytics.overview.scheduled_posts }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Scheduled</div>
                </Panel>
                <Panel class="text-center">
                    <div class="text-4xl font-bold text-red-500">
                        {{ analytics.overview.failed_posts }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Failed</div>
                </Panel>
                <Panel class="text-center">
                    <div class="text-4xl font-bold text-green-500">
                        {{ analytics.engagement?.followers || 0 }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Total Followers</div>
                </Panel>
            </div>

            <!-- Posts Per Day Chart -->
            <Panel class="mt-lg">
                <template #title>Posts Over Time</template>
                
                <div class="h-48 flex items-end gap-1">
                    <div 
                        v-for="(day, index) in analytics.posts_per_day" 
                        :key="index"
                        class="flex-1 bg-primary-500 rounded-t transition-all hover:bg-primary-600 relative group"
                        :style="{ height: `${(day.count / maxPostsPerDay) * 100}%`, minHeight: day.count > 0 ? '4px' : '0' }"
                    >
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                            {{ day.label }}: {{ day.count }} posts
                        </div>
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-2">
                    <span>{{ analytics.posts_per_day[0]?.label }}</span>
                    <span>{{ analytics.posts_per_day[analytics.posts_per_day.length - 1]?.label }}</span>
                </div>
            </Panel>

            <!-- Posts By Hour and Day of Week -->
            <div class="grid grid-cols-2 gap-4 mt-lg">
                <Panel>
                    <template #title>Posts by Hour</template>
                    
                    <div class="h-32 flex items-end gap-0.5">
                        <div 
                            v-for="hour in analytics.posts_by_hour" 
                            :key="hour.hour"
                            class="flex-1 bg-blue-500 rounded-t transition-all hover:bg-blue-600 relative group"
                            :style="{ height: `${(hour.count / maxByHour) * 100}%`, minHeight: hour.count > 0 ? '2px' : '0' }"
                        >
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                {{ hour.label }}: {{ hour.count }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-2">
                        <span>12 AM</span>
                        <span>6 AM</span>
                        <span>12 PM</span>
                        <span>6 PM</span>
                        <span>12 AM</span>
                    </div>
                </Panel>

                <Panel>
                    <template #title>Posts by Day of Week</template>
                    
                    <div class="space-y-2">
                        <div 
                            v-for="day in analytics.posts_by_day_of_week" 
                            :key="day.day"
                            class="flex items-center gap-3"
                        >
                            <span class="w-10 text-sm text-gray-500">{{ day.label }}</span>
                            <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden">
                                <div 
                                    class="h-full bg-green-500 rounded"
                                    :style="{ width: `${(day.count / Math.max(...analytics.posts_by_day_of_week.map(d => d.count), 1)) * 100}%` }"
                                ></div>
                            </div>
                            <span class="w-8 text-sm text-gray-600 dark:text-gray-400 text-right">
                                {{ day.count }}
                            </span>
                        </div>
                    </div>
                </Panel>
            </div>

            <!-- Posts by Account -->
            <Panel class="mt-lg">
                <template #title>Posts by Account</template>
                
                <div v-if="analytics.posts_by_account.length > 0" class="space-y-3">
                    <div 
                        v-for="account in analytics.posts_by_account" 
                        :key="account.id"
                        class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ account.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ account.provider }}
                            </div>
                        </div>
                        <Badge variant="info">
                            {{ account.posts_count }} posts
                        </Badge>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    No accounts found.
                </div>
            </Panel>

            <!-- Top Posts -->
            <Panel class="mt-lg mb-lg">
                <template #title>Recent Posts</template>
                
                <div v-if="analytics.top_posts.length > 0" class="space-y-3">
                    <div 
                        v-for="post in analytics.top_posts" 
                        :key="post.id"
                        class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            {{ post.preview }}...
                        </p>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span>{{ post.scheduled_at }}</span>
                            <span>{{ post.accounts.join(', ') }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    No posts in this period.
                </div>
            </Panel>
        </div>
    </div>
</template>
