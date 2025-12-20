<script setup>
import {Link} from '@inertiajs/vue3';
import Logo from "@/Components/DataDisplay/Logo.vue"
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import DarkButtonLink from "@/Components/Button/DarkButtonLink.vue"
import PlusIcon from "@/Icons/Plus.vue"
import GridIcon from "@/Icons/Grid.vue"
import CalendarIcon from "@/Icons/Calendar.vue"
import PhotoIcon from "@/Icons/Photo.vue"
import ShareIcon from "@/Icons/Share.vue"
import CogIcon from "@/Icons/Cog.vue"
import ServerStackIcon from "@/Icons/ServerStack.vue"
import UserMenu from "../Navigation/UserMenu.vue";
import QueueList from "../../Icons/QueueList.vue";
import InformationCircle from "../../Icons/InformationCircle.vue";
import Document from "../../Icons/Document.vue";
import ProLabel from "../Pro/ProLabel.vue";
import UpgradePro from "../Pro/UpgradePro.vue";
// New icons for Pro Features
import Hashtag from "../../Icons/Hashtag.vue";
import ChartBar from "../../Icons/ChartBar.vue";
</script>
<template>
    <div class="w-full h-full flex flex-col py-2xl bg-white border-r border-gray-200">
        <div class="relative mb-12 px-xl">
            <Link :href="route('mixpost.dashboard')">
                <Logo class="h-12"/>
            </Link>
        </div>

        <div class="flex px-xl">
            <DarkButtonLink :href="route('mixpost.posts.create')" class="w-full">
                <PlusIcon class="mr-xs"/>
                Create post
            </DarkButtonLink>
        </div>

        <div class="flex flex-col space-y-lg overflow-y-auto px-xl mt-2xl h-full">
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.dashboard')" :active="$page.component === 'Dashboard'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </template>
                    Dashboard
                </MenuItem>
            </MenuGroupBody>
            <MenuDelimiter/>
            <MenuGroupHeader :create-url="route('mixpost.posts.create')" class="mt-lg">
                Content
                <template #icon>
                    <PlusIcon/>
                </template>
            </MenuGroupHeader>
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.posts.index')"
                          :active="$page.component === 'Posts/Index'">
                    <template #icon>
                        <GridIcon/>
                    </template>
                    Posts
                </MenuItem>
                <MenuItem :url="route('mixpost.calendar')" :active="$page.component === 'Calendar'">
                    <template #icon>
                        <CalendarIcon/>
                    </template>
                    Calendar
                </MenuItem>
                <MenuItem :url="route('mixpost.media.index')" :active="$page.component === 'Media'">
                    <template #icon>
                        <PhotoIcon/>
                    </template>
                    Media Library
                </MenuItem>
            </MenuGroupBody>
            <MenuDelimiter/>
            <!-- Pro Features Section -->
            <MenuGroupHeader>
                Pro Features
            </MenuGroupHeader>
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.analytics.index')" :active="$page.component === 'Analytics'">
                    <template #icon>
                        <ChartBar/>
                    </template>
                    Analytics
                </MenuItem>
                <MenuItem :url="route('mixpost.schedule.index')" :active="$page.component === 'PostingSchedule'">
                    <template #icon>
                        <QueueList/>
                    </template>
                    Posting Schedule
                </MenuItem>
                <MenuItem :url="route('mixpost.variables.index')" :active="$page.component === 'Variables'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </template>
                    Variables
                </MenuItem>
                <MenuItem :url="route('mixpost.hashtagGroups.index')" :active="$page.component === 'HashtagGroups'">
                    <template #icon>
                        <Hashtag/>
                    </template>
                    Hashtag Groups
                </MenuItem>
                <MenuItem :url="route('mixpost.templates.index')" :active="$page.component === 'PostTemplates'">
                    <template #icon>
                        <Document/>
                    </template>
                    Post Templates
                </MenuItem>
                <MenuItem :url="route('mixpost.approvals.index')" :active="$page.component === 'Approvals'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    Approvals
                </MenuItem>
                <MenuItem :url="route('mixpost.translations.index')" :active="$page.component === 'Translations'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </template>
                    Languages
                </MenuItem>
            </MenuGroupBody>
            <MenuDelimiter/>
            <!-- Enterprise Section -->
            <MenuGroupHeader>
                Enterprise
            </MenuGroupHeader>
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.workspaces.index')" :active="$page.component === 'Workspaces'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </template>
                    Workspaces
                </MenuItem>
                <MenuItem :url="route('mixpost.webhooks.index')" :active="$page.component === 'Webhooks'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </template>
                    Webhooks
                </MenuItem>
                <MenuItem :url="route('mixpost.apiTokens.index')" :active="$page.component === 'ApiTokens'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </template>
                    API Tokens
                </MenuItem>
                <MenuItem :url="route('mixpost.branding.index')" :active="$page.component === 'Branding'">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </template>
                    White Label
                </MenuItem>
            </MenuGroupBody>
            <MenuDelimiter/>
            <MenuGroupHeader :create-url="route('mixpost.posts.create')">
                Configuration
            </MenuGroupHeader>
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.accounts.index')" :active="$page.component === 'Accounts/Accounts'">
                    <template #icon>
                        <ShareIcon/>
                    </template>
                    Social Accounts
                </MenuItem>
                <MenuItem :url="route('mixpost.services.index')" :active="$page.component === 'Services'">
                    <template #icon>
                        <ServerStackIcon/>
                    </template>
                    Services
                </MenuItem>
                <MenuItem :url="route('mixpost.settings.index')" :active="$page.component === 'Settings'">
                    <template #icon>
                        <CogIcon/>
                    </template>
                    Settings
                </MenuItem>
            </MenuGroupBody>
            <MenuDelimiter/>
            <MenuGroupHeader>
                System
            </MenuGroupHeader>
            <MenuGroupBody>
                <MenuItem :url="route('mixpost.system.status')" :active="$page.component === 'System/Status'">
                    <template #icon>
                        <InformationCircle/>
                    </template>
                    Status
                </MenuItem>
                <MenuItem :url="route('mixpost.system.logs.index')" :active="$page.component === 'System/Logs'">
                    <template #icon>
                        <Document/>
                    </template>
                    Logs
                </MenuItem>
                <template v-if="$page.props.app.horizon_path">
                    <MenuItem :url="`/${$page.props.app.horizon_path}`" :external="true" externalTarget="_blank">
                        <template #icon>
                            <QueueList/>
                        </template>
                        Laravel Horizon
                    </MenuItem>
                </template>
            </MenuGroupBody>
        </div>

        <div class="px-xl pt-md mb-[3.0rem]">
            <UserMenu/>
        </div>

        <div class="absolute bottom-0 mb-sm w-full">
            <MenuDelimiter/>
            <div class="flex flex-col items-start px-xl mt-sm">
                <div class="text-sm text-gray-500 mb-xs">Lite version: {{ $page.props.mixpost.version }}</div>
            </div>
        </div>
    </div>
</template>

