<script setup>
import { useI18n } from "vue-i18n";
import { Menu as MenuIcon } from "lucide-vue-next";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNavListItem from "@/shared/components/nav/AppNavListItem.vue";

const { t } = useI18n();

defineProps({
    menus: { type: Array, required: true },
    selectedId: { type: Number, default: null },
});

defineEmits(["select"]);
</script>

<template>
    <aside class="lg:w-72 shrink-0 space-y-2">
        <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.menus.title") }}</h2>

        <AppNoData v-if="!menus.length" :message="t('backend.menus.empty')" />

        <div v-else class="space-y-0.5">
            <AppNavListItem
                v-for="menu in menus"
                :key="menu.id"
                :active="selectedId === menu.id"
                v-on:click="$emit('select', menu)"
            >
                <template #icon>
                    <MenuIcon class="w-4 h-4" :stroke-width="2" />
                </template>
                <span class="block truncate">{{ menu.name }}</span>
                <span class="block text-xs opacity-70 font-mono truncate">{{ menu.location }}</span>
                <template #trailing>
                    <AppBadge :color="selectedId === menu.id ? 'accent' : 'gray'">
                        {{ menu.itemCount }}
                    </AppBadge>
                </template>
            </AppNavListItem>
        </div>
    </aside>
</template>
