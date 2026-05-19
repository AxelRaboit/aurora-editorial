<script setup>
import { ref, computed } from "vue";
import { ChevronDown, ChevronRight, Pencil, Trash2, ExternalLink, EyeOff, Eye, Link } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";

const props = defineProps({
    item: { type: Object, required: true },
    targetTypes: { type: Array, default: () => [] },
    draggingId: { type: Number, default: null },
    dragOverId: { type: Number, default: null },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits([
    "edit",
    "delete",
    "item-drag-start",
    "item-drag-end",
    "item-drag-over",
    "item-drag-leave",
    "drop-on-item",
]);

const expanded = ref(true);

const children = computed(() => props.item.children ?? []);
const hasChildren = computed(() => children.value.length > 0);
const isDragOver = computed(() => props.dragOverId === props.item.id);
const isBeingDragged = computed(() => props.draggingId === props.item.id);

// 1rem per level — same convention as the taxonomy term tree.
const indentStyle = computed(() => ({ marginLeft: `${props.depth * 1}rem` }));

function visibilityIcon(item) {
    if (item.visibility === "guests_only" || item.visibility === "authenticated_only") return EyeOff;
    return Eye;
}
</script>

<template>
    <div>
        <div
            class="group flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors min-w-0 cursor-grab active:cursor-grabbing"
            :class="[
                isDragOver
                    ? 'bg-accent-600/15 text-accent-400 border-accent-600/30 ring-2 ring-accent-500'
                    : 'hover:bg-surface-2 text-primary border-line/60 bg-surface',
                isBeingDragged ? 'opacity-40' : '',
            ]"
            :style="indentStyle"
            :draggable="true"
            v-on:dragstart.stop="emit('item-drag-start', item, $event)"
            v-on:dragend="emit('item-drag-end', $event)"
            v-on:dragover="emit('item-drag-over', item, $event)"
            v-on:dragleave="emit('item-drag-leave', item, $event)"
            v-on:drop="emit('drop-on-item', item, $event)"
        >
            <AppIconButton
                v-if="hasChildren"
                size="sm"
                variant="ghost"
                class="-ml-1 shrink-0"
                :title="expanded ? $t('shared.common.collapse') : $t('shared.common.expand')"
                v-on:click.stop="expanded = !expanded"
            >
                <ChevronDown v-if="expanded" class="w-3 h-3" :stroke-width="2" />
                <ChevronRight v-else class="w-3 h-3" :stroke-width="2" />
            </AppIconButton>
            <span v-else class="w-4 shrink-0" />

            <Link class="w-4 h-4 shrink-0" :class="isDragOver ? 'text-accent-400' : 'text-muted'" :stroke-width="2" />

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm truncate" :class="{ 'text-rose-400': item.targetPreview?.missing }">
                        {{ item.targetPreview?.label ?? "—" }}
                    </span>
                    <AppBadge v-if="item.translations?.fr || item.translations?.en" color="accent" class="shrink-0">
                        {{ Object.keys(item.translations).filter((l) => item.translations[l]).join(", ") }}
                    </AppBadge>
                    <AppBadge v-if="item.openInNewTab" color="gray" class="shrink-0">
                        <ExternalLink class="w-3 h-3" :stroke-width="2.5" />
                    </AppBadge>
                    <AppBadge v-if="item.visibility !== 'always'" color="amber" class="shrink-0">
                        <component :is="visibilityIcon(item)" class="w-3 h-3" :stroke-width="2.5" />
                    </AppBadge>
                </div>
                <p v-if="item.targetPreview?.hint" class="text-xs text-muted truncate font-mono mt-0.5">{{ item.targetPreview.hint }}</p>
            </div>

            <div class="opacity-0 group-hover:opacity-100 flex gap-0.5 transition-opacity shrink-0">
                <AppButton variant="secondary" size="sm" v-on:click.stop="emit('edit', item)">
                    <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                </AppButton>
                <AppButton variant="danger" size="sm" v-on:click.stop="emit('delete', item)">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                </AppButton>
            </div>
        </div>

        <div v-if="hasChildren && expanded" class="space-y-2 mt-2">
            <MenuItemRow
                v-for="child in children"
                :key="child.id"
                :item="child"
                :target-types="targetTypes"
                :dragging-id="draggingId"
                :drag-over-id="dragOverId"
                :depth="depth + 1"
                v-on:edit="emit('edit', $event)"
                v-on:delete="emit('delete', $event)"
                v-on:item-drag-start="(n, e) => emit('item-drag-start', n, e)"
                v-on:item-drag-end="(e) => emit('item-drag-end', e)"
                v-on:item-drag-over="(n, e) => emit('item-drag-over', n, e)"
                v-on:item-drag-leave="(n, e) => emit('item-drag-leave', n, e)"
                v-on:drop-on-item="(n, e) => emit('drop-on-item', n, e)"
            />
        </div>
    </div>
</template>
