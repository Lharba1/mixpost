<script setup>
import {ref} from "vue";
import {Head, router} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Trash from "@/Icons/Trash.vue";
import ArrowPath from "@/Icons/ArrowPath.vue";
import Account from "@/Components/Account/Account.vue";

const props = defineProps({
    recycling_posts: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (dateString) => {
    if (!dateString) return 'Not scheduled';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const toggleActive = (recyclingPost) => {
    router.put(route('mixpost.recycling.toggle', {recyclingPost: recyclingPost.uuid}), {}, {
        preserveScroll: true,
    });
};

const removeFromRecycling = (recyclingPost) => {
    if (confirm('Stop recycling this post?')) {
        router.delete(route('mixpost.recycling.destroy', {recyclingPost: recyclingPost.uuid}), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Evergreen Content"/>

    <div class="row-py mb-2xl w-full mx-auto">
        <PageHeader title="Evergreen Content">
            <template #description>
                Manage posts that automatically recycle on a schedule.
            </template>
        </PageHeader>

        <div class="row-px">
            <Panel>
                <template #title>Recycling Posts</template>
                <template #description>
                    Posts configured to automatically republish on a recurring schedule.
                </template>

                <div v-if="recycling_posts.length > 0" class="space-y-4">
                    <div
                        v-for="item in recycling_posts"
                        :key="item.uuid"
                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        :class="{ 'opacity-60': !item.is_active }"
                    >
                        <div class="flex-1 min-w-0">
                            <!-- Post excerpt -->
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate mb-2">
                                {{ item.post?.excerpt || 'No content' }}
                            </p>

                            <!-- Accounts -->
                            <div class="flex items-center gap-1 mb-2">
                                <template v-for="account in item.post?.accounts" :key="account.id">
                                    <Account
                                        :provider="account.provider"
                                        :name="account.name"
                                        :img-url="account.image"
                                        size="sm"
                                        v-tooltip="account.name"
                                    />
                                </template>
                            </div>

                            <!-- Schedule info -->
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <ArrowPath class="w-3 h-3"/>
                                    {{ item.interval_description }}
                                </span>
                                <span>
                                    Recycled {{ item.recycle_count }} times
                                    <template v-if="item.max_recycles">
                                        / {{ item.max_recycles }} max
                                    </template>
                                </span>
                                <span v-if="item.next_recycle_at">
                                    Next: {{ formatDate(item.next_recycle_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ml-4">
                            <Badge :variant="item.is_active ? 'success' : 'default'">
                                {{ item.is_active ? 'Active' : 'Paused' }}
                            </Badge>
                            <SecondaryButton size="sm" @click="toggleActive(item)">
                                {{ item.is_active ? 'Pause' : 'Resume' }}
                            </SecondaryButton>
                            <button
                                @click="removeFromRecycling(item)"
                                class="p-2 text-gray-400 hover:text-red-500"
                            >
                                <Trash class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-gray-500">
                    <ArrowPath class="w-12 h-12 mx-auto mb-4 text-gray-400"/>
                    <p>No evergreen content yet.</p>
                    <p class="text-sm">Add posts to recycling from the post editor.</p>
                </div>
            </Panel>
        </div>
    </div>
</template>
