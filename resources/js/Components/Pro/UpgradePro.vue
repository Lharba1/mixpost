<script setup>
/**
 * UpgradePro component - UNLOCKED VERSION
 * 
 * This component has been modified to pass through all Pro features
 * without showing the paywall modal. All 14 Pro features are now
 * available in Mixpost Lite.
 */

// Simply render the slot content without any modal/paywall behavior
</script>
<template>
    <!-- Pro features unlocked - pass through content directly -->
    <template v-if="!$slots.trigger">
        <slot/>
    </template>

    <template v-else>
        <slot name="trigger"/>
    </template>
</template>
