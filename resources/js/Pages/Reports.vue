<script setup>
import {ref, computed} from "vue";
import {Head, router} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import Account from "@/Components/Account/Account.vue";
import ChartBar from "@/Icons/ChartBar.vue";
import ArrowPath from "@/Icons/ArrowPath.vue";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    account_stats: {
        type: Array,
        default: () => [],
    },
    posting_activity: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(route('mixpost.reports'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
    });
};

const exportReport = () => {
    window.location.href = route('mixpost.reports.export', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const maxActivityCount = computed(() => {
    if (!props.posting_activity.length) return 1;
    return Math.max(...props.posting_activity.map(item => item.count));
});
</script>

<template>
    <Head title="Reports"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Reports">
            <template #description>
                Analyze your social media performance with detailed statistics.
            </template>
        </PageHeader>

        <div class="row-px">
            <!-- Filters -->
            <Panel class="mb-6">
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <Label for="startDate">Start Date</Label>
                        <Input
                            id="startDate"
                            v-model="startDate"
                            type="date"
                            class="w-40"
                        />
                    </div>
                    <div>
                        <Label for="endDate">End Date</Label>
                        <Input
                            id="endDate"
                            v-model="endDate"
                            type="date"
                            class="w-40"
                        />
                    </div>
                    <SecondaryButton @click="applyFilters">
                        <ArrowPath class="w-4 h-4 mr-1"/>
                        Apply Filters
                    </SecondaryButton>
                    <PrimaryButton @click="exportReport">
                        Export CSV
                    </PrimaryButton>
                </div>
            </Panel>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Posts</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_posts || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Published</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.published || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Scheduled</p>
                    <p class="text-2xl font-bold text-blue-600">{{ stats.scheduled || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Drafts</p>
                    <p class="text-2xl font-bold text-gray-600">{{ stats.draft || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed || 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Posting Activity Chart -->
                <Panel>
                    <template #title>Posting Activity</template>
                    <template #description>Daily post publishing trends</template>

                    <div v-if="posting_activity.length > 0" class="space-y-2">
                        <div
                            v-for="item in posting_activity"
                            :key="item.date"
                            class="flex items-center gap-2"
                        >
                            <span class="text-xs text-gray-500 w-20">{{ item.date }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div
                                    class="bg-indigo-500 h-4 rounded-full"
                                    :style="{ width: `${(item.count / maxActivityCount) * 100}%` }"
                                ></div>
                            </div>
                            <span class="text-xs font-medium w-8 text-right">{{ item.count }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        <ChartBar class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                        <p>No activity in this period.</p>
                    </div>
                </Panel>

                <!-- Account Stats -->
                <Panel>
                    <template #title>Posts by Account</template>
                    <template #description>Breakdown of posts per connected account</template>

                    <div v-if="account_stats.length > 0" class="space-y-3">
                        <div
                            v-for="account in account_stats"
                            :key="account.id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <Account
                                    :provider="account.provider"
                                    :name="account.name"
                                    :img-url="account.image"
                                    size="sm"
                                />
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ account.name }}</span>
                            </div>
                            <Badge variant="default">{{ account.post_count }} posts</Badge>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        <p>No accounts found.</p>
                    </div>
                </Panel>
            </div>
        </div>
    </div>
</template>
