<script setup>
/**
 * ProLabel component - HIDDEN VERSION
 * 
 * This component is now hidden since all Pro features are unlocked.
 * It used to show a "Pro" badge on locked features.
 */

defineProps({
    withTitle: {
        type: Boolean,
        default: true
    },
    paddingClass: {
        type: String,
        default: 'py-[1px] px-1'
    },
    name: {
        type: String,
        default: 'Pro'
    },
    icon: {
        type: String,
        default: 'lock-closed'
    }
})
</script>
<template>
    <!-- Pro features unlocked - hide the Pro label -->
</template>
