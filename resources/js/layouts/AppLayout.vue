<script setup lang="ts">
import { computed } from 'vue';
import { useT } from '@/composables/useT';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const { t } = useT();

const translatedBreadcrumbs = computed<BreadcrumbItem[]>(() =>
    breadcrumbs.map((breadcrumb) => ({
        ...breadcrumb,
        title: t(breadcrumb.title),
    })),
);
</script>

<template>
    <AppLayout :breadcrumbs="translatedBreadcrumbs">
        <slot />
    </AppLayout>
</template>
