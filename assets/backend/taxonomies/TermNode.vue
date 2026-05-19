<script setup>
import { computed } from "vue";
import { ChevronDown, ChevronRight, Pencil, Trash2, Plus, Tag, Folder } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";

const props = defineProps({
    node: { type: Object, required: true },
    hierarchical: { type: Boolean, default: false },
    activeLocale: { type: String, required: true },
    collapsed: { type: Object, required: true },
    draggingId: { type: Number, default: null },
    dragOverId: { type: Number, default: null },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits([
    "edit",
    "delete",
    "add-child",
    "toggle-collapse",
    "term-drag-start",
    "term-drag-end",
    "term-drag-over",
    "term-drag-leave",
    "drop-on-term",
]);

const name = computed(
    () =>
        props.node.translations?.[props.activeLocale]?.name
        ?? props.node.translations?.fr?.name
        ?? "(—)",
);
const slug = computed(() => props.node.translations?.[props.activeLocale]?.slug ?? "");

const isCollapsed = computed(() => props.collapsed.has(props.node.id));
const children = computed(() => props.node.children ?? []);
const hasChildren = computed(() => children.value.length > 0);
const isDragOver = computed(() => props.dragOverId === props.node.id);
const isBeingDragged = computed(() => props.draggingId === props.node.id);

// Children indent only — the row card itself spans full width so its
// edges always align with the surrounding header / hint banner.
const indentStyle = computed(() => ({ marginLeft: `${props.depth * 1}rem` }));
</script>

<template>
    <div>
        <!--
            The full row is a single rounded-lg card. Chevron, icon, label,
            slug and action buttons are all inside it, so the card spans
            edge-to-edge of its container and aligns with the AppMessage
            banner above. Depth indent is applied as margin-left on the
            card itself.
        -->
        <div
            class="group flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors min-w-0 cursor-grab active:cursor-grabbing"
            :class="[
                isDragOver
                    ? 'bg-accent-600/15 text-accent-400 border-accent-600/30 ring-2 ring-accent-500'
                    : 'hover:bg-surface-2 text-primary border-transparent',
                isBeingDragged ? 'opacity-40' : '',
            ]"
            :style="indentStyle"
            :draggable="true"
            v-on:dragstart.stop="emit('term-drag-start', node, $event)"
            v-on:dragend="emit('term-drag-end', $event)"
            v-on:dragover="emit('term-drag-over', node, $event)"
            v-on:dragleave="emit('term-drag-leave', node, $event)"
            v-on:drop="emit('drop-on-term', node, $event)"
        >
            <AppIconButton
                v-if="hierarchical && hasChildren"
                size="sm"
                variant="ghost"
                class="-ml-1 shrink-0"
                :title="isCollapsed ? $t('shared.common.expand') : $t('shared.common.collapse')"
                v-on:click.stop="emit('toggle-collapse', node.id)"
            >
                <ChevronRight v-if="isCollapsed" class="w-3 h-3" :stroke-width="2" />
                <ChevronDown v-else class="w-3 h-3" :stroke-width="2" />
            </AppIconButton>

            <component
                :is="hasChildren ? Folder : Tag"
                class="w-4 h-4 shrink-0 text-muted"
                :stroke-width="2"
            />

            <span class="flex-1 text-sm truncate min-w-0">{{ name }}</span>
            <span v-if="slug" class="text-xs text-muted font-mono shrink-0 hidden sm:inline">{{ slug }}</span>

            <div class="opacity-0 group-hover:opacity-100 flex gap-0.5 transition-opacity shrink-0">
                <AppIconButton v-if="hierarchical" color="sky" :title="$t('backend.taxonomies.terms.addChild')" v-on:click.stop="emit('add-child', node.id)">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="accent" v-on:click.stop="emit('edit', node)">
                    <Pencil class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="rose" v-on:click.stop="emit('delete', node)">
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>
        </div>

        <div v-if="hierarchical && !isCollapsed && hasChildren" class="space-y-0.5 mt-0.5">
            <TermNode
                v-for="child in children"
                :key="child.id"
                :node="child"
                :hierarchical="hierarchical"
                :active-locale="activeLocale"
                :collapsed="collapsed"
                :dragging-id="draggingId"
                :drag-over-id="dragOverId"
                :depth="depth + 1"
                v-on:toggle-collapse="emit('toggle-collapse', $event)"
                v-on:edit="emit('edit', $event)"
                v-on:delete="emit('delete', $event)"
                v-on:add-child="emit('add-child', $event)"
                v-on:term-drag-start="(n, e) => emit('term-drag-start', n, e)"
                v-on:term-drag-end="(e) => emit('term-drag-end', e)"
                v-on:term-drag-over="(n, e) => emit('term-drag-over', n, e)"
                v-on:term-drag-leave="(n, e) => emit('term-drag-leave', n, e)"
                v-on:drop-on-term="(n, e) => emit('drop-on-term', n, e)"
            />
        </div>
    </div>
</template>
