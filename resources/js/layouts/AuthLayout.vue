<script setup lang="ts">
import { useT } from '@/composables/useT';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useT();
</script>

<template>
    <AuthLayout
        :title="title ? t(title) : ''"
        :description="description ? t(description) : ''"
    >
        <slot />
    </AuthLayout>
</template>
