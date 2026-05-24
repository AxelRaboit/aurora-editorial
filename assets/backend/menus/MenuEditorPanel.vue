<script setup>
 
import { toRef } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Trash2, Pencil, ListTree } from "lucide-vue-next";
import { useMenuTree } from "@editorial/backend/menus/composables/useMenuTree.js";
import { useMenuDragDrop } from "@editorial/backend/menus/composables/useMenuDragDrop.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import MenuItemRow from "@editorial/backend/menus/MenuItemRow.vue";

const { t } = useI18n();

const props = defineProps({
    menu: { type: Object, default: null },
    targetTypes: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "edit-menu",
    "delete-menu",
    "add-item",
    "edit-item",
    "delete-item",
    "reorder-root",
]);

const menuRef = toRef(props, "menu");
const { itemCount } = useMenuTree(menuRef);

const {
    draggingId, dragOverId, rootDragOver,
    onItemDragStart, onItemDragEnd, onItemDragOver, onItemDragLeave,
    onRootDragOver, onRootDragLeave, onDropOnItem, onDropOnRoot,
} = useMenuDragDrop({
    menuRef,
    persist: () => emit("reorder-root"),
});
</script>

<template>
    <main class="flex-1 min-w-0 space-y-4">
        <AppNoData v-if="!menu" :message="t('backend.menus.select_hint')" :icon="ListTree" />

        <div v-else class="space-y-4">
            <!-- Header card — menu meta + edit/delete actions (matches TaxonomiesApp pattern) -->
            <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-primary">{{ menu.name }}</h3>
                        <p class="text-xs text-muted font-mono mt-0.5">{{ menu.location }}</p>
                        <div v-if="menu.protected" class="flex items-center gap-2 mt-2">
                            <AppBadge color="amber">
                                {{ t('backend.menus.protected') }}
                            </AppBadge>
                        </div>
                        <p v-if="menu.description" class="text-xs text-secondary mt-2">{{ menu.description }}</p>
                    </div>
                    <div class="flex gap-2">
                        <AppButton variant="secondary" size="md" v-on:click="$emit('edit-menu', menu)">
                            <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.common.edit") }}
                        </AppButton>
                        <AppButton
                            v-if="!menu.protected"
                            variant="danger"
                            size="md"
                            v-on:click="$emit('delete-menu', menu)"
                        >
                            <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.common.delete") }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- Items card — list + native DnD + add -->
            <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">
                        {{ t("backend.menus.items") }} ({{ itemCount }})
                    </h4>
                    <AppButton variant="primary" size="md" v-on:click="$emit('add-item')">
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.add") }}
                    </AppButton>
                </div>

                <AppNoData v-if="!menu.items?.length" :message="t('backend.menus.items_empty')" />

                <div
                    v-else
                    class="space-y-2 p-1 rounded-md transition-colors"
                    :class="rootDragOver ? 'bg-accent-50 dark:bg-accent-900/10 ring-1 ring-accent-500/40' : ''"
                    v-on:dragover="onRootDragOver"
                    v-on:dragleave="onRootDragLeave"
                    v-on:drop="onDropOnRoot"
                >
                    <MenuItemRow
                        v-for="item in menu.items"
                        :key="item.id"
                        :item="item"
                        :target-types="targetTypes"
                        :dragging-id="draggingId"
                        :drag-over-id="dragOverId"
                        v-on:edit="$emit('edit-item', $event)"
                        v-on:delete="$emit('delete-item', $event)"
                        v-on:item-drag-start="onItemDragStart"
                        v-on:item-drag-end="onItemDragEnd"
                        v-on:item-drag-over="onItemDragOver"
                        v-on:item-drag-leave="onItemDragLeave"
                        v-on:drop-on-item="onDropOnItem"
                    />
                </div>
            </div>
        </div>
    </main>
</template>
